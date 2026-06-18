<?php
/**
 * AMA — documentos.php
 * API Endpoint: GET /api/documentos
 *
 * Devuelve los documentos publicados con su doc_key.
 */

require_once dirname(__DIR__, 2) . '/core/database.php';

checkRateLimit(60, 60);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('Vary: Accept-Encoding');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método HTTP no permitido.']);
    exit;
}

try {
    // Verificar si la columna doc_key existe (compatibilidad con migración pendiente)
    $hasDocKey = false;
    try {
        $check = $pdo->query("SHOW COLUMNS FROM public_documents LIKE 'doc_key'");
        $hasDocKey = $check->rowCount() > 0;
    } catch (\PDOException $e) {
        // Tabla puede no existir aún
    }

    if ($hasDocKey) {
        // Nuevo formato: devolver con doc_key
        $stmt = $pdo->query("SELECT doc_key, nombre_documento, categoria, file_path, peso_bytes, fecha_update FROM public_documents WHERE doc_key != '' ORDER BY fecha_update DESC");
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($docs as $d) {
            $result[] = [
                'doc_key'    => $d['doc_key'],
                'nombre'     => $d['nombre_documento'],
                'categoria'  => $d['categoria'],
                'url'        => $d['file_path'],
                'peso_bytes' => (int)$d['peso_bytes'],
                'fecha'      => date('d/m/Y', strtotime($d['fecha_update']))
            ];
        }

        echo json_encode([
            'status' => 'ok',
            'data'   => $result
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Legacy format: agrupar por categoría (sin doc_key)
        $stmt = $pdo->query("SELECT nombre_documento, categoria, file_path, peso_bytes, fecha_update FROM public_documents ORDER BY categoria ASC, fecha_update DESC");
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categorias = [];
        foreach ($docs as $d) {
            $cat = $d['categoria'];
            if (!isset($categorias[$cat])) {
                $categorias[$cat] = [];
            }
            $categorias[$cat][] = [
                'nombre'     => $d['nombre_documento'],
                'url'        => $d['file_path'],
                'peso_bytes' => (int)$d['peso_bytes'],
                'fecha'      => date('d/m/Y', strtotime($d['fecha_update']))
            ];
        }

        echo json_encode([
            'status' => 'ok',
            'data'   => $categorias
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Error de conexión con el repositorio de documentos.']);
}
?>
