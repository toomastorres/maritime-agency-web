<?php
/**
 * AMA — vacantes.php
 * API Endpoint: GET /api/vacantes
 *
 * Devuelve vacantes activas (estado = 1) para el frontend.
 */

require_once dirname(__DIR__, 2) . '/core/database.php';

checkRateLimit(60, 60);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=1800');
header('Vary: Accept-Encoding');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método HTTP no permitido.']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, titulo, departamento, ubicacion, descripcion, requisitos, creado_en FROM vacancies WHERE estado = 1 ORDER BY creado_en DESC");
    $vacantesActivas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear salida para no romper el HTML anterior
    $dataOut = [];
    foreach ($vacantesActivas as $v) {
        $dataOut[] = [
            'id' => $v['id'],
            'titulo' => $v['titulo'],
            'departamento' => $v['departamento'],
            'ubicacion' => $v['ubicacion'],
            'descripcion' => $v['descripcion'] . ($v['requisitos'] ? "\n\n**Requisitos:**\n" . $v['requisitos'] : ''),
            'fecha' => date('d/m/Y', strtotime($v['creado_en']))
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'data' => $dataOut
    ], JSON_UNESCAPED_UNICODE);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Error de conexión con la base de talentos.']);
}
?>
