<?php
/**
 * AMA — schedules.php
 * API Endpoint: GET /api/schedules
 *
 * Devuelve itinerarios de buques con filtro opcional por nombre.
 *
 * Respuesta JSON:
 * {
 *   "status": "ok"|"sin_datos",
 *   "ultima_actualizacion": "dd/mm/YYYY",
 *   "week_number": 11,
 *   "total": N,
 *   "rutas": [
 *     {
 *       "nombre": "Grande Amburgo",
 *       "voyage_code": "GHA0126",
 *       "viajes": [
 *         { "puerto": "Antwerp", "eta": "07/02/2026", "ets": "11/02/2026",
 *           "is_feeder": false, "feeder_code": null }
 *       ]
 *     }
 *   ]
 * }
 *
 * Si las tablas nuevas no existen aún, cae automáticamente al sistema legacy (tabla schedules).
 */

require_once dirname(__DIR__, 2) . '/core/database.php';

checkRateLimit(60, 60);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('Vary: Accept-Encoding');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Método HTTP no permitido.']);
    exit;
}

$filtro_buque = isset($_GET['buque']) ? mb_substr(trim($_GET['buque']), 0, 150) : '';
// Los filtros POL/POD no aplican al nuevo modelo columnar; se mantienen por compatibilidad de URL
$filtro_pol   = isset($_GET['pol'])   ? mb_substr(trim($_GET['pol']), 0, 150)   : '';
$filtro_pod   = isset($_GET['pod'])   ? mb_substr(trim($_GET['pod']), 0, 150)   : '';

