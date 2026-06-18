<?php
/**
 * AMA — contactos.php
 * API Endpoint: GET /api/contactos
 *
 * Devuelve el directorio de personal interno alineado al modelo institucional:
 *  - Departamentos en orden jerárquico, cada uno con sus canales de área.
 *  - Contactos ordenados por prioridad de puesto dentro de su departamento.
 *  - Email personal solo para los referentes (es_referente / email_personal).
 *
 * El modelo (orden, canales, referentes, slugs i18n) es compartido con la página
 * pública vía src/logic/contactos_model.php para que nunca se desincronicen.
 */

require_once dirname(__DIR__, 2) . '/core/database.php';
require_once dirname(__DIR__) . '/contactos_model.php';

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
    // 1. Caché de archivo (TTL: 1 hora / 3600 segundos)
    $cache_file = dirname(__DIR__, 2) . '/data/cache/contactos.json';
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

    // 2. Empleados agrupados y ordenados según el modelo institucional
    $rows      = $pdo->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);
    $empleados = contactos_agrupar_y_ordenar($rows);

    $canalesArea = contactos_canales_area();
    $referentes  = contactos_puestos_email_personal();

    $departamentos = [];
    $contactos     = [];

    foreach ($empleados as $dept => $lista) {
        $slug = getDeptKey($dept);

        $departamentos[] = [
            'id'        => $slug,
            'nombre'    => (string)$dept,
            'label_key' => 'departments.' . $slug,         // clave i18n para localizar
            'canales'   => $canalesArea[$dept] ?? [],        // buzones funcionales del área
        ];

        foreach ($lista as $emp) {
            $puesto = (string)($emp['puesto'] ?? '');
            $esRef  = in_array($puesto, $referentes, true);

            $contactos[] = [
                'id'             => (int)$emp['id'],
                'nombre'         => (string)$emp['nombre'],
                'apellido'       => (string)$emp['apellido'],
                'posicion'       => $puesto,
                'posicion_key'   => 'positions.' . getPositionKey($puesto), // clave i18n
                'departamento'   => $slug,
                'email'          => (string)$emp['email'],                  // crudo (compat.)
                'email_personal' => $esRef ? (string)$emp['email'] : null,  // solo referentes
                'es_referente'   => $esRef,
                'telefono'       => (string)$emp['telefono'],
                'jerarquia'      => (int)$emp['jerarquia'],
            ];
        }
    }

    $response_json = json_encode([
        'status' => 'ok',
        'data'   => [
            'departamentos' => $departamentos,
            'contactos'     => $contactos,
        ],
    ], JSON_UNESCAPED_UNICODE);

    atomicCacheWrite($cache_file, $response_json);

    echo $response_json;

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Error de conexión con la base del directorio corporativo.']);
}
?>
