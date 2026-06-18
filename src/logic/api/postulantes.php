<?php
/**
 * AMA — postulantes.php
 * API Endpoint: POST /api/postulantes
 *
 * Recibe postulaciones espontáneas con CV adjunto.
 */

require_once dirname(__DIR__, 2) . '/core/database.php';

// POST endpoints: límite estricto (5 req/min)
checkRateLimit(5, 60);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Método no permitido.']);
    exit;
}

// ── Anti-bot: Honeypot ──────────────────────────────────────
if (!empty($_POST['website_url'])) {
    // Bot detectado — responder como si todo OK para no alertar
    echo json_encode(['status' => 'ok', 'message' => 'Postulación recibida.']);
    exit;
}

// ── Recoger campos del FormData ──
$nombre   = trim($_POST['nombre']   ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email    = trim($_POST['email']    ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$area     = trim($_POST['area']     ?? '');

// ── Validaciones ──
$errors = [];

if ($nombre === '' || mb_strlen($nombre) < 2) {
    $errors[] = 'El nombre es obligatorio (mínimo 2 caracteres).';
}
if ($apellido === '' || mb_strlen($apellido) < 2) {
    $errors[] = 'El apellido es obligatorio (mínimo 2 caracteres).';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ingrese un email válido.';
}

// ── Validar archivo CV ──
if (empty($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Debe adjuntar su CV (PDF, DOC o DOCX).';
} else {
    $cv_tmp  = $_FILES['cv']['tmp_name'];
    $cv_name = $_FILES['cv']['name'];
    $cv_size = $_FILES['cv']['size'];

    // Verificar MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $cv_mime = $finfo->file($cv_tmp);
    $allowed_mimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    // Whitelist de extensión (además del MIME) — evita polyglots .php con cabecera PDF
    $cv_ext_check = strtolower(pathinfo($cv_name, PATHINFO_EXTENSION));
    $allowed_exts = ['pdf', 'doc', 'docx'];

    if (!in_array($cv_mime, $allowed_mimes) || !in_array($cv_ext_check, $allowed_exts)) {
        $errors[] = 'Formato de archivo no permitido. Use PDF, DOC o DOCX.';
    }
    if ($cv_size > 5 * 1024 * 1024) {
        $errors[] = 'El archivo supera los 5 MB permitidos.';
    }
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'errors' => $errors]);
    exit;
}

// ── Subir archivo ──
$upload_dir = dirname(__DIR__, 3) . '/public/uploads/cv';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

$ext = strtolower(pathinfo($cv_name, PATHINFO_EXTENSION)) ?: 'pdf';
$slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(mb_substr($apellido . '-' . $nombre, 0, 60)));
$cv_filename = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '_' . $slug . '.' . $ext;
$cv_dest = $upload_dir . '/' . $cv_filename;

if (!move_uploaded_file($cv_tmp, $cv_dest)) {
    error_log("AMA CV upload failed: source=$cv_tmp dest=$cv_dest");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Error al guardar el archivo. Intente nuevamente.']);
    exit;
}

$cv_path = '/uploads/cv/' . $cv_filename;

// ── Insertar en base de datos ──
try {
    $nombre_completo = mb_substr($nombre . ' ' . $apellido, 0, 150);

    $stmt = $pdo->prepare(
        "INSERT INTO applicants (vacancy_id, nombre, email, telefono, area, cv_path, mensaje)
         VALUES (NULL, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $nombre_completo,
        mb_substr($email, 0, 150),
        $telefono ? mb_substr($telefono, 0, 50) : null,
        $area ?: null,
        $cv_path,
        null, // mensaje (no hay campo de mensaje en el form espontáneo)
    ]);

    // Send Internal Email Notification
    try {
        require_once dirname(__DIR__, 2) . '/config/config.php';
        require_once dirname(__DIR__, 2) . '/core/email.php';

        $emailService = new EmailService();
        $subject = "Nueva Postulación Espontánea - {$nombre_completo}";
        $htmlBody = EmailService::buildHtmlTemplate($subject, "
            <p>Se ha recibido un nuevo CV a través del portal web.</p>
            <p><strong>Nombre:</strong> {$nombre_completo}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Teléfono:</strong> " . ($telefono ?: 'No especificado') . "</p>
            <p><strong>Área de interés:</strong> " . ($area ?: 'No especificada') . "</p>
            <p><em>El archivo CV ha sido subido con éxito y está disponible en el servidor ({$cv_filename}).</em></p>
        ");
        $emailService->sendNotification('rrhh', $subject, $htmlBody);
    } catch (\Exception $e) {
        error_log("Email error (postulantes): " . $e->getMessage());
    }

    echo json_encode(['status' => 'ok', 'message' => 'CV recibido correctamente.']);

} catch (\PDOException $e) {
    error_log("AMA postulantes DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Error al registrar la postulación.']);
}
