<?php
/**
 * AMA — tipo_cambio.php
 * API Endpoint: GET /api/tipo_cambio
 *
 * Devuelve cotizaciones del día (solo en horario comercial L-V 09:00-17:00).
 */

require_once dirname(__DIR__, 2) . '/core/database.php';

checkRateLimit(60, 60);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Vary: Accept-Encoding');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método HTTP no permitido.']);
    exit;
}

// 1. Validación de Horario Comercial (Lunes a Viernes, 09:00 a 17:00 hs)
$hora_actual = (int)date('H');
$dia_semana = (int)date('N'); // 1 = Lunes, 7 = Domingo

if ($dia_semana >= 6 || $hora_actual < 9 || $hora_actual >= 17) {
    echo json_encode([
        'status' => 'cerrado', 
        'mensaje' => 'El servicio de consulta de tipo de cambio opera de lunes a viernes de 09:00 a 17:00 hs.',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 2. Implementar caché de archivo (TTL: 5 minutos / 300 segundos)
    $cache_file = dirname(__DIR__, 2) . '/data/cache/tipo_cambio.json';
    $cache_ttl = 300;

    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
        $cached_data = file_get_contents($cache_file);
        if ($cached_data) {
            // Indicar mediante header si fue servido desde caché (opcional, útil para debug)
            header('X-Cache: HIT');
            echo $cached_data;
            exit;
        }
    }
    header('X-Cache: MISS');

    // 3. Extraer datos del día EN CURSO de MySQL
    
    // Obtener último USD de hoy
    $stmtUSD = $pdo->query("SELECT valor, creado_en FROM exchange_rates WHERE moneda = 'USD' AND fecha_cotizacion = CURDATE() ORDER BY creado_en DESC LIMIT 1");
    $usd_row = $stmtUSD->fetch();

    if (!$usd_row) {
        // No hay dólar publicado hoy
        echo json_encode([
            'status' => 'no_disponible', 
            'mensaje' => 'Tipo de cambio para el día de la fecha aún no disponible.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $dolar_valor = $usd_row['valor'];
    $ultima_actualizacion = $usd_row['creado_en']; // Usamos la hora a la que se cargó el dólar

    // Obtener último EUR de hoy (opcional)
    $stmtEUR = $pdo->query("SELECT valor FROM exchange_rates WHERE moneda = 'EUR' AND fecha_cotizacion = CURDATE() ORDER BY creado_en DESC LIMIT 1");
    $eur_row = $stmtEUR->fetch();
    $euro_valor = $eur_row ? $eur_row['valor'] : '';

    // Obtener datos bancarios
    $stmtBank = $pdo->query("SELECT contenido_html FROM bank_details WHERE id = 1");
    $bank_row = $stmtBank->fetch();
    $datos_bancarios = $bank_row ? $bank_row['contenido_html'] : '';

    // 4. Responder al frontend con la misma estructura y guardar en caché
    $response_json = json_encode([
        'status' => 'ok',
        'mensaje' => 'Cotización válida.',
        'data' => [
            'dolar_valor' => $dolar_valor,
            'euro_valor' => $euro_valor,
            'datos_bancarios' => $datos_bancarios,
            'ultima_actualizacion' => $ultima_actualizacion
        ]
    ], JSON_UNESCAPED_UNICODE);

    // Guardar caché (silenciosamente fallará si no hay permisos, lo cual está bien en read-only)
    atomicCacheWrite($cache_file, $response_json);

    echo $response_json;

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => 'Error de conexión con la base de datos financiera.']);
}
?>
