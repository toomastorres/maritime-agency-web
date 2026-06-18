<?php
/**
 * AMA — rrhh_empleados_form.php (Admin)
 * Formulario de alta/edición de empleados.
 */
if (!function_exists('is_logged_in')) exit;

$id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$empleado = [
    'nombre' => '',
    'apellido' => '',
    'puesto' => '',
    'departamento' => '',
    'email' => '',
    'telefono' => '',
    'jerarquia' => 10
];
$accion = 'Alta';

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $empleado = $row;
            $accion = 'Modificar';
        } else {
            $_SESSION['msj'] = 'Error: Empleado no encontrado.';
            header("Location: dashboard.php?section=rrhh&action=empleados");
            exit;
        }
    } catch (\PDOException $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF inválido.";
    } else {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $puesto = trim($_POST['puesto']);
        $departamento = trim($_POST['departamento']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $jerarquia = (int)$_POST['jerarquia'];

        // Validación de longitud máxima
        if (strlen($nombre) > 150 || strlen($apellido) > 150) {
            $error = "Nombre o apellido exceden la longitud máxima (150 caracteres).";
        } elseif (strlen($puesto) > 200) {
            $error = "El puesto excede la longitud máxima (200 caracteres).";
        } elseif (strlen($departamento) > 100) {
            $error = "El departamento excede la longitud máxima (100 caracteres).";
        } elseif (strlen($email) > 150 || ($email && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $error = "El email ingresado no es válido.";
        } elseif (strlen($telefono) > 50) {
            $error = "El teléfono excede la longitud máxima (50 caracteres).";
        }

        if (!empty($error)) {
            // Skip DB operation
        } else try {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE employees SET nombre=?, apellido=?, puesto=?, departamento=?, email=?, telefono=?, jerarquia=? WHERE id=?");
                $stmt->execute([$nombre, $apellido, $puesto, $departamento, $email, $telefono, $jerarquia, $id]);
                $_SESSION['msj'] = 'Datos del empleado actualizados en la estructura pública.';
                $auditAccion = 'empleado_update';
                $auditDetalle = "Modificado: {$apellido}, {$nombre} | Puesto: {$puesto} | Depto: {$departamento}";
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO employees (nombre, apellido, puesto, departamento, email, telefono, jerarquia) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $apellido, $puesto, $departamento, $email, $telefono, $jerarquia]);
                $_SESSION['msj'] = 'Directorio actualizado. El empleado ya aparece en la web pública.';
                $auditAccion = 'empleado_create';
                $auditDetalle = "Alta: {$apellido}, {$nombre} | Puesto: {$puesto} | Depto: {$departamento}";
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
                $actionWord = ($id > 0) ? "Modificado" : "Agregado";
                $subject = "Portal RRHH: Empleado {$actionWord}";
                $htmlBody = EmailService::buildHtmlTemplate($subject, "
                    <p>Se ha <strong>{$actionWord}</strong> un empleado en el directorio oficial.</p>
                    <ul>
                        <li><strong>Nombre:</strong> {$nombre} {$apellido}</li>
                        <li><strong>Puesto:</strong> {$puesto}</li>
                        <li><strong>Departamento:</strong> {$departamento}</li>
                        <li><strong>Email:</strong> {$email}</li>
                    </ul>
                ");
                $emailService->sendNotification('rrhh', $subject, $htmlBody);
            } catch (\Exception $e) {
                error_log("Email error (empleados): " . $e->getMessage());
            }

            header("Location: dashboard.php?section=rrhh&action=empleados");
            exit;
        } catch (\PDOException $e) {
            $error = "Error al guardar en la base de datos.";
        }
    }
}
?>

<div class="admin-card" style="max-width: 800px; margin: 0 auto;">
    <div class="admin-card-header">
        <h3><span class="material-symbols-outlined">badge</span> <?php echo $accion; ?> Empleado (Staff)</h3>
        <a href="?section=rrhh&action=empleados" class="btn-admin-secondary" style="font-size:0.85rem; padding:0.4rem 0.75rem;">
            <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span> Volver
        </a>
    </div>

    <div class="admin-card-body">
        <?php if (isset($error)): ?>
            <div class="admin-alert admin-alert-error" style="margin-bottom:1rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Nombre/s <span style="color:#dc2626">*</span></label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($empleado['nombre']); ?>" required class="admin-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Apellido/s <span style="color:#dc2626">*</span></label>
                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($empleado['apellido']); ?>" required class="admin-input">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Puesto Administrativo <span style="color:#dc2626">*</span></label>
                    <input type="text" name="puesto" value="<?php echo htmlspecialchars($empleado['puesto']); ?>" required class="admin-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Departamento <span style="color:#dc2626">*</span></label>
                    <select name="departamento" required class="admin-select">
                        <?php
                            $deptos = ['gerencia-general' => 'Gerencia General', 'comercial' => 'Comercial', 'exportacion' => 'Exportación', 'importacion' => 'Importación', 'operaciones' => 'Operaciones', 'administracion' => 'Administración / Legales', 'recepcion' => 'Recepción'];
                            foreach ($deptos as $slug => $nom) {
                                $sel = ($empleado['departamento'] == $slug) ? 'selected' : '';
                                echo "<option value=\"$slug\" $sel>$nom</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Teléfono Web (Conmutador/Interno)</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($empleado['telefono']); ?>" placeholder="Ej: +54 11 4310-4400" class="admin-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Corporativo <span style="color:#dc2626">*</span></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($empleado['email']); ?>" required class="admin-input">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Orden de Jerarquía Visual</label>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <input type="number" name="jerarquia" value="<?php echo htmlspecialchars($empleado['jerarquia']); ?>" required class="admin-input" style="width:120px;">
                    <span style="font-size:0.85rem; color:var(--admin-text-muted);">Menor número aparece primero en la página de Contactos (Ej: 1 = Gral. Manager, 10 = Empleado base).</span>
                </div>
            </div>

            <button type="submit" class="btn-admin-primary">
                <span class="material-symbols-outlined">save</span>
                <?php echo $accion === 'Alta' ? 'Dar de Alta' : 'Guardar Cambios'; ?>
            </button>
        </form>
    </div>
</div>
