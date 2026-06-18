<?php
/**
 * AMA — bridge.php
 * API Bridge READ-ONLY para consultas externas a la base de datos.
 *
 *  Endpoints:
 *    GET  /schema              → Estructura completa de la BD
 *    GET  /data/{table}        → Primeras 10 filas de una tabla
 *    GET  /data/{table}?departamento=X  → Filtro por departamento (wp_empleados)
 *
 *  Seguridad:
 *    Header  X-Bridge-Token    → Token compartido (definido en .env)
 *
 *  Versión: 1.0 — Marzo 2026
 * ============================================================
 */

// ─── Configuración de errores ──────────────────────────────
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/core/utils.php';

// ─── Carga de .env (compartido) ──────────────

$env = loadEnv(dirname(__DIR__, 2) . '/.env');

// ─── Variables de entorno ──────────────────────────────────
$DB_HOST      = $env['DB_HOST']      ?? 'localhost';
$DB_USER      = $env['DB_USER']      ?? '';
$DB_PASS      = $env['DB_PASS']      ?? '';
$DB_NAME      = $env['DB_NAME']      ?? '';
$BRIDGE_TOKEN = $env['BRIDGE_TOKEN'] ?? '';

// ─── Rate Limiting Bridge ──────────────────────────────────
// Rate limit diferenciado: /schema es mas sensible (5/min), /data normal (30/min)
$bridgeRoute = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (strpos($bridgeRoute, '/schema') !== false) {
    checkRateLimit(5, 60);
} else {
    checkRateLimit(30, 60);
}

// ─── Headers de respuesta ──────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: X-Bridge-Token, Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Utiliza jsonResponse de src/core/utils.php

// ─── Autenticación ─────────────────────────────────────────
function authenticate(string $expectedToken): void
{
    if (empty($expectedToken)) {
        jsonResponse(500, null, 'Bridge token no configurado en el servidor.');
    }

    $headers = getallheaders();
    // Normalizar claves del header (algunos servidores las cambian)
    $normalized = [];
    foreach ($headers as $k => $v) {
        $normalized[strtolower($k)] = $v;
    }

    $provided = $normalized['x-bridge-token'] ?? '';

    if (empty($provided)) {
        jsonResponse(403, null, 'Acceso denegado. Header X-Bridge-Token requerido.');
    }

    if (!hash_equals($expectedToken, $provided)) {
        jsonResponse(403, null, 'Acceso denegado. Token inválido.');
    }
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, null, 'Método no permitido. Solo se acepta GET.');
}

// Autenticar
authenticate($BRIDGE_TOKEN);

// ─── Tablas sensibles: nunca exponibles vía bridge ─────────
// Contienen hashes de contraseña, tokens de reset e IPs de auditoría.
$BLOCKED_TABLES = ['usuarios_admin', 'admin_audit_logs'];

// ─── Conexión a BD ─────────────────────────────────────────
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    jsonResponse(500, null, 'Error de conexión a la base de datos.');
}
$conn->set_charset('utf8mb4');

// ─── Router ────────────────────────────────────────────────
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
// Remover query string para el routing
$path = parse_url($requestUri, PHP_URL_PATH);
// Remover el prefijo del directorio (ej: /api-bridge/)
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$route = '/' . trim(substr($path, strlen($basePath)), '/');

// ─── Endpoint: GET /schema ─────────────────────────────────
if ($route === '/schema' || $route === '/schema/') {

    $tables = [];
    $result = $conn->query('SHOW TABLES');

    if (!$result) {
        jsonResponse(500, null, 'Error al consultar las tablas.');
    }

    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $tableName = $row[0];

        // Omitir tablas sensibles del esquema expuesto
        if (in_array($tableName, $BLOCKED_TABLES, true)) {
            continue;
        }

        $columns   = [];

        $descResult = $conn->query("DESCRIBE `" . $conn->real_escape_string($tableName) . "`");
        if ($descResult) {
            while ($col = $descResult->fetch_assoc()) {
                $columns[] = [
                    'field'   => $col['Field'],
                    'type'    => $col['Type'],
                    'null'    => $col['Null'],
                    'key'     => $col['Key'],
                    'default' => $col['Default'],
                    'extra'   => $col['Extra'],
                ];
            }
            $descResult->free();
        }

        // Contar registros
        $countResult = $conn->query("SELECT COUNT(*) as total FROM `" . $conn->real_escape_string($tableName) . "`");
        $rowCount    = $countResult ? $countResult->fetch_assoc()['total'] : '?';

        $tables[] = [
            'table_name'  => $tableName,
            'columns'     => $columns,
            'row_count'   => (int) $rowCount,
        ];
    }
    $result->free();

    jsonResponse(200, [
        'database' => $DB_NAME,
        'tables'   => $tables,
    ]);
}

// ─── Endpoint: GET /data/{table} ───────────────────────────
if (preg_match('#^/data/([a-zA-Z0-9_]+)/?$#', $route, $matches)) {

    $requestedTable = $matches[1];

    // Rechazar tablas sensibles (hashes de contraseña, auditoría)
    if (in_array($requestedTable, $BLOCKED_TABLES, true)) {
        jsonResponse(403, null, "Acceso denegado: la tabla '{$requestedTable}' no está disponible vía bridge.");
    }

    // Whitelist: obtener tablas existentes para validar
    $allowedTables = [];
    $tablesResult  = $conn->query('SHOW TABLES');
    if ($tablesResult) {
        while ($row = $tablesResult->fetch_array(MYSQLI_NUM)) {
            $allowedTables[] = $row[0];
        }
        $tablesResult->free();
    }

    if (!in_array($requestedTable, $allowedTables, true)) {
        jsonResponse(404, null, "Tabla '{$requestedTable}' no encontrada en la base de datos.");
    }

    // Construir query con límite de seguridad (prepared statement)
    $safeTable = $conn->real_escape_string($requestedTable);
    $limit     = 10;
    $query     = "SELECT * FROM `{$safeTable}`";

    // Filtro opcional por departamento (solo para wp_empleados)
    $wheres = [];
    $params = [];
    $types  = '';
    if ($requestedTable === 'wp_empleados' && isset($_GET['departamento'])) {
        $wheres[] = "`departamento` = ?";
        $params[] = $_GET['departamento'];
        $types   .= 's';
    }

    if (!empty($wheres)) {
        $query .= ' WHERE ' . implode(' AND ', $wheres);
    }

    $query .= " LIMIT {$limit}";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        jsonResponse(500, null, 'Error al consultar la tabla.');
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        jsonResponse(500, null, 'Error al consultar la tabla.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
    $stmt->close();

    jsonResponse(200, [
        'table'      => $requestedTable,
        'limit'      => $limit,
        'count'      => count($rows),
        'rows'       => $rows,
    ]);
}

// ─── 404: Ruta no encontrada ───────────────────────────────
jsonResponse(404, null, "Endpoint no encontrado. Rutas disponibles: GET /schema, GET /data/{table}");

$conn->close();
