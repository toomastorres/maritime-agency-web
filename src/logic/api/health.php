<?php
/**
 * AMA — health.php
 * API Endpoint: GET /api/health
 *
 * Healthcheck protegido por token. Permite monitorear externamente
 * (UptimeRobot, cron, etc.) si la conexión a BD funciona, sin exponer
 * credenciales ni mensajes de error detallados.
 *
 * IMPORTANTE: este endpoint NO incluye database.php directamente porque
 * eso mataría el script con 500 antes de poder reportar el estado. En su
 * lugar, carga utils.php, lee .env y prueba la conexión en un try/catch
 * aislado.
 *
 * Uso:
 *   GET /api/health                       -> 401 sin token
 *   GET /api/health?token=XXX             -> 200 ok/fail según BD
 *   GET /api/health con header X-Health-Token: XXX
 */

require_once dirname(__DIR__, 2) . '/core/utils.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

// Rate limit: máximo 10 requests/minuto por IP. No usamos jsonResponse()
// porque checkRateLimit() ya responde por sí mismo si se excede.
checkRateLimit(10, 60);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Method Not Allowed']);
    exit;
}

// Cargar .env
$env = loadEnv(dirname(__DIR__, 3) . '/.env');
$expectedToken = $env['HEALTH_TOKEN'] ?? '';

// Verificar token (header o query string). Comparación timing-safe.
$providedToken = $_SERVER['HTTP_X_HEALTH_TOKEN'] ?? ($_GET['token'] ?? '');

if ($expectedToken === '' || !hash_equals($expectedToken, (string)$providedToken)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'Unauthorized']);
    exit;
}

// Evaluar estado
$envLoaded = !empty($env) && !empty($env['DB_HOST']) && !empty($env['DB_USER']) && !empty($env['DB_NAME']);
$dbStatus = 'unknown';
$dbErrorCode = null;

if ($envLoaded) {
    try {
        $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO(
            $dsn,
            $env['DB_USER'],
            $env['DB_PASS'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]
        );
        $pdo->query('SELECT 1')->fetch();
        $dbStatus = 'ok';
    } catch (\Throwable $e) {
        $dbStatus = 'fail';
        // Exponemos SOLO el código SQLSTATE, nunca el mensaje completo.
        // SQLSTATE basta para clasificar el tipo de error (08xxx = conexión, 28xxx = auth, etc).
        if ($e instanceof PDOException && isset($e->errorInfo[0])) {
            $dbErrorCode = (string)$e->errorInfo[0];
        } else {
            $dbErrorCode = 'HY000';
        }
    }
} else {
    $dbStatus = 'fail';
    $dbErrorCode = 'ENV_MISSING';
}

http_response_code($dbStatus === 'ok' ? 200 : 503);
echo json_encode([
    'status'     => $dbStatus === 'ok' ? 'ok' : 'error',
    'db'         => $dbStatus,
    'env_loaded' => $envLoaded,
    'error_code' => $dbErrorCode,
    'timestamp'  => date('c'),
], JSON_UNESCAPED_UNICODE);
