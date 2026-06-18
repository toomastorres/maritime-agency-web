<?php
/**
 * AMA — cotizacion.php
 * API Endpoint: POST /api/cotizacion
 *
 * Recibe solicitudes de cotización (leads) desde el formulario de servicios.
 */

require_once dirname(__DIR__, 2) . '/core/database.php';

// POST endpoints: límite más estricto (10 req/min)
checkRateLimit(10, 60);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true, 10);
if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'JSON inválido.']);
    exit;
}

// Anti-bot: honeypot
if (!empty($input['website_url']) || !empty($input['url'])) {
    echo json_encode(['status' => 'ok', 'message' => 'Cotización recibida.']);
    exit;
}

$servicio = trim($input['servicio'] ?? '');
$nombre   = htmlspecialchars(strip_tags(trim($input['nombre'] ?? '')), ENT_QUOTES, 'UTF-8');
$empresa  = htmlspecialchars(strip_tags(trim($input['empresa'] ?? '')), ENT_QUOTES, 'UTF-8');
$email    = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$telefono = htmlspecialchars(strip_tags(trim($input['telefono'] ?? '')), ENT_QUOTES, 'UTF-8');
$mensaje  = htmlspecialchars(strip_tags(trim($input['mensaje'] ?? '')), ENT_QUOTES, 'UTF-8');

// Validación
$valid_services = ['roro', 'proyecto', 'contenedores'];
$errors = [];

if (!in_array($servicio, $valid_services)) {
    $errors[] = 'Servicio inválido.';
}
if ($nombre === '' || mb_strlen($nombre) < 2) {
    $errors[] = 'El nombre es obligatorio.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ingrese un email válido.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'errors' => $errors]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO cotizacion_leads (servicio, nombre, empresa, email, telefono, mensaje)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $servicio,
        mb_substr($nombre, 0, 150),
        $empresa ? mb_substr($empresa, 0, 200) : null,
        mb_substr($email, 0, 255),
        $telefono ? mb_substr($telefono, 0, 50) : null,
        $mensaje ?: null,
    ]);

    // Send Internal Email Notification
    try {
        require_once dirname(__DIR__, 2) . '/config/config.php';
        require_once dirname(__DIR__, 2) . '/core/email.php';

        $emailService = new EmailService();

        // Mapear servicio → canal comercial
        $channelMap = [
            'roro'          => 'comercial_autos',
            'proyecto'      => 'comercial_proyecto',
            'contenedores'  => 'comercial_containers',
        ];
        $channel = $channelMap[$servicio] ?? 'comercial_autos';

        $subject = "Nueva Solicitud de Cotizacion - " . strtoupper($servicio);
        $htmlBody = EmailService::buildHtmlTemplate($subject, "
            <p><strong>Servicio:</strong> {$servicio}</p>
            <p><strong>Nombre:</strong> {$nombre}</p>
            <p><strong>Empresa:</strong> " . ($empresa ?: 'No especificada') . "</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Telefono:</strong> " . ($telefono ?: 'No especificado') . "</p>
            <p><strong>Mensaje:</strong></p>
            <blockquote>" . nl2br(htmlspecialchars($mensaje)) . "</blockquote>
        ");
        $emailService->sendNotification($channel, $subject, $htmlBody);
    } catch (\Exception $e) {
        error_log("Email error (cotizacion): " . $e->getMessage());
    }

    echo json_encode(['status' => 'ok', 'message' => 'Solicitud recibida correctamente.']);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Error al guardar la solicitud.']);
}
