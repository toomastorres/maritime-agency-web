<?php
/**
 * AMA — config.php
 * Configuración central: rutas base, constantes de la aplicación.
 */

// Defensa en profundidad: archivo solo para inclusión, nunca acceso web directo.
if (isset($_SERVER['SCRIPT_FILENAME']) && @realpath($_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

// Enforce Buenos Aires, Argentina timezone globally for exact financial/log operations
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Load environment variables for global constants
require_once dirname(__DIR__) . '/core/utils.php';
$env = loadEnv(dirname(__DIR__, 2) . '/.env');

// SMTP — Configuracion del servidor de correo (el hosting SMTPS)
define('SMTP_HOST',      $env['SMTP_HOST'] ?? '');
define('SMTP_PORT',      $env['SMTP_PORT'] ?? '465');
define('SMTP_SECURE',    $env['SMTP_SECURE'] ?? 'ssl');
define('SMTP_USER',      $env['SMTP_USER'] ?? '');
define('SMTP_PASS',      $env['SMTP_PASS'] ?? '');
define('SMTP_FROM',      $env['SMTP_FROM'] ?? $env['SMTP_USER'] ?? '');
define('SMTP_FROM_NAME', $env['SMTP_FROM_NAME'] ?? 'AMA Sistemas');
define('SMTP_REPLY_TO',  $env['SMTP_REPLY_TO'] ?? '');
define('NOTIFY_TO',      $env['NOTIFY_TO'] ?? (defined('SMTP_FROM') ? SMTP_FROM : ''));

// Canales de notificación por área
define('NOTIFY_RRHH', $env['NOTIFY_RRHH'] ?? NOTIFY_TO);
define('NOTIFY_CAJA', $env['NOTIFY_CAJA'] ?? NOTIFY_TO);
define('NOTIFY_DOCS', $env['NOTIFY_DOCS'] ?? NOTIFY_TO);
define('NOTIFY_CS', $env['NOTIFY_CS'] ?? NOTIFY_TO);
define('NOTIFY_COMERCIAL_AUTOS', $env['NOTIFY_COMERCIAL_AUTOS'] ?? NOTIFY_TO);
define('NOTIFY_COMERCIAL_PROYECTO', $env['NOTIFY_COMERCIAL_PROYECTO'] ?? NOTIFY_TO);
define('NOTIFY_COMERCIAL_CONTAINERS', $env['NOTIFY_COMERCIAL_CONTAINERS'] ?? NOTIFY_TO);

// Autodetect the base protocol and host (for BASE_URL)
// Handles: direct SSL, el hosting shared hosting, and reverse proxy / SSL termination setups
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');
$protocol = $isHttps ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($path === '\\' || $path === '/') {
    $path = '';
}

// Ensure the BASE_URL ends with a slash for consistent concatenation
define('BASE_URL', rtrim($protocol . $domainName . $path, '/') . '/');

// Root directory of the application
define('ROOT_DIR', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);

// Derived URL constants
define('ASSETS_URL', BASE_URL . 'public/assets');
define('CSS_URL', BASE_URL . 'public/assets/css');
define('JS_URL', BASE_URL . 'public/assets/js');

/**
 * Genera URL de asset con cache-busting basado en hash de contenido.
 * Permite caché agresiva (1 año) ya que el hash cambia al modificar el archivo.
 */
function assetUrl(string $relativePath): string {
    $fullPath = ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $hash = file_exists($fullPath) ? substr(md5_file($fullPath), 0, 8) : 'x';
    return ASSETS_URL . '/' . $relativePath . '?v=' . $hash;
}

?>
