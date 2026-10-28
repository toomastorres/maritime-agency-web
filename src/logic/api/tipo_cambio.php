<?php
/**
 * AMA — tipo_cambio.php
 * API Endpoint: GET /api/tipo_cambio
 *
 * Devuelve cotizaciones del día (solo en horario comercial L-V 09:00-17:00).
 * La regla de vigencia vive en core/tipo_cambio.php: este archivo solo la
 * traduce a JSON y la cachea.
 */

require_once dirname(__DIR__, 2) . '/core/database.php';
require_once dirname(__DIR__, 2) . '/core/tipo_cambio.php';

checkRateLimit(60, 60);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Vary: Accept-Encoding');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método HTTP no permitido.']);
    exit;
}

/**
 * Respuesta de los estados sin cotización. No se cachean: `hora_local`
 * quedaría vieja y, sobre todo, la transición de "aún no cargado" a
 * "cargado" tiene que verse en el acto.
 *
 * @param  array $estado Salida de tcEstadoActual().
 * @return void
 */
function tc_responder_sin_cotizacion(array $estado): void {
    echo json_encode([
        'status'  => $estado['status'],
        'mensaje' => $estado['mensaje'],
        'data'    => [
            'datos_bancarios' => $estado['datos_bancarios'],
            // El navegador del visitante puede estar en cualquier huso: la
            // hora que vale para el horario comercial es la de la agencia.
            'hora_local'      => $estado['hora_local'],
            'fecha_local'     => $estado['fecha_local'],
            'horario'         => $estado['horario'],
        ],
    ], JSON_UNESCAPED_UNICODE);
}

// ── 1. Fuera de horario ────────────────────────────────────
// Se resuelve antes de tocar la caché: una cotización cacheada a las 16:58
// no puede seguir sirviéndose a las 17:05.

if (!tcEnHorario()) {
    tc_responder_sin_cotizacion(tcEstadoActual($pdo ?? null));
    exit;
}

// ── 2. Caché de cotización vigente (TTL 5 minutos) ─────────
// Antes de consultar la base: un HIT no debe costar queries.

$cache_file = dirname(__DIR__, 2) . '/data/cache/tipo_cambio.json';
$cache_ttl  = 300;

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
    $cached_data = file_get_contents($cache_file);
    if ($cached_data) {
        header('X-Cache: HIT');
        echo $cached_data;
        exit;
    }
}
header('X-Cache: MISS');

// ── 3. Estado real ─────────────────────────────────────────

$estado = tcEstadoActual($pdo ?? null);

if ($estado['status'] === 'error') {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $estado['mensaje']], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($estado['status'] !== 'ok') {
    tc_responder_sin_cotizacion($estado);
    exit;
}

$response_json = json_encode([
    'status'  => 'ok',
    'mensaje' => $estado['mensaje'],
    'data'    => [
        'dolar_valor'          => $estado['dolar_valor'],
        'euro_valor'           => $estado['euro_valor'],
        'datos_bancarios'      => $estado['datos_bancarios'],
        'ultima_actualizacion' => $estado['ultima_actualizacion'],
        // Sin hora_local: esta respuesta se cachea 5 minutos y serviría
        // una hora vieja. Las ramas cerrado/no_disponible no se cachean.
        'horario'              => $estado['horario'],
    ],
], JSON_UNESCAPED_UNICODE);

// Guardar caché (silenciosamente fallará si no hay permisos, lo cual está bien en read-only)
atomicCacheWrite($cache_file, $response_json);

echo $response_json;
