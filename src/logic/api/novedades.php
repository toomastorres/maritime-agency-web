<?php
/**
 * AMA — novedades.php
 * API Endpoint: GET /api/novedades
 *
 * Devuelve listado de noticias con filtros opcionales (categoría, búsqueda, límite).
 */

require_once dirname(__DIR__, 2) . '/core/database.php';

checkRateLimit(60, 60);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Vary: Accept-Encoding');
// CORS restringido al dominio propio
$allowedOrigin = (!empty($_SERVER['HTTP_ORIGIN']) && preg_match('/^https?:\/\/(www\.)?ama-bue\.com\.ar$/i', $_SERVER['HTTP_ORIGIN']))
    ? $_SERVER['HTTP_ORIGIN']
    : '';
if ($allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Método HTTP no permitido.']);
    exit;
}

// ─── Parámetros de filtrado ───────────────────────────────────────────────────
$id         = isset($_GET['id'])         ? (int)$_GET['id']          : 0;
$categoria  = isset($_GET['categoria'])  ? trim($_GET['categoria'])  : '';
$q          = isset($_GET['q'])          ? mb_substr(trim($_GET['q']), 0, 255) : '';
$limit      = isset($_GET['limit'])      ? (int)$_GET['limit']       : 0;
$destacadas = !empty($_GET['destacadas']);

try {
    // ─── Obtener documentos de una novedad ────────────────────────────────
    function getDocumentos($pdo, $novedad_id) {
        try {
            $stmt = $pdo->prepare("SELECT nombre_original, archivo_path, tipo_mime, tamano_bytes FROM novedad_documentos WHERE novedad_id = ? ORDER BY id ASC");
            $stmt->execute([$novedad_id]);
            $docs = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
                $size_kb = round($d['tamano_bytes'] / 1024);
                $size_label = $size_kb >= 1024 ? round($size_kb / 1024, 1) . ' MB' : $size_kb . ' KB';
                $docs[] = [
                    'nombre' => $d['nombre_original'],
                    'url'    => $d['archivo_path'],
                    'tipo'   => $d['tipo_mime'],
                    'tamano' => $size_label,
                ];
            }
            return $docs;
        } catch (\PDOException $e) {
            return [];
        }
    }

    // ─── Modo detalle: ?id=N ──────────────────────────────────────────────
    if ($id > 0) {
        $stmt = $pdo->prepare(
            "SELECT id, titulo, bajada, cuerpo, categoria, autor, destacada, etiquetas, imagen_url, fecha_iso, creado_en
             FROM novedades WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$r) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'error' => 'Novedad no encontrada.']);
            exit;
        }

        echo json_encode([
            'status'  => 'ok',
            'novedad' => [
                'id'         => (int)$r['id'],
                'titulo'     => $r['titulo'],
                'bajada'     => $r['bajada'],
                'cuerpo'     => $r['cuerpo'],
                'categoria'  => $r['categoria'],
                'autor'      => $r['autor'],
                'fecha'      => date('d/m/Y', strtotime($r['fecha_iso'])),
                'fecha_iso'  => $r['fecha_iso'],
                'destacada'  => (bool)$r['destacada'],
                'etiquetas'  => $r['etiquetas'] ? json_decode($r['etiquetas'], true) : [],
                'imagen_url' => $r['imagen_url'],
                'documentos' => getDocumentos($pdo, (int)$r['id']),
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Modo listado ─────────────────────────────────────────────────────
    $where  = [];
    $params = [];

    if ($categoria !== '') {
        $where[]  = 'LOWER(categoria) = LOWER(?)';
        $params[] = $categoria;
    }
    if ($q !== '') {
        $where[]  = '(titulo LIKE ? OR bajada LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }
    if ($destacadas) {
        $where[] = 'destacada = 1';
    }

    $sql = "SELECT id, titulo, bajada, cuerpo, categoria, autor, destacada, etiquetas, imagen_url, fecha_iso, creado_en
            FROM novedades";
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY creado_en DESC';

    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int)$limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ─── Formatear resultados ─────────────────────────────────────────────
    $novedades = [];
    foreach ($rows as $r) {
        $novedades[] = [
            'id'         => (int)$r['id'],
            'titulo'     => $r['titulo'],
            'bajada'     => $r['bajada'],
            'cuerpo'     => $r['cuerpo'],
            'categoria'  => $r['categoria'],
            'autor'      => $r['autor'],
            'fecha'      => date('d/m/Y', strtotime($r['fecha_iso'])),
            'fecha_iso'  => $r['fecha_iso'],
            'destacada'  => (bool)$r['destacada'],
            'etiquetas'  => $r['etiquetas'] ? json_decode($r['etiquetas'], true) : [],
            'imagen_url' => $r['imagen_url'],
        ];
    }

    // ─── Conteo de categorías (del set completo, no del filtrado) ─────────
    $stmt_cats = $pdo->query("SELECT categoria, COUNT(*) as total FROM novedades GROUP BY categoria");
    $categoryCounts = [];
    foreach ($stmt_cats->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $categoryCounts[$row['categoria']] = (int)$row['total'];
    }

    // ─── Fecha de última actualización ────────────────────────────────────
    $stmt_last = $pdo->query("SELECT MAX(creado_en) as ult FROM novedades");
    $ultima_raw = $stmt_last->fetchColumn();
    $ultima_actualizacion = $ultima_raw ? date('d/m/Y', strtotime($ultima_raw)) : '';

    echo json_encode([
        'status'               => 'ok',
        'ultima_actualizacion' => $ultima_actualizacion,
        'total'                => count($novedades),
        'novedades'            => $novedades,
        'categorias'           => $categoryCounts
    ], JSON_UNESCAPED_UNICODE);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error'  => 'Error al obtener las novedades.'
    ]);
}
