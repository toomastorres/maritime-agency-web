<?php
/**
 * AMA — tc_mailbot.php
 * API Endpoint / tarea programada: GET /api/tc_mailbot
 *
 * Dispara una corrida del bot que responde el buzón de tipo de cambio.
 * Pensado para ejecutarse cada pocos minutos desde el cron del hosting.
 *
 * Uso:
 *   CLI:  php /ruta/public_html/src/logic/api/tc_mailbot.php
 *   CLI:  php ... tc_mailbot.php --simulacro    (no toca el buzón ni la base)
 *   HTTP: GET /api/tc_mailbot?token=XXX
 *   HTTP: GET /api/tc_mailbot con header X-TC-Bot-Token: XXX
 *
 * Por HTTP exige TC_BOT_TOKEN. Por CLI no: ahí la autorización es tener
 * shell en el servidor.
 */

$esCli = (php_sapi_name() === 'cli');

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/core/database.php';
require_once dirname(__DIR__, 2) . '/core/tc_mailbot.php';

$env = loadEnv(dirname(__DIR__, 3) . '/.env');

// ── Autorización ───────────────────────────────────────────

if (!$esCli) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');

    // checkRateLimit() responde por sí mismo si se excede, así que va
    // después de fijar el Content-Type.
    checkRateLimit(10, 60);

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'error' => 'Method Not Allowed']);
        exit;
    }

    $esperado = $env['TC_BOT_TOKEN'] ?? '';
    $recibido = $_SERVER['HTTP_X_TC_BOT_TOKEN'] ?? ($_GET['token'] ?? '');

    if ($esperado === '' || !hash_equals($esperado, (string)$recibido)) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'error' => 'Unauthorized']);
        exit;
    }
}

// ── Cerrojo: una corrida por vez ───────────────────────────
// Si una corrida se demora más que el intervalo del cron, la siguiente no
// debe pisarla. La bitácora ya evita respuestas duplicadas, pero mejor no
// gastar llamadas a Graph de a dos.

$lockFile = dirname(__DIR__, 2) . '/data/cache/tc_mailbot.lock';
if (!is_dir(dirname($lockFile))) {
    @mkdir(dirname($lockFile), 0755, true);
}
$lock = @fopen($lockFile, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    $msg = ['status' => 'skipped', 'error' => 'Ya hay una corrida en curso.'];
    if ($esCli) {
        fwrite(STDERR, "Ya hay una corrida en curso.\n");
        exit(0);
    }
    http_response_code(409);
    echo json_encode($msg);
    exit;
}

// ── Corrida ────────────────────────────────────────────────

$simulacro = $esCli
    ? in_array('--simulacro', $argv ?? [], true)
    : isset($_GET['simulacro']);

$bot = new TcMailBot($pdo, new GraphMail($env), [
    'max_mensajes'      => (int)($env['TC_BOT_MAX_MENSAJES'] ?? 25),
    'max_por_remitente' => (int)($env['TC_BOT_MAX_POR_REMITENTE'] ?? 3),
    'antiguedad_horas'  => (int)($env['TC_BOT_ANTIGUEDAD_HORAS'] ?? 12),
    'simulacro'         => $simulacro,
    'url_publica'       => $env['TC_BOT_URL_PUBLICA'] ?? 'https://atlantic-agency.example.com/tipo-de-cambio',
]);

$resumen = $bot->correr();

flock($lock, LOCK_UN);
fclose($lock);

// ── Salida ─────────────────────────────────────────────────

if ($esCli) {
    printf(
        "[%s] %s | estado TC: %s | revisados: %d, respondidos: %d, omitidos: %d, errores: %d%s\n",
        date('Y-m-d H:i:s'),
        $resumen['ok'] ? 'OK' : 'FALLO',
        $resumen['estado_tc'] ?? '-',
        $resumen['revisados'], $resumen['respondidos'],
        $resumen['omitidos'], $resumen['errores'],
        isset($resumen['error']) ? ' | ' . $resumen['error'] : ''
    );

    if ($simulacro) {
        foreach ($resumen['detalle'] as $d) {
            printf("  - %-22s %-28s %s\n", $d['accion'], $d['motivo'], $d['remitente']);
        }
    }

    exit($resumen['ok'] ? 0 : 1);
}

// Por HTTP no se exponen remitentes ni asuntos por defecto:
if (!isset($_GET['debug'])) {
    unset($resumen['detalle']);
}

http_response_code($resumen['ok'] ? 200 : 503);
echo json_encode(array_merge(
    ['status' => $resumen['ok'] ? 'ok' : 'error'],
    $resumen,
    ['timestamp' => date('c')]
), JSON_UNESCAPED_UNICODE);
