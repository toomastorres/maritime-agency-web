<?php
/**
 * AMA — entra_oidc.php
 * Cliente OpenID Connect para Microsoft Entra ID.
 *
 * Authorization Code Flow + PKCE, cliente confidencial.
 * Implementación nativa (curl + openssl), sin dependencias de Composer,
 * siguiendo el criterio del proyecto de no depender de vendor/ en runtime.
 *
 * Los tokens NUNCA llegan al navegador: se validan en el servidor, se
 * extraen los claims necesarios y se descartan.
 *
 * Requiere PHP 7.4+ con las extensiones curl, openssl y json.
 */

// Defensa en profundidad: archivo solo para inclusión, nunca acceso web directo.
if (isset($_SERVER['SCRIPT_FILENAME']) && @realpath($_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/utils.php';

class EntraOidc
{
    /** Tolerancia de reloj al validar exp/nbf, en segundos. */
    const CLOCK_SKEW = 60;

    /** Vida máxima de la transacción de login (state/nonce/verifier), en segundos. */
    const TX_TTL = 600;

    /** TTL del caché de JWKS y discovery, en segundos. */
    const JWKS_TTL = 86400;

    /** Timeout de las llamadas salientes a Microsoft, en segundos. */
    const HTTP_TIMEOUT = 10;

    /** @var array|null Configuración cargada desde .env */
    private static $config = null;

    /** @var string|null Último error legible para el usuario */
    private static $lastError = null;

    // ── Configuración ──────────────────────────────────────────

    /**
     * Carga y cachea la configuración de Entra desde el .env.
     *
     * @return array
     */
    private static function config(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $env = loadEnv(dirname(__DIR__, 2) . '/.env');

        self::$config = [
            'tenant_id'     => trim($env['ENTRA_TENANT_ID'] ?? ''),
            'client_id'     => trim($env['ENTRA_CLIENT_ID'] ?? ''),
            'client_secret' => trim($env['ENTRA_CLIENT_SECRET'] ?? ''),
            'redirect_uri'  => trim($env['ENTRA_REDIRECT_URI'] ?? ''),
            'logout_uri'    => trim($env['ENTRA_POST_LOGOUT_URI'] ?? ''),
        ];

        return self::$config;
    }

    /**
     * Indica si Entra ID está configurado y operativo.
     * Si devuelve false, el portal cae al login local (break-glass).
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        $c = self::config();
        return $c['tenant_id'] !== ''
            && $c['client_id'] !== ''
            && $c['client_secret'] !== ''
            && $c['redirect_uri'] !== '';
    }

    /**
     * Último error de autenticación, apto para mostrar al usuario.
     *
     * @return string|null
     */
    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    /**
     * Emisor esperado de los tokens de este tenant.
     *
     * @return string
     */
    private static function expectedIssuer(): string
    {
        return 'https://login.microsoftonline.com/' . self::config()['tenant_id'] . '/v2.0';
    }

    // ── Paso 1: redirección a Microsoft ────────────────────────

    /**
     * Construye la URL de /authorize y guarda state, nonce y code_verifier
     * en la sesión del servidor. Nunca viajan por cookie ni por la URL.
     *
     * @param  string $returnTo Ruta interna a la que volver tras el login.
     * @return string URL absoluta de Microsoft.
     */
    public static function buildAuthorizationUrl(string $returnTo = ''): string
    {
        $c = self::config();

        $state    = bin2hex(random_bytes(32));
        $nonce    = bin2hex(random_bytes(32));
        $verifier = self::b64uEncode(random_bytes(64));
        $challenge = self::b64uEncode(hash('sha256', $verifier, true));

        $_SESSION['entra_tx'] = [
            'state'      => $state,
            'nonce'      => $nonce,
            'verifier'   => $verifier,
            'return_to'  => $returnTo,
            'created_at' => time(),
        ];

        $params = [
            'client_id'             => $c['client_id'],
            'response_type'         => 'code',
            'redirect_uri'          => $c['redirect_uri'],
            'response_mode'         => 'query',
            'scope'                 => 'openid profile email',
            'state'                 => $state,
            'nonce'                 => $nonce,
            'code_challenge'        => $challenge,
            'code_challenge_method' => 'S256',
        ];

        return 'https://login.microsoftonline.com/' . rawurlencode($c['tenant_id'])
            . '/oauth2/v2.0/authorize?' . http_build_query($params);
    }

    // ── Paso 2: callback ───────────────────────────────────────

    /**
     * Procesa el retorno de Microsoft: valida el state, canjea el código
     * y valida el ID token por completo.
     *
     * @param  string $code  Código de autorización recibido.
     * @param  string $state State recibido.
     * @return array|null    Claims validados, o null si algo falló.
     */
    public static function handleCallback(string $code, string $state): ?array
    {
        $tx = $_SESSION['entra_tx'] ?? null;

        // La transacción es de un solo uso: se consume pase lo que pase.
        unset($_SESSION['entra_tx']);

        if (!is_array($tx) || empty($tx['state'])) {
            return self::fail('No se encontró una solicitud de login en curso. Volvé a intentar.', 'oidc_state_ausente');
        }

        if (time() - (int)$tx['created_at'] > self::TX_TTL) {
            return self::fail('La solicitud de login expiró. Volvé a intentar.', 'oidc_tx_expirada');
        }

        if (!hash_equals($tx['state'], $state)) {
            return self::fail('La solicitud de login no es válida.', 'oidc_state_mismatch');
        }

        $tokens = self::exchangeCode($code, $tx['verifier']);
        if ($tokens === null) {
            return null;
        }

        if (empty($tokens['id_token'])) {
            return self::fail('Microsoft no devolvió un token de identidad.', 'oidc_sin_id_token');
        }

        $claims = self::validateIdToken($tokens['id_token'], $tx['nonce']);
        if ($claims === null) {
            return null;
        }

        $claims['__return_to'] = $tx['return_to'] ?? '';
        return $claims;
    }

    /**
     * Canjea el código de autorización por tokens. Servidor a servidor.
     *
     * @param  string $code
     * @param  string $verifier code_verifier de PKCE.
     * @return array|null
     */
    private static function exchangeCode(string $code, string $verifier): ?array
    {
        $c = self::config();

        $body = http_build_query([
            'client_id'     => $c['client_id'],
            'client_secret' => $c['client_secret'],
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $c['redirect_uri'],
            'code_verifier' => $verifier,
            'scope'         => 'openid profile email',
        ]);

        $url = 'https://login.microsoftonline.com/' . rawurlencode($c['tenant_id']) . '/oauth2/v2.0/token';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            // El detalle de curl va al log, nunca a la pantalla.
            error_log('[entra] fallo de red en /token: ' . $cerr);
            return self::fail('No se pudo contactar a Microsoft. Reintentá en unos minutos.', 'oidc_red');
        }

        $json = json_decode($raw, true);

        if ($http !== 200 || !is_array($json)) {
            $code = is_array($json) ? ($json['error'] ?? 'desconocido') : 'respuesta_invalida';
            error_log('[entra] /token HTTP ' . $http . ' error=' . $code);
            return self::fail('Microsoft rechazó la solicitud de acceso.', 'oidc_token_error');
        }

        return $json;
    }

    // ── Validación del ID token ────────────────────────────────

    /**
     * Valida el ID token completo. Las ocho comprobaciones son obligatorias:
     * firma, alg, iss, aud, exp/nbf, nonce, tid y presencia de oid.
     *
     * @param  string $jwt
     * @param  string $expectedNonce
     * @return array|null Claims si todo valida.
     */
    private static function validateIdToken(string $jwt, string $expectedNonce): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return self::fail('Token de identidad malformado.', 'oidc_token_invalido');
        }

        list($h64, $p64, $s64) = $parts;

        $header  = json_decode(self::b64uDecode($h64), true);
        $claims  = json_decode(self::b64uDecode($p64), true);
        $sigRaw  = self::b64uDecode($s64);

        if (!is_array($header) || !is_array($claims)) {
            return self::fail('Token de identidad ilegible.', 'oidc_token_invalido');
        }

        // (2) alg: sólo RS256. Rechaza "none" y algoritmos simétricos.
        if (($header['alg'] ?? '') !== 'RS256') {
            return self::fail('Algoritmo de firma no admitido.', 'oidc_alg_invalido');
        }

        $kid = $header['kid'] ?? '';
        if ($kid === '') {
            return self::fail('El token no indica la clave de firma.', 'oidc_sin_kid');
        }

        // (1) Firma contra el JWKS del tenant. Si el kid es desconocido se
        // refresca el caché una vez: Microsoft rota las claves periódicamente.
        $pem = self::publicKeyForKid($kid, false);
        if ($pem === null) {
            $pem = self::publicKeyForKid($kid, true);
        }
        if ($pem === null) {
            return self::fail('No se pudo verificar la firma del token.', 'oidc_kid_desconocido');
        }

        $ok = openssl_verify($h64 . '.' . $p64, $sigRaw, $pem, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return self::fail('La firma del token no es válida.', 'oidc_firma_invalida');
        }

        $c   = self::config();
        $now = time();

        // (3) iss
        if (($claims['iss'] ?? '') !== self::expectedIssuer()) {
            return self::fail('El emisor del token no es el esperado.', 'oidc_iss_invalido');
        }

        // (4) aud
        if (($claims['aud'] ?? '') !== $c['client_id']) {
            return self::fail('El token fue emitido para otra aplicación.', 'oidc_aud_invalido');
        }

        // (5) exp / nbf
        if (!isset($claims['exp']) || $now > ((int)$claims['exp'] + self::CLOCK_SKEW)) {
            return self::fail('El token de identidad expiró.', 'oidc_token_expirado');
        }
        if (isset($claims['nbf']) && $now < ((int)$claims['nbf'] - self::CLOCK_SKEW)) {
            return self::fail('El token todavía no es válido.', 'oidc_token_nbf');
        }

        // (6) nonce
        if (!isset($claims['nonce']) || !hash_equals($expectedNonce, (string)$claims['nonce'])) {
            return self::fail('El token no corresponde a esta solicitud de login.', 'oidc_nonce_mismatch');
        }

        // (7) tid — sin esto, cualquier tenant de Microsoft podría entrar.
        if (($claims['tid'] ?? '') !== $c['tenant_id']) {
            error_log('[entra] tid ajeno rechazado: ' . substr((string)($claims['tid'] ?? ''), 0, 40));
            return self::fail('Tu cuenta no pertenece a la organización.', 'oidc_tenant_mismatch');
        }

        // (8) oid — identificador inmutable. Sin él no se puede identificar.
        if (empty($claims['oid'])) {
            return self::fail('El token no incluye el identificador de usuario.', 'oidc_sin_oid');
        }

        return $claims;
    }

    // ── JWKS ───────────────────────────────────────────────────

    /**
     * Devuelve la clave pública en PEM para un kid dado.
     *
     * @param  string $kid
     * @param  bool   $forceRefresh Ignora el caché y vuelve a bajar el JWKS.
     * @return string|null
     */
    private static function publicKeyForKid(string $kid, bool $forceRefresh): ?string
    {
        $jwks = self::jwks($forceRefresh);
        if (!isset($jwks['keys']) || !is_array($jwks['keys'])) {
            return null;
        }

        foreach ($jwks['keys'] as $key) {
            if (($key['kid'] ?? '') !== $kid) {
                continue;
            }

            // Camino preferido: Microsoft publica el certificado en x5c.
            if (!empty($key['x5c'][0])) {
                $pem = "-----BEGIN CERTIFICATE-----\n"
                    . chunk_split($key['x5c'][0], 64, "\n")
                    . "-----END CERTIFICATE-----\n";
                $res = openssl_pkey_get_public($pem);
                if ($res !== false) {
                    return $pem;
                }
            }

            // Alternativa: reconstruir la clave RSA desde el módulo y el exponente.
            if (!empty($key['n']) && !empty($key['e'])) {
                $pem = self::rsaJwkToPem($key['n'], $key['e']);
                if ($pem !== null) {
                    return $pem;
                }
            }
        }

        return null;
    }

    /**
     * Obtiene el JWKS del tenant, con caché en disco.
     *
     * @param  bool $forceRefresh
     * @return array
     */
    private static function jwks(bool $forceRefresh): array
    {
        $cacheFile = dirname(__DIR__) . '/data/cache/entra_jwks.json';

        if (!$forceRefresh && is_readable($cacheFile)) {
            $age = time() - (int) @filemtime($cacheFile);
            if ($age < self::JWKS_TTL) {
                $cached = json_decode((string) @file_get_contents($cacheFile), true);
                if (is_array($cached) && !empty($cached['keys'])) {
                    return $cached;
                }
            }
        }

        $url = 'https://login.microsoftonline.com/' . rawurlencode(self::config()['tenant_id'])
            . '/discovery/v2.0/keys';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw !== false && $http === 200) {
            $json = json_decode($raw, true);
            if (is_array($json) && !empty($json['keys'])) {
                $dir = dirname($cacheFile);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                @file_put_contents($cacheFile, $raw, LOCK_EX);
                return $json;
            }
        }

        // Si la descarga falló, usar el caché vencido antes que quedarse sin claves.
        if (is_readable($cacheFile)) {
            $stale = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($stale) && !empty($stale['keys'])) {
                error_log('[entra] JWKS no disponible, usando caché vencido');
                return $stale;
            }
        }

        return [];
    }

    /**
     * Reconstruye una clave pública RSA en PEM a partir del módulo y el
     * exponente de un JWK (DER SubjectPublicKeyInfo).
     *
     * @param  string $nB64u Módulo en base64url.
     * @param  string $eB64u Exponente en base64url.
     * @return string|null
     */
    private static function rsaJwkToPem(string $nB64u, string $eB64u): ?string
    {
        $n = self::b64uDecode($nB64u);
        $e = self::b64uDecode($eB64u);

        if ($n === '' || $e === '') {
            return null;
        }

        // ASN.1 INTEGER es con signo: anteponer 0x00 si el bit alto está prendido.
        if (ord($n[0]) > 0x7F) {
            $n = "\x00" . $n;
        }
        if (ord($e[0]) > 0x7F) {
            $e = "\x00" . $e;
        }

        $nDer = "\x02" . self::derLength(strlen($n)) . $n;
        $eDer = "\x02" . self::derLength(strlen($e)) . $e;

        $rsaSeq = "\x30" . self::derLength(strlen($nDer . $eDer)) . $nDer . $eDer;

        // BIT STRING con 0 bits sin usar.
        $bitString = "\x03" . self::derLength(strlen($rsaSeq) + 1) . "\x00" . $rsaSeq;

        // AlgorithmIdentifier: OID 1.2.840.113549.1.1.1 (rsaEncryption) + NULL.
        $algId = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

        $spki = "\x30" . self::derLength(strlen($algId . $bitString)) . $algId . $bitString;

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($spki), 64, "\n")
            . "-----END PUBLIC KEY-----\n";

        return openssl_pkey_get_public($pem) === false ? null : $pem;
    }

    /**
     * Codifica una longitud en formato DER.
     *
     * @param  int $len
     * @return string
     */
    private static function derLength(int $len): string
    {
        if ($len <= 0x7F) {
            return chr($len);
        }
        $bytes = ltrim(pack('N', $len), "\x00");
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    // ── Logout ─────────────────────────────────────────────────

    /**
     * URL de cierre de sesión en Microsoft (RP-initiated logout).
     *
     * @return string
     */
    public static function logoutUrl(): string
    {
        $c = self::config();
        $params = [];
        if ($c['logout_uri'] !== '') {
            $params['post_logout_redirect_uri'] = $c['logout_uri'];
        }

        return 'https://login.microsoftonline.com/' . rawurlencode($c['tenant_id'])
            . '/oauth2/v2.0/logout' . ($params ? '?' . http_build_query($params) : '');
    }

    // ── Utilidades ─────────────────────────────────────────────

    /**
     * Registra el error y devuelve null, para cortar el flujo en el llamador.
     *
     * @param  string $userMessage Mensaje apto para mostrar.
     * @param  string $auditCode   Código para el log de auditoría.
     * @return null
     */
    private static function fail(string $userMessage, string $auditCode)
    {
        self::$lastError = $userMessage;
        $_SESSION['entra_audit_code'] = $auditCode;
        return null;
    }

    /**
     * Código de auditoría del último fallo.
     *
     * @return string
     */
    public static function lastAuditCode(): string
    {
        return $_SESSION['entra_audit_code'] ?? 'oidc_error';
    }

    /**
     * @param  string $data
     * @return string
     */
    private static function b64uEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param  string $data
     * @return string
     */
    private static function b64uDecode(string $data): string
    {
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }
        $out = base64_decode(strtr($data, '-_', '+/'), true);
        return $out === false ? '' : $out;
    }
}
