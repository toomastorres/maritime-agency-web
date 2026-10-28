<?php
/**
 * Test del cliente OIDC nativo contra criptografía real.
 * Genera un par RSA, arma un JWKS y un ID token firmado, y verifica
 * que EntraOidc::validateIdToken() acepte el válido y rechace los ataques.
 */

require_once __DIR__ . '/../src/core/entra_oidc.php';

session_start();

$TENANT = '11111111-2222-3333-4444-555555555555';
$CLIENT = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
$ISSUER = "https://login.microsoftonline.com/{$TENANT}/v2.0";
$KID    = 'test-key-1';

// ── Generar par RSA + certificado autofirmado ──────────────

$pkey = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);
$details = openssl_pkey_get_details($pkey);

$dn = ['countryName' => 'AR', 'commonName' => 'test.ama'];
$csr  = openssl_csr_new($dn, $pkey, ['digest_alg' => 'sha256']);
$x509 = openssl_csr_sign($csr, null, $pkey, 365, ['digest_alg' => 'sha256']);
openssl_x509_export($x509, $certPem);
$x5c = preg_replace('/-----[A-Z ]+-----|\s/', '', $certPem);

function b64u(string $d): string { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }

// ── Escribir el JWKS al caché que lee EntraOidc ────────────

$cacheFile = __DIR__ . '/../src/data/cache/entra_jwks.json';
@mkdir(dirname($cacheFile), 0755, true);

function writeJwks(string $file, array $keys): void {
    file_put_contents($file, json_encode(['keys' => $keys]));
    touch($file); // asegurar mtime fresco
}

$keyX5C = ['kid' => $KID, 'kty' => 'RSA', 'use' => 'sig', 'x5c' => [$x5c]];
$keyNE  = ['kid' => $KID, 'kty' => 'RSA', 'use' => 'sig',
           'n' => b64u($details['rsa']['n']), 'e' => b64u($details['rsa']['e'])];

// ── Inyectar configuración ─────────────────────────────────

$rc = new ReflectionClass('EntraOidc');
$cfg = $rc->getProperty('config');
$cfg->setAccessible(true);
$cfg->setValue(null, [
    'tenant_id'     => $TENANT,
    'client_id'     => $CLIENT,
    'client_secret' => 'secreto',
    'redirect_uri'  => 'https://atlantic-agency.example.com/gestion-interna-agencia/auth/callback.php',
    'logout_uri'    => 'https://atlantic-agency.example.com/',
]);

$validate = $rc->getMethod('validateIdToken');
$validate->setAccessible(true);

// ── Fábrica de tokens ──────────────────────────────────────

function makeToken(array $claimOverrides, $pkey, string $kid, string $alg = 'RS256', bool $badSig = false): string {
    global $ISSUER, $CLIENT, $TENANT;
    $now = time();
    $claims = array_merge([
        'iss'   => $ISSUER,
        'aud'   => $CLIENT,
        'tid'   => $TENANT,
        'oid'   => '99999999-8888-7777-6666-555555555555',
        'nonce' => 'NONCE_OK',
        'exp'   => $now + 3600,
        'nbf'   => $now - 10,
        'iat'   => $now,
        'name'  => 'Prueba Usuario',
        'preferred_username' => 'prueba@atlantic-agency.example.com',
        'roles' => ['Portal.AdminGlobal'],
    ], $claimOverrides);

    foreach ($claims as $k => $v) {
        if ($v === '__UNSET__') unset($claims[$k]);
    }

    $h = b64u(json_encode(['alg' => $alg, 'typ' => 'JWT', 'kid' => $kid]));
    $p = b64u(json_encode($claims));

    if ($alg === 'none') return "$h.$p.";

    openssl_sign("$h.$p", $sig, $pkey, OPENSSL_ALGO_SHA256);
    if ($badSig) $sig = strrev($sig);
    return "$h.$p." . b64u($sig);
}

// ── Casos ──────────────────────────────────────────────────

$cases = [];

