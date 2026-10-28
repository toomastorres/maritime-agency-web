<?php
/**
 * AMA — descargar-cv.php (Admin)
 * Descarga autenticada del CV de un postulante.
 *
 * Los CV viven en public/uploads/cv/, que es un directorio web. Hasta ahora
 * cualquiera con la URL podía bajarse el CV de un candidato —nombre, teléfono,
 * historia laboral— sin pasar por el portal. El nombre de archivo lleva 8 bytes
 * aleatorios, pero eso es oscuridad, no control de acceso, sobre datos
 * personales que la propia política de privacidad del sitio declara protegidos
 * por la Ley 25.326.
 *
 * Ahora el directorio está bloqueado por .htaccess y este endpoint es la única
 * vía: exige sesión, exige rol de RRHH, recibe el ID del postulante (nunca una
 * ruta del cliente) y registra cada descarga en la auditoría.
 */

require_once 'includes/auth.php';
require_login();

if (!has_role('rrhh') && !has_role('admin_global')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

require_once 'includes/db.php';

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Solicitud inválida.');
}

try {
    $stmt = $pdo->prepare("SELECT nombre, cv_path FROM applicants WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
} catch (\PDOException $e) {
    error_log('descargar-cv: ' . $e->getMessage());
    http_response_code(500);
    exit('Error al recuperar el archivo.');
}

if (!$row) {
    http_response_code(404);
    exit('Postulante no encontrado.');
}

// La ruta guardada es relativa a public/ (p. ej. /uploads/cv/2026...pdf).
$baseDir = realpath(dirname(__DIR__, 2) . '/public/uploads/cv');
$file    = realpath(dirname(__DIR__, 2) . '/public' . $row['cv_path']);

// Defensa en profundidad: aunque cv_path lo escribe el servidor y no el
// cliente, se verifica que el archivo resuelto caiga dentro del directorio
// de CV antes de mandarlo.
if ($baseDir === false || $file === false || strpos($file, $baseDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
    http_response_code(404);
    exit('El archivo ya no está disponible.');
}

// Auditoría: quién se bajó el CV de quién y cuándo.
try {
    $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
        ->execute([
            $_SESSION['user_id'] ?? null,
            'cv_download',
            'CV descargado: ' . $row['nombre'] . ' (postulante #' . $id . ')',
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
} catch (\PDOException $e) { /* la auditoría no debe impedir la descarga */ }

// Nombre legible para quien lo baja, en vez del hash del servidor.
$ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'pdf';
$slug  = preg_replace('/[^A-Za-z0-9]+/', '_', $row['nombre']);
$slug  = trim($slug, '_') ?: 'postulante';
$nombreDescarga = 'CV_' . $slug . '.' . $ext;

$mimes = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

while (ob_get_level() > 0) { ob_end_clean(); }

header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($file));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file);
exit;
