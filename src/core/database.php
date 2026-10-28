<?php
/**
 * AMA — database.php
 * Conexión centralizada a base de datos (PDO).
 *
 * Lee credenciales desde .env vía loadEnv().
 */

// Defensa en profundidad: archivo solo para inclusión, nunca acceso web directo.
if (isset($_SERVER['SCRIPT_FILENAME']) && @realpath($_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/utils.php';

// Prevent multiple connections
if (isset($pdo) && $pdo instanceof PDO) {
    return;
}

// Load environment variables
$env = loadEnv(dirname(__DIR__, 2) . '/.env');

$DB_HOST    = $env['DB_HOST']    ?? 'localhost';
$DB_USER    = $env['DB_USER']    ?? '';
$DB_PASS    = $env['DB_PASS']    ?? '';
$DB_NAME    = $env['DB_NAME']    ?? '';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);

    // Forzar la zona horaria de la sesión MySQL para que CURDATE() y NOW()
    // devuelvan la fecha/hora de Argentina, no la del servidor (que opera en
    // UTC). Sin esto, CURDATE() cambia de día a las 21:00 ART y las queries
    // de tipo de cambio (WHERE fecha_cotizacion = CURDATE()) dejan de
    // encontrar la cotización del día tres horas antes de lo esperado.
    $pdo->exec("SET time_zone = '-03:00'");
} catch (\PDOException $e) {
    // Detectar variables de entorno faltantes para diagnóstico en log
    $missing = [];
    foreach (['DB_HOST', 'DB_USER', 'DB_NAME'] as $k) {
        if (empty($env[$k])) { $missing[] = $k; }
    }
    $extra = $missing ? ' | Missing env: ' . implode(',', $missing) : '';

    // Log the real error securely, never expose to client
    $logDir = dirname(__DIR__, 2) . '/src/data/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @error_log(
        '[' . date('Y-m-d H:i:s') . '] DB Connection Error: ' . $e->getMessage() . $extra . PHP_EOL,
        3,
        $logDir . '/app.log'
    );

    // Return a generic error — never expose DB details
    if (php_sapi_name() === 'cli') {
        die("Error de conexión a la base de datos.\n");
    }

    http_response_code(500);
    // Determinar si la solicitud espera JSON o HTML
    $acceptsJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
                || (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);
    if ($acceptsJson) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['status' => 'error', 'error' => 'Error de conexión a la base de datos.']));
    }

    // Detectar entorno mediante APP_ENV. Default seguro: production.
    // Nunca usar HTTP_HOST: puede venir vacío en crons/FastCGI y filtrar pistas.
    $isDev = (($env['APP_ENV'] ?? 'production') === 'development');

    if ($isDev) {
        // Desarrollo: mostrar instrucciones de debug (solo cuando APP_ENV=development)
        die('<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error</title>'
          . '<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#f5f5f5}'
          . '.box{background:#fff;border-radius:8px;padding:2rem 3rem;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.1);max-width:480px}'
          . 'h2{color:#c0392b;margin-top:0}p{color:#555;line-height:1.5}code{background:#f0f0f0;padding:2px 6px;border-radius:4px;font-size:.9em}'
          . '</style></head><body><div class="box">'
          . '<h2>&#9888; Error de conexión</h2>'
          . '<p>No se pudo conectar a la base de datos.</p>'
          . '<div style="text-align:left; background:#fff3f3; padding:15px; border-left:4px solid #c0392b; margin-top:15px;">'
          . '<p style="margin-top:0;"><strong>Posibles causas:</strong></p>'
          . '<ul style="margin-bottom:0; font-size:14px;">'
          . '<li>El archivo <code>.env</code> no existe o no tiene las credenciales correctas.</li>'
          . '<li>El servidor MySQL no está corriendo en este equipo.</li>'
          . '<li>Prueba cambiar <code>DB_HOST=localhost</code> por <code>DB_HOST=127.0.0.1</code> en tu .env.</li>'
          . '</ul></div>'
          . '</div></body></html>');
    }

    // Producción: mensaje genérico, sin ningún detalle técnico ni pista
    die('<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Servicio no disponible | Atlantic Maritime Agency</title>'
      . '<style>body{font-family:"DM Sans",Verdana,Geneva,Arial,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;padding:24px;box-sizing:border-box;background:#F0F0F0;color:#1A1A1C}'
      . '.olt-maint-card{background:#fff;border:1px solid rgba(128,128,131,.35);padding:48px 40px;text-align:center;max-width:480px;width:100%;box-sizing:border-box}'
      . '.olt-maint-kicker{font-size:10px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:#808083;margin-bottom:16px}'
      . 'h2{font-family:Georgia,"DM Serif Display",serif;font-weight:400;color:#0F396C;font-size:26px;margin:0 0 16px;line-height:1.2}'
      . 'p{color:#1A1A1C;line-height:1.7;font-size:15px;margin:0 0 8px}'
      . 'a{color:#0069CC;text-decoration:none;font-weight:500}a:hover{color:#0F396C;text-decoration:underline}'
      . '</style></head><body><div class="olt-maint-card">'
      . '<div class="olt-maint-kicker">Atlantic Maritime Agency S.A.</div>'
      . '<h2>Servicio temporalmente no disponible</h2>'
      . '<p>Estamos trabajando para restablecer el servicio. Por favor intente nuevamente en unos minutos.</p>'
      . '<p style="margin-top:1.5rem;"><a href="/">Volver al inicio</a></p>'
      . '</div></body></html>');
}
