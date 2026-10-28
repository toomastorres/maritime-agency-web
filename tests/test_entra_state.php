<?php
require_once __DIR__ . '/../src/core/entra_oidc.php';
session_start();

$rc = new ReflectionClass('EntraOidc');
$cfg = $rc->getProperty('config'); $cfg->setAccessible(true);
$cfg->setValue(null, [
    'tenant_id' => 'T', 'client_id' => 'C', 'client_secret' => 'S',
    'redirect_uri' => 'https://x/cb', 'logout_uri' => 'https://x/',
]);

$fail = 0;
function chk($n, $ok) { global $fail; if (!$ok) $fail++; printf("  %s  %-52s %s\n", $ok?'PASS':'FAIL', $n, EntraOidc::lastError() ?? ''); }

// Transacción válida en curso
function seedTx(string $state, int $age = 0): void {
    $_SESSION['entra_tx'] = [
        'state' => $state, 'nonce' => 'N', 'verifier' => 'V',
        'return_to' => '', 'created_at' => time() - $age,
    ];
}

// 1. state manipulado -> rechazo ANTES de cualquier llamada de red
seedTx('ESTADO_REAL');
$r = EntraOidc::handleCallback('codigo', 'ESTADO_FALSO');
chk('state manipulado -> rechazado', $r === null);
chk('  y la transaccion se consumio (single-use)', !isset($_SESSION['entra_tx']));

// 2. replay: reusar el mismo state despues de consumido
seedTx('ESTADO_REAL');
EntraOidc::handleCallback('codigo', 'ESTADO_REAL');   // consume (fallara en red, da igual)
$r = EntraOidc::handleCallback('codigo', 'ESTADO_REAL'); // replay
chk('replay del mismo state -> rechazado', $r === null);

// 3. sin transaccion previa (callback directo sin pasar por login)
unset($_SESSION['entra_tx']);
$r = EntraOidc::handleCallback('codigo', 'CUALQUIERA');
chk('callback sin login previo -> rechazado', $r === null);

// 4. transaccion expirada (TTL 600s)
seedTx('ESTADO_REAL', 601);
$r = EntraOidc::handleCallback('codigo', 'ESTADO_REAL');
chk('transaccion expirada (>600s) -> rechazado', $r === null);

// 5. dentro del TTL: pasa la validacion de state (luego falla en red, esperado)
seedTx('ESTADO_REAL', 599);
$r = EntraOidc::handleCallback('codigo', 'ESTADO_REAL');
chk('dentro del TTL -> supera state, cae en red', $r === null && strpos((string)EntraOidc::lastError(), 'Microsoft') !== false);

// 6. state vacio
seedTx('ESTADO_REAL');
$r = EntraOidc::handleCallback('codigo', '');
chk('state vacio -> rechazado', $r === null);

echo $fail === 0 ? "\nTODOS OK\n" : "\n$fail FALLOS\n";
exit($fail === 0 ? 0 : 1);
