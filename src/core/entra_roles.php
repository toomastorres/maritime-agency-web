<?php
/**
 * AMA — entra_roles.php
 * Traducción de los App Roles de Entra ID a los roles internos del portal.
 *
 * La AUTORIDAD sobre el rol es el claim "roles" del token de Microsoft.
 * El administrador otorga y quita accesos desde Entra ID; el portal sólo
 * traduce. La columna rol_portal de la base queda como reflejo del último
 * login, útil para listados y auditoría, nunca como fuente de permisos.
 */

// Defensa en profundidad: archivo solo para inclusión, nunca acceso web directo.
if (isset($_SERVER['SCRIPT_FILENAME']) && @realpath($_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

// ── Mapa App Role (Entra) → rol interno ────────────────────
// La clave es el campo "value" del App Role en el manifest de la aplicación.
// El valor debe existir en el ENUM rol_portal de usuarios_admin.

const ENTRA_ROLE_MAP = [
    'Portal.AdminGlobal'   => 'admin_global',
    'Portal.RRHH'          => 'rrhh',
    'Portal.CS'            => 'cs',
    'Portal.Caja'          => 'caja',
    'Portal.Documentacion' => 'documentacion',
];

// ── Prioridad cuando el token trae varios roles ────────────
// Entra permite asignar más de un App Role a la misma persona, pero el
// portal maneja un rol por usuario. Se toma el de mayor privilegio.

const ENTRA_ROLE_PRIORITY = [
    'admin_global',
    'rrhh',
    'cs',
    'caja',
    'documentacion',
];

/**
 * Traduce el claim "roles" del token al rol interno efectivo.
 *
 * Fail-closed: si no hay ningún App Role reconocido devuelve null y el
 * login se rechaza. Nunca existe un rol por defecto.
 *
 * @param  mixed $claimRoles Claim "roles" del ID token (array o ausente).
 * @return string|null Rol interno, o null si no corresponde ninguno.
 */
function entra_map_role($claimRoles): ?string
{
    if (!is_array($claimRoles) || $claimRoles === []) {
        return null;
    }

    $mapped = [];
    foreach ($claimRoles as $role) {
        if (!is_string($role)) {
            continue;
        }
        $role = trim($role);
        if (isset(ENTRA_ROLE_MAP[$role])) {
            $mapped[ENTRA_ROLE_MAP[$role]] = true;
        }
    }

    if ($mapped === []) {
        return null;
    }

    foreach (ENTRA_ROLE_PRIORITY as $candidate) {
        if (isset($mapped[$candidate])) {
            return $candidate;
        }
    }

    return null;
}

/**
 * Lista los App Roles del token que el portal no reconoce.
 * Sirve para avisar al administrador de una asignación mal escrita en Entra.
 *
 * @param  mixed $claimRoles
 * @return array
 */
function entra_unknown_roles($claimRoles): array
{
    if (!is_array($claimRoles)) {
        return [];
    }

    $unknown = [];
    foreach ($claimRoles as $role) {
        if (is_string($role) && trim($role) !== '' && !isset(ENTRA_ROLE_MAP[trim($role)])) {
            $unknown[] = trim($role);
        }
    }

    return $unknown;
}

/**
 * Valida un destino interno post-login contra una lista blanca.
 * Evita open redirect: sólo se permiten rutas del portal de gestión.
 *
 * @param  string|null $target Valor sin confianza.
 * @return string Ruta segura.
 */
function entra_safe_redirect(?string $target): string
{
    $fallback = '/gestion-interna-agencia/dashboard.php';

    if ($target === null || $target === '') {
        return $fallback;
    }
    // Caracteres de control y saltos de línea (header injection).
    if (preg_match('/[\x00-\x1F\x7F]/', $target)) {
        return $fallback;
    }
    // URLs absolutas, protocol-relative ("//evil.com") y backslashes.
    if (preg_match('#^(?:[a-z][a-z0-9+.\-]*:|//|\\\\)#i', $target)) {
        return $fallback;
    }
    if ($target[0] !== '/') {
        return $fallback;
    }
    // Path traversal, incluso codificado ("..", "%2e%2e", "..%2F").
    $decoded = rawurldecode($target);
    if (strpos($decoded, '..') !== false || strpos($decoded, '\\') !== false) {
        return $fallback;
    }
    // Sólo rutas del portal interno, evaluadas sobre la ruta sin query string.
    $path = explode('?', $target, 2)[0];
    if (strpos($path, '/gestion-interna-agencia/') === 0) {
        return $target;
    }

    return $fallback;
}