$cases[] = ['Token válido (x5c)', $keyX5C, makeToken([], $pkey, $KID), 'NONCE_OK', true];
$cases[] = ['Token válido (n/e sin x5c)', $keyNE, makeToken([], $pkey, $KID), 'NONCE_OK', true];
$cases[] = ['Firma alterada', $keyX5C, makeToken([], $pkey, $KID, 'RS256', true), 'NONCE_OK', false];
$cases[] = ['alg: none', $keyX5C, makeToken([], $pkey, $KID, 'none'), 'NONCE_OK', false];
$cases[] = ['tid de OTRO tenant', $keyX5C, makeToken(['tid' => '00000000-0000-0000-0000-000000000000'], $pkey, $KID), 'NONCE_OK', false];
$cases[] = ['aud de OTRA aplicación', $keyX5C, makeToken(['aud' => 'otra-app-guid'], $pkey, $KID), 'NONCE_OK', false];
$cases[] = ['iss de otro emisor', $keyX5C, makeToken(['iss' => 'https://login.microsoftonline.com/otro/v2.0'], $pkey, $KID), 'NONCE_OK', false];
$cases[] = ['Token expirado', $keyX5C, makeToken(['exp' => time() - 3600], $pkey, $KID), 'NONCE_OK', false];
$cases[] = ['nbf en el futuro', $keyX5C, makeToken(['nbf' => time() + 3600], $pkey, $KID), 'NONCE_OK', false];
$cases[] = ['nonce distinto (replay)', $keyX5C, makeToken(['nonce' => 'OTRO'], $pkey, $KID), 'NONCE_OK', false];
$cases[] = ['nonce ausente', $keyX5C, makeToken(['nonce' => '__UNSET__'], $pkey, $KID), 'NONCE_OK', false];
$cases[] = ['sin oid', $keyX5C, makeToken(['oid' => '__UNSET__'], $pkey, $KID), 'NONCE_OK', false];
$cases[] = ['kid desconocido', $keyX5C, makeToken([], $pkey, 'kid-inexistente'), 'NONCE_OK', false];
$cases[] = ['Token malformado', $keyX5C, 'no.es.un.jwt.valido', 'NONCE_OK', false];
$cases[] = ['Token vacío', $keyX5C, '', 'NONCE_OK', false];
$cases[] = ['exp dentro del skew (59s pasado)', $keyX5C, makeToken(['exp' => time() - 59], $pkey, $KID), 'NONCE_OK', true];

$fail = 0;
foreach ($cases as [$name, $jwkSet, $token, $nonce, $shouldPass]) {
    writeJwks($cacheFile, [$jwkSet]);
    $r = new ReflectionClass('EntraOidc');
    $m = $r->getMethod('validateIdToken'); $m->setAccessible(true);

    $got = $m->invoke(null, $token, $nonce);
    $passed = ($got !== null);
    $ok = ($passed === $shouldPass);
    if (!$ok) $fail++;

    printf("  %s  %-38s %s\n",
        $ok ? 'PASS' : 'FAIL',
        $name,
        $passed ? 'ACEPTADO' : 'rechazado: ' . (EntraOidc::lastError() ?? '?')
    );
}

// ── Verificar que el PEM reconstruido equivale al original ──

$r = new ReflectionClass('EntraOidc');
$toPem = $r->getMethod('rsaJwkToPem'); $toPem->setAccessible(true);
$rebuilt = $toPem->invoke(null, b64u($details['rsa']['n']), b64u($details['rsa']['e']));
$rebuiltNorm = preg_replace('/\s/', '', (string)$rebuilt);
$originalNorm = preg_replace('/\s/', '', $details['key']);
$same = ($rebuiltNorm === $originalNorm);
if (!$same) $fail++;
printf("  %s  %-38s %s\n", $same ? 'PASS' : 'FAIL',
    'PEM reconstruido == PEM original', $same ? 'idéntico' : 'DIFIERE');

@unlink($cacheFile);
echo $fail === 0 ? "\nTODOS OK (" . (count($cases) + 1) . " casos)\n" : "\n$fail FALLOS\n";
exit($fail === 0 ? 0 : 1);