try {
    // 0. Cache Handler
    $cache_key = md5("{$filtro_buque}_{$filtro_pol}_{$filtro_pod}");
    $cache_file = dirname(__DIR__, 2) . "/data/cache/schedules_{$cache_key}.json";
    $cache_ttl = 3600;

    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
        $cached_data = file_get_contents($cache_file);
        if ($cached_data) {
            header('X-Cache: HIT');
            echo $cached_data;
            exit;
        }
    }
    header('X-Cache: MISS');

    $response_json = '';

    // ── 1. Intentar nuevo sistema schedule_imports ────────────────────────────
    $use_new = false;
    $lote    = null;

    try {
        $chk = $pdo->query("SHOW TABLES LIKE 'schedule_imports'");
        if ($chk->rowCount() > 0) {
            $s    = $pdo->query(
                "SELECT id, service_name, week_number, creado_en
                 FROM schedule_imports
                 ORDER BY creado_en DESC
                 LIMIT 1"
            );
            $lote    = $s->fetch(PDO::FETCH_ASSOC);
            $use_new = ($lote !== false);
        }
    } catch (\PDOException $e) {
        // Tabla no existe — caer al sistema legacy
    }

    if ($use_new && $lote) {
        $import_id = (int)$lote['id'];

        $sql  = "SELECT vessel_name, voyage_code, port_name, eta, ets, is_feeder, feeder_code, port_order, vessel_order, block_number, leg
                 FROM schedule_port_calls
                 WHERE import_id = ?
                 ORDER BY port_order ASC, vessel_order ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$import_id]);
        $pcs  = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($filtro_buque !== '') {
            $pcs = array_values(array_filter($pcs, function ($r) use ($filtro_buque) {
                return stripos($r['vessel_name'], $filtro_buque) !== false;
            }));
        }

        $rutas_map = [];
        foreach ($pcs as $pc) {
            $key = $pc['vessel_name'] . '||' . $pc['voyage_code'];
            if (!isset($rutas_map[$key])) {
                $rutas_map[$key] = [
                    'nombre'       => $pc['vessel_name'],
                    'voyage_code'  => $pc['voyage_code'],
                    'vessel_order' => (int)$pc['vessel_order'],
                    'viajes'       => [],
                ];
            }
            $rutas_map[$key]['viajes'][] = [
                'puerto'      => $pc['port_name'],
                'eta'         => $pc['eta'] ? date('d/m/Y', strtotime($pc['eta'])) : null,
                'ets'         => $pc['ets'] ? date('d/m/Y', strtotime($pc['ets'])) : null,
                'is_feeder'   => (bool)(int)$pc['is_feeder'],
                'feeder_code' => $pc['feeder_code'],
                'leg'         => $pc['leg'] ?? 'outbound',
                'port_order'  => (int)$pc['port_order'],
            ];
        }

        $response_json = json_encode([
            'status'               => count($rutas_map) > 0 ? 'ok' : 'sin_datos',
            'ultima_actualizacion' => date('d/m/Y', strtotime($lote['creado_en'])),
            'week_number'          => (int)$lote['week_number'],
            'service_name'         => $lote['service_name'] ?? null,
            'total'                => count($pcs),
            'rutas'                => array_values($rutas_map),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        atomicCacheWrite($cache_file, $response_json);
        echo $response_json;
        exit;
    }

    // ── 2. Fallback legacy: tabla schedules ───────────────────────────────────
    $stmt_hash = $pdo->query(
        "SELECT hash_importacion, MAX(creado_en) as fecha_subida
         FROM schedules
         GROUP BY hash_importacion
         ORDER BY fecha_subida DESC
         LIMIT 1"
    );
    $lote_legacy = $stmt_hash->fetch(PDO::FETCH_ASSOC);

    if (!$lote_legacy) {
        $response_json = json_encode([
            'status'               => 'sin_datos',
            'ultima_actualizacion' => null,
            'week_number'          => null,
            'total'                => 0,
            'rutas'                => [],
        ], JSON_UNESCAPED_UNICODE);
        atomicCacheWrite($cache_file, $response_json);
        echo $response_json;
        exit;
    }

    $hash         = $lote_legacy['hash_importacion'];
    $fecha_subida = $lote_legacy['fecha_subida'];

    $sql = "SELECT buque, viaje, eta, etd, puerto_origen, puerto_destino, datos_extra
            FROM schedules
            WHERE hash_importacion = ?
            ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hash]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($filtro_buque !== '') {
        $rows = array_values(array_filter($rows, function ($r) use ($filtro_buque) {
            return stripos($r['buque'], $filtro_buque) !== false;
        }));
    }
    if ($filtro_pol !== '') {
        $rows = array_values(array_filter($rows, function ($r) use ($filtro_pol) {
            return stripos((string)($r['puerto_origen'] ?? ''), $filtro_pol) !== false;
        }));
    }
    if ($filtro_pod !== '') {
        $rows = array_values(array_filter($rows, function ($r) use ($filtro_pod) {
            return stripos((string)($r['puerto_destino'] ?? ''), $filtro_pod) !== false;
        }));
    }

    $rutas_map = [];
    foreach ($rows as $row) {
        $buque      = $row['buque'];
        $viaje_data = [];

        if (!empty($row['datos_extra'])) {
            $extra = json_decode($row['datos_extra'], true);
            if (isset($extra['row']) && is_array($extra['row'])) {
                $raw_row     = $extra['row'];
                $raw_headers = $extra['headers'] ?? null;
                if ($raw_headers && is_array($raw_headers)) {
                    $header_row = [];
                    foreach ($raw_headers as $h_row) {
                        foreach ($h_row as $col => $cell_val) {
                            if (!empty(trim((string)$cell_val))) {
                                $header_row[$col] = trim((string)$cell_val);
                            }
                        }
                    }
                    foreach ($raw_row as $col_letter => $cell_val) {
                        $col_name             = $header_row[$col_letter] ?? $col_letter;
                        $viaje_data[$col_name] = $cell_val;
                    }
                } else {
                    $viaje_data = $raw_row;
                }
            }
        }

        if (empty($viaje_data)) {
            $viaje_data = ['buque' => $row['buque'], 'viaje' => $row['viaje']];
            if ($row['eta'])            $viaje_data['ETA'] = $row['eta'];
            if ($row['etd'])            $viaje_data['ETD'] = $row['etd'];
            if ($row['puerto_origen'])  $viaje_data['POL'] = $row['puerto_origen'];
            if ($row['puerto_destino']) $viaje_data['POD'] = $row['puerto_destino'];
        }

        if (!isset($rutas_map[$buque])) $rutas_map[$buque] = [];
        $rutas_map[$buque][] = $viaje_data;
    }

    $rutas = [];
    foreach ($rutas_map as $nombre_buque => $viajes) {
        $rutas[] = ['nombre' => $nombre_buque, 'viajes' => $viajes];
    }

    $response_json = json_encode([
        'status'               => 'ok',
        'ultima_actualizacion' => date('d/m/Y', strtotime($fecha_subida)),
        'week_number'          => null,
        'total'                => count($rows),
        'rutas'                => $rutas,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    atomicCacheWrite($cache_file, $response_json);
    echo $response_json;

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Error al obtener los schedules.']);
}
