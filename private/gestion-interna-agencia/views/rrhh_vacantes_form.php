<?php
/**
 * AMA — rrhh_vacantes_form.php (Admin)
 * Formulario de alta/edición de vacantes.
 */
if (!function_exists('is_logged_in')) exit;
if (!isset($pdo)) require_once dirname(__DIR__) . '/includes/db.php';

$id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$vacante = [
    'titulo' => '',
    'departamento' => '',
    'ubicacion' => '',
    'descripcion' => '',
    'requisitos' => '',
    'estado' => 1
];
$accion = 'Crear';

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM vacancies WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $vacante = $row;
            $accion = 'Editar';
        } else {
            $_SESSION['msj'] = 'Error: Vacante no encontrada.';
            admin_redirect("dashboard.php?section=rrhh_vacantes");
        }
    } catch (\PDOException $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF inválido.";
    } else {
        $titulo = trim($_POST['titulo']);
        $departamento = trim($_POST['departamento']);
        $ubicacion = trim($_POST['ubicacion']);
        $descripcion = trim($_POST['descripcion']);
        $requisitos = trim($_POST['requisitos']);
        $estado = isset($_POST['estado']) ? 1 : 0;

        // Validación de longitud máxima
        if (strlen($titulo) > 200) {
            $error = "El título excede la longitud máxima (200 caracteres).";
        } elseif (strlen($departamento) > 100) {
            $error = "El departamento excede la longitud máxima (100 caracteres).";
        } elseif (strlen($ubicacion) > 200) {
            $error = "La ubicación excede la longitud máxima (200 caracteres).";
        } elseif (strlen($descripcion) > 5000 || strlen($requisitos) > 5000) {
            $error = "La descripción o requisitos exceden la longitud máxima (5000 caracteres).";
        }

        if (!empty($error)) {
            // Skip DB operation
        } else try {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE vacancies SET titulo=?, departamento=?, ubicacion=?, descripcion=?, requisitos=?, estado=? WHERE id=?");
                $stmt->execute([$titulo, $departamento, $ubicacion, $descripcion, $requisitos, $estado, $id]);
                $_SESSION['msj'] = 'Vacante actualizada con éxito.';
                $auditAccion = 'vacante_update';
                $auditDetalle = "Modificada: {$titulo} | Depto: {$departamento} | Estado: " . ($estado ? 'Abierta' : 'Cerrada');
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO vacancies (titulo, departamento, ubicacion, descripcion, requisitos, estado) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $departamento, $ubicacion, $descripcion, $requisitos, $estado]);
                $_SESSION['msj'] = 'Nueva vacante publicada con éxito.';
                $auditAccion = 'vacante_create';
                $auditDetalle = "Creada: {$titulo} | Depto: {$departamento} | Estado: " . ($estado ? 'Abierta' : 'Cerrada');
            }

            // Audit log
            try {
                $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                    ->execute([$_SESSION['user_id'], $auditAccion, $auditDetalle, $_SERVER['REMOTE_ADDR']]);
            } catch (\PDOException $e) { /* audit no-crítico */ }

            // Send Internal Email Notification
            try {
                require_once dirname(__DIR__, 3) . '/src/config/config.php';
                require_once dirname(__DIR__, 3) . '/src/core/email.php';
                $emailService = new EmailService();
                $actionWord = ($id > 0) ? "Modificada" : "Creada";
                $estadoTxt = $estado ? "Publicada" : "Borrador";
                $subject = "Portal RRHH: Vacante {$actionWord}";
                $htmlBody = EmailService::buildHtmlTemplate($subject, "
                    <p>Se ha <strong>{$actionWord}</strong> una vacante en el portal de recursos humanos.</p>
                    <ul>
                        <li><strong>Título:</strong> {$titulo}</li>
                        <li><strong>Departamento:</strong> {$departamento}</li>
                        <li><strong>Ubicación:</strong> {$ubicacion}</li>
                        <li><strong>Estado:</strong> {$estadoTxt}</li>
                    </ul>
                ");
                $emailService->sendNotification('rrhh', $subject, $htmlBody);
            } catch (\Exception $e) {
                error_log("Email error (vacantes): " . $e->getMessage());
            }

            admin_redirect("dashboard.php?section=rrhh_vacantes");
        } catch (\PDOException $e) {
            $error = "Error al guardar en la base de datos.";
        }
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title"><?php echo $isEdit ? 'Editar vacante' : 'Nueva vacante'; ?></h1>
        <p class="admin-page-sub">Se publica en la sección Institucional del sitio cuando está activa.</p>
    </div>
    <a href="?section=rrhh_vacantes" class="btn-admin-secondary">
        <span class="material-symbols-outlined">arrow_back</span> Volver al listado
    </a>
</div>

<div class="admin-card admin-card--form">
    <div class="admin-card-header">
        <h3><span class="material-symbols-outlined">work</span> <?php echo $accion; ?> Vacante</h3>
        <a href="?section=rrhh_vacantes" class="btn-admin-secondary" style="font-size:0.85rem; padding:0.4rem 0.75rem;">
            <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span> Volver
        </a>
    </div>

    <div class="admin-card-body">
        <?php if (isset($error)): ?>
            <div class="admin-alert admin-alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Título de la Búsqueda <span style="color:#dc2626">*</span></label>
                    <input type="text" name="titulo" value="<?php echo htmlspecialchars($vacante['titulo']); ?>" required class="admin-input" placeholder="Ej: Analista de Comercio Exterior">
                </div>
                <div class="form-group">
                    <label class="form-label">Departamento <span style="color:#dc2626">*</span></label>
                    <input type="text" name="departamento" value="<?php echo htmlspecialchars($vacante['departamento']); ?>" required class="admin-input" placeholder="Ej: Operaciones">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Ubicación (Ej. CABA, Retiro) <span style="color:#dc2626">*</span></label>
                <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($vacante['ubicacion']); ?>" required class="admin-input">
            </div>

            <div class="form-group">
                <label class="form-label">Descripción General del Puesto <span style="color:#dc2626">*</span></label>
                <textarea name="descripcion" required rows="5" class="admin-textarea"><?php echo htmlspecialchars($vacante['descripcion']); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Requisitos Excluyentes (Opcional)</label>
                <textarea name="requisitos" rows="3" class="admin-textarea"><?php echo htmlspecialchars($vacante['requisitos']); ?></textarea>
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1.5rem;">
                <input type="checkbox" id="estado" name="estado" value="1" <?php echo $vacante['estado'] ? 'checked' : ''; ?> style="width:1.1rem; height:1.1rem; accent-color:var(--admin-gold);">
                <label for="estado" style="font-weight:600; cursor:pointer; margin:0;">Publicar inmediatamente (Visible en la web)</label>
            </div>

            <button type="submit" class="btn-admin-primary">
                <span class="material-symbols-outlined">save</span>
                <?php echo $accion === 'Crear' ? 'Crear Vacante' : 'Guardar Cambios'; ?>
            </button>
        </form>
    </div>
</div>
