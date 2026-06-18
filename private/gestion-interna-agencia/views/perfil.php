<?php
/**
 * AMA — perfil.php (Admin)
 * Mi Perfil: cambiar datos personales y contraseña.
 * Accesible para TODOS los roles autenticados.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once __DIR__ . '/../includes/password_policy.php';

$msgDatos = '';
$errDatos = '';
$msgPass = '';
$errPass = '';

// Obtener datos actuales del usuario
$stmt = $pdo->prepare("SELECT id, nombre, username, email, rol_portal, creado_en, ultimo_login, password_updated_at FROM usuarios_admin WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Error: usuario no encontrado en la base de datos.');
}

$rolesDict = [
    'admin_global'  => 'Administrador Global',
    'caja'          => 'Caja / Finanzas',
    'cs'            => 'Customer Service',
    'rrhh'          => 'Recursos Humanos',
    'documentacion' => 'Documentación'
];

// ── Procesar formularios ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errDatos = 'Token CSRF inválido. Recargue la página e intente nuevamente.';
    } else {
        $formType = $_POST['form_type'] ?? '';

        // ── FORM 1: Actualizar datos personales ──
        if ($formType === 'datos') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($nombre) || strlen($nombre) > 100) {
                $errDatos = 'El nombre es obligatorio y no puede superar los 100 caracteres.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errDatos = 'Ingrese un correo electrónico válido.';
            } else {
                try {
                    $upd = $pdo->prepare("UPDATE usuarios_admin SET nombre = ?, email = ? WHERE id = ?");
                    $upd->execute([$nombre, $email, $_SESSION['user_id']]);

                    // Actualizar sesión para reflejar el cambio inmediatamente
                    $_SESSION['user_name'] = $nombre;
                    $user['nombre'] = $nombre;
                    $user['email'] = $email;

                    // Audit log
                    $logStmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
                    $logStmt->execute([$_SESSION['user_id'], 'profile_update', "Actualizó nombre/email", $_SERVER['REMOTE_ADDR']]);

                    $msgDatos = 'Datos personales actualizados correctamente.';
                } catch (\PDOException $e) {
                    error_log('AMA perfil datos error: ' . $e->getMessage());
                    $errDatos = 'Error al actualizar tus datos. Intente nuevamente.';
                }
            }
        }

        // ── FORM 2: Cambiar contraseña ──
        if ($formType === 'password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
                $errPass = 'Complete todos los campos de contraseña.';
            } elseif ($newPass !== $confirmPass) {
                $errPass = 'La nueva contraseña y la confirmación no coinciden.';
            } elseif (!empty($pwdErrors = validate_password_strength($newPass, $user['username'], $user['email']))) {
                $errPass = $pwdErrors[0];
            } else {
                // Verificar contraseña actual
                $hashStmt = $pdo->prepare("SELECT password_hash FROM usuarios_admin WHERE id = ?");
                $hashStmt->execute([$_SESSION['user_id']]);
                $currentHash = $hashStmt->fetchColumn();

                if (!password_verify($currentPass, $currentHash)) {
                    $errPass = 'La contraseña actual es incorrecta.';
                } else {
                    try {
                        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                        $upd = $pdo->prepare("UPDATE usuarios_admin SET password_hash = ?, password_updated_at = NOW() WHERE id = ?");
                        $upd->execute([$newHash, $_SESSION['user_id']]);

                        // Refrescar dato en vista
                        $user['password_updated_at'] = date('Y-m-d H:i:s');

                        // Audit log
                        $logStmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
                        $logStmt->execute([$_SESSION['user_id'], 'password_change', "Cambió su propia contraseña", $_SERVER['REMOTE_ADDR']]);

                        $msgPass = 'Contraseña actualizada correctamente.';
                    } catch (\PDOException $e) {
                        error_log('AMA perfil password error: ' . $e->getMessage());
                        $errPass = 'Error al cambiar la contraseña. Intente nuevamente.';
                    }
                }
            }
        }
    }
}
?>

<div class="header-actions perfil-header">
    <div>
        <h1 class="perfil-title">Mi Perfil</h1>
        <p class="perfil-subtitle">
            Administrá tus datos personales y contraseña de acceso al portal.
        </p>
    </div>
</div>

<div class="perfil-grid">

    <!-- Card 1: Datos Personales -->
    <div class="admin-card perfil-card">
        <div class="admin-card-header perfil-card-header">
            <span class="material-symbols-outlined">badge</span>
            <h2>Datos Personales</h2>
        </div>

        <?php if ($msgDatos): ?>
            <div class="admin-alert admin-alert-success perfil-alert">
                <span class="material-symbols-outlined perfil-alert-icon">check_circle</span>
                <?php echo htmlspecialchars($msgDatos); ?>
            </div>
        <?php endif; ?>
        <?php if ($errDatos): ?>
            <div class="admin-alert admin-alert-error perfil-alert">
                <span class="material-symbols-outlined perfil-alert-icon">error</span>
                <?php echo htmlspecialchars($errDatos); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="form_type" value="datos">

            <!-- Username (readonly) -->
            <div class="form-group perfil-form-group">
                <label class="form-label" for="perfil_username">Nombre de Usuario</label>
                <input type="text" id="perfil_username" class="admin-input perfil-readonly-input" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
            </div>

            <!-- Rol (readonly) -->
            <div class="form-group perfil-form-group">
                <label class="form-label">Rol en el Sistema</label>
                <div class="perfil-role-box">
                    <span class="badge badge-blue"><?php echo htmlspecialchars($rolesDict[$user['rol_portal']] ?? $user['rol_portal']); ?></span>
                </div>
            </div>

            <!-- Nombre (editable) -->
            <div class="form-group perfil-form-group">
                <label class="form-label" for="perfil_nombre">Nombre Completo</label>
                <input type="text" id="perfil_nombre" name="nombre" class="admin-input" required maxlength="100" value="<?php echo htmlspecialchars($user['nombre']); ?>">
            </div>

            <!-- Email (editable) -->
            <div class="form-group perfil-form-group--last">
                <label class="form-label" for="perfil_email">Correo Electrónico</label>
                <input type="email" id="perfil_email" name="email" class="admin-input" required value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <!-- Info de cuenta -->
            <div class="perfil-info-box">
                <div>
                    <span class="material-symbols-outlined perfil-info-icon">calendar_today</span>
                    Cuenta creada: <strong><?php echo date('d/m/Y', strtotime($user['creado_en'])); ?></strong>
                </div>
                <div>
                    <span class="material-symbols-outlined perfil-info-icon">login</span>
                    Último ingreso: <strong><?php echo $user['ultimo_login'] ? date('d/m/Y H:i', strtotime($user['ultimo_login'])) : 'No registra'; ?></strong>
                </div>
            </div>

            <button type="submit" class="btn-admin-primary btn-admin-full">
                <span class="material-symbols-outlined">save</span>
                Guardar Cambios
            </button>
        </form>
    </div>

    <!-- Card 2: Cambiar Contraseña -->
    <div class="admin-card perfil-card">
        <div class="admin-card-header perfil-card-header">
            <span class="material-symbols-outlined">lock</span>
            <h2>Cambiar Contraseña</h2>
        </div>

        <?php if ($msgPass): ?>
            <div class="admin-alert admin-alert-success perfil-alert">
                <span class="material-symbols-outlined perfil-alert-icon">check_circle</span>
                <?php echo htmlspecialchars($msgPass); ?>
            </div>
        <?php endif; ?>
        <?php if ($errPass): ?>
            <div class="admin-alert admin-alert-error perfil-alert">
                <span class="material-symbols-outlined perfil-alert-icon">error</span>
                <?php echo htmlspecialchars($errPass); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="form_type" value="password">

            <div class="form-group perfil-form-group">
                <label class="form-label" for="current_password">Contraseña Actual</label>
                <input type="password" id="current_password" name="current_password" class="admin-input" required autocomplete="current-password">
            </div>

            <div class="form-group perfil-form-group">
                <label class="form-label" for="new_password">Nueva Contraseña</label>
                <input type="password" id="new_password" name="new_password" class="admin-input" required minlength="<?php echo AMA_PASSWORD_MIN_LENGTH; ?>" autocomplete="new-password" data-pw-meter>
                <p class="perfil-hint"><?php echo htmlspecialchars(password_policy_help_text()); ?></p>
            </div>

            <div class="form-group perfil-form-group--last">
                <label class="form-label" for="confirm_password">Confirmar Nueva Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" class="admin-input" required minlength="<?php echo AMA_PASSWORD_MIN_LENGTH; ?>" autocomplete="new-password">
            </div>

            <!-- Info de último cambio -->
            <div class="perfil-info-box">
                <div>
                    <span class="material-symbols-outlined perfil-info-icon">key</span>
                    Último cambio de clave: <strong><?php echo $user['password_updated_at'] ? date('d/m/Y H:i', strtotime($user['password_updated_at'])) : 'No registra'; ?></strong>
                </div>
            </div>

            <button type="submit" class="btn-admin-primary btn-admin-full btn-admin-danger">
                <span class="material-symbols-outlined">lock_reset</span>
                Actualizar Contraseña
            </button>
        </form>
    </div>

</div>
<script src="../../public/assets/js/admin-password.js" defer></script>
