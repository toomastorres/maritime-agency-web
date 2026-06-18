<?php
/**
 * AMA — usuarios_form.php (Admin)
 * Creación y Edición de Usuarios Administrativos
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_role('admin_global');
require_once dirname(__DIR__, 3) . '/src/core/database.php';
require_once dirname(__DIR__, 3) . '/src/core/email.php';
require_once __DIR__ . '/../includes/password_policy.php';

$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$user = [
    'nombre' => '',
    'username' => '',
    'email' => '',
    'rol_portal' => 'cs'
];

if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT id, nombre, username, email, rol_portal FROM usuarios_admin WHERE id = ?");
        $stmt->execute([$id]);
        $fetched = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetched) {
            $user = $fetched;
        } else {
            die("Usuario no encontrado.");
        }
    } catch (\PDOException $e) {
        error_log("AMA usuarios_form fetch error: " . $e->getMessage());
        die("Error de base de datos.");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token inválido. Intente de nuevo.";
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = $_POST['rol_portal'] ?? '';
        $forced_password = $_POST['forced_password'] ?? '';

        $validRoles = ['admin_global', 'caja', 'cs', 'rrhh', 'documentacion'];

        if (!$nombre || !$username || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Por favor, complete correctamente el nombre, usuario y correo electrónico.";
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            $error = "El Nombre de Usuario solo puede contener caracteres alfanuméricos, puntos y guiones.";
        } elseif (!in_array($rol, $validRoles)) {
            $error = "Rol seleccionado es inválido.";
        } else {
            try {
                // Check si username ya existe en otro usuario
                $stmtCheck = $pdo->prepare("SELECT id FROM usuarios_admin WHERE username = ? AND id != ?");
                $stmtCheck->execute([$username, $id]);
                if ($stmtCheck->fetch()) {
                    $error = "El Nombre de Usuario ya está registrado en otra cuenta.";
                } else {
                    if ($isEdit) {
                        // UPDATE
                        if (!empty($forced_password)) {
                            $pwdErrors = validate_password_strength($forced_password, $username, $email);
                            if (!empty($pwdErrors)) {
                                $error = $pwdErrors[0];
                            } else {
                                $hash = password_hash($forced_password, PASSWORD_DEFAULT);
                                $stmtUpdate = $pdo->prepare("UPDATE usuarios_admin SET nombre = ?, username = ?, email = ?, rol_portal = ?, password_hash = ?, reset_token = NULL, reset_expires = NULL, password_updated_at = NOW() WHERE id = ?");
                                $successUp = $stmtUpdate->execute([$nombre, $username, $email, $rol, $hash, $id]);
                                $auditMsg = "Editó usuario ID $id y Forzó Contraseña Manual";
                            }
                        } else {
                            $stmtUpdate = $pdo->prepare("UPDATE usuarios_admin SET nombre = ?, username = ?, email = ?, rol_portal = ? WHERE id = ?");
                            $successUp = $stmtUpdate->execute([$nombre, $username, $email, $rol, $id]);
                            $auditMsg = "Editó usuario ID $id";
                        }
                        
                        if (empty($error) && !empty($successUp)) {
                            $logStmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
                            $logStmt->execute([$_SESSION['user_id'], 'user_edit', $auditMsg, $_SERVER['REMOTE_ADDR']]);
                            header('Location: ?section=usuarios');
                            exit;
                        } elseif (empty($error)) {
                            $error = "Error al actualizar el usuario en base de datos.";
                        }
                    } else {
                        // CREATE
                        if (!empty($forced_password)) {
                            $pwdErrors = validate_password_strength($forced_password, $username, $email);
                            if (!empty($pwdErrors)) {
                                $error = $pwdErrors[0];
                            }
                        }
                        if (empty($error)) {
                            if (!empty($forced_password)) {
                                $hash = password_hash($forced_password, PASSWORD_DEFAULT);
                                $token = NULL;
                                $tokenHash = NULL;
                                $expires = NULL;
                            } else {
                                $dummyPass = bin2hex(random_bytes(16));
                                $hash = password_hash($dummyPass, PASSWORD_DEFAULT);
                                $token = bin2hex(random_bytes(32));
                                $tokenHash = hash('sha256', $token);
                                $expires = date('Y-m-d H:i:s', time() + (3600 * 24 * 3)); // 3 días para configurarla
                            }

                            $stmtInsert = $pdo->prepare("INSERT INTO usuarios_admin (nombre, username, email, password_hash, rol_portal, reset_token, reset_expires) VALUES (?, ?, ?, ?, ?, ?, ?)");

                            if ($stmtInsert->execute([$nombre, $username, $email, $hash, $rol, $tokenHash, $expires])) {
                                $newId = $pdo->lastInsertId();

                                if (empty($forced_password)) {
                                    // Enviar mail de bienvenida c/ token
                                    $resetLink = BASE_URL . "private/gestion-interna-agencia/reset_password.php?token=" . urlencode($token);
                                    $subject = "🌟 Bienvenido al Portal Interno de AMA Agencies";
                                    $htmlBody = EmailService::buildHtmlTemplate("Configuración de Cuenta", "
                                        <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                                        <p>El administrador <strong>{$_SESSION['user_name']}</strong> ha creado tu cuenta en el Sistema de Gestión Interna de AMA Agencies.</p>
                                        <p>Tu rol asignado es: <strong>" . strtoupper($rol) . "</strong></p>
                                        <p>Tu Nombre de Usuario para Iniciar Sesión es: <strong style='font-size: 1.2em; color: #0033a0;'>" . htmlspecialchars($username) . "</strong></p>
                                        <p>Para completar tu registro, necesitas ingresar una contraseña que usarás para entrar al sistema. Hacé clic en el botón de abajo para configurarla:</p>
                                        <p style='text-align: center; margin: 30px 0;'>
                                            <a href='{$resetLink}' style='background-color: #0033a0; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; font-family: Arial, sans-serif; display: inline-block;'>
                                                Establecer mi contraseña
                                            </a>
                                        </p>
                                        <p style='font-size: 0.85em; color: #888;'>Este enlace de configuración caducará en 3 días.</p>
                                    ");

                                    $emailSvc = new EmailService();
                                    $mailEnviado = $emailSvc->send($subject, $htmlBody, $email);
                                    $detalles = "Creado usuario ID $newId. Mail de invitación enviado: " . ($mailEnviado ? 'Sí' : 'No');
                                } else {
                                    $detalles = "Creado usuario ID $newId con CLAVE MANUAL. No se envió email.";
                                }

                                $logStmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
                                $logStmt->execute([$_SESSION['user_id'], 'user_create', $detalles, $_SERVER['REMOTE_ADDR']]);
                                
                                $successTitle = urlencode("Usuario Procesado");
                                header("Location: ?section=usuarios&msg={$successTitle}");
                                exit;
                            } else {
                                $error = "Error al intentar crear el usuario en la BD.";
                            }
                        }
                    }
                }
            } catch (\PDOException $e) {
                error_log("AMA usuarios_form error: " . $e->getMessage());
                $error = "Error interno de base de datos.";
            }
        }
    }
}
?>

<div class="header-actions" style="margin-bottom: 2rem;">
    <div>
        <h1 style="margin:0; font-size:1.5rem; color:var(--admin-text);"><?php echo $isEdit ? 'Editar Usuario' : 'Nuevo Usuario / Enviar Invitación'; ?></h1>
        <p style="margin: 0.2rem 0 0; color:var(--admin-text-muted); font-size: 0.9rem;">
            <?php if ($isEdit): ?>
                Modificar los datos de la cuenta de un compañero.
            <?php else: ?>
                Crear una cuenta y generar un enlace de primer acceso por correo automático.
            <?php endif; ?>
        </p>
    </div>
    <a href="?section=usuarios" class="btn-admin-secondary">
        <span class="material-symbols-outlined">arrow_back</span>
        Volver
    </a>
</div>

<?php if ($error): ?>
    <div class="admin-alert admin-alert-error"><span class="material-symbols-outlined">error</span> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="admin-card" style="max-width: 600px; padding: 2rem;">
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--admin-text);">Nombre Completo</label>
            <input type="text" name="nombre" required value="<?php echo htmlspecialchars($user['nombre']); ?>" style="width:100%; padding:0.75rem; border:1px solid var(--admin-border-light); border-radius:6px; box-sizing:border-box;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--admin-text);">Nombre de Usuario (Para Iniciar Sesión)</label>
            <input type="text" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="ej: jperez o of.caja" style="width:100%; padding:0.75rem; border:1px solid var(--admin-border-light); border-radius:6px; box-sizing:border-box;" title="Solo letras, números, puntos y guiones">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--admin-text);">Correo Electrónico (Envío de Invitación/Recuperación)</label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>" style="width:100%; padding:0.75rem; border:1px solid var(--admin-border-light); border-radius:6px; box-sizing:border-box;">
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--admin-text);">Rol en el Sistema</label>
            <p style="font-size:0.85rem; color:var(--admin-text-muted); margin-top:0; margin-bottom:0.5rem;">Define qué secciones del menú lateral están disponibles para ver y modificar.</p>
            <select name="rol_portal" required style="width:100%; padding:0.75rem; border:1px solid var(--admin-border-light); border-radius:6px; background:#fff; box-sizing:border-box;">
                <option value="cs" <?php echo $user['rol_portal'] === 'cs' ? 'selected' : ''; ?>>Customer Service (Schedules / Rutas)</option>
                <option value="caja" <?php echo $user['rol_portal'] === 'caja' ? 'selected' : ''; ?>>Caja / Finanzas (Tipo de Cambio)</option>
                <option value="rrhh" <?php echo $user['rol_portal'] === 'rrhh' ? 'selected' : ''; ?>>Recursos Humanos (Vacantes, Empleados)</option>
                <option value="documentacion" <?php echo $user['rol_portal'] === 'documentacion' ? 'selected' : ''; ?>>Documentación Oficial (Descargas de Sistema)</option>
                <hr>
                <option value="admin_global" <?php echo $user['rol_portal'] === 'admin_global' ? 'selected' : ''; ?>>Administrador Global (Acceso Total)</option>
            </select>
        </div>

        <div style="margin-bottom: 2rem; padding: 1.25rem; border: 1px dashed var(--admin-border-light); border-radius: 6px; background-color: #f8fafc;">
            <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:var(--admin-text);">
                <span class="material-symbols-outlined" style="font-size:1.1rem; vertical-align:middle; color:var(--admin-text-muted);">key</span> 
                Establecer Contraseña Manualmente (Opcional)
            </label>
            <p style="font-size:0.85rem; color:var(--admin-text-muted); margin-top:0; margin-bottom:0.75rem;">
                <?php if ($isEdit): ?>
                    Dejá este campo vacío si <strong>NO</strong> querés modificar su clave actual.
                <?php else: ?>
                    Si dejás esto vacío, el sistema enviará un Email Automático con un Link para que el usuario invente su propia clave. Si le escribís una contraseña aquí, <strong>NO</strong> se enviará ningún correo y deberás informarle la clave vos mismo de boca a boca.
                <?php endif; ?>
            </p>
            <input type="password" name="forced_password" placeholder="Mín. <?php echo AMA_PASSWORD_MIN_LENGTH; ?> caracteres..." autocomplete="new-password" minlength="<?php echo AMA_PASSWORD_MIN_LENGTH; ?>" style="width:100%; padding:0.75rem; border:1px solid var(--admin-border-light); border-radius:6px; box-sizing:border-box; font-size:16px;" data-pw-meter>
            <p style="font-size:0.8rem; color:var(--admin-text-muted); margin: 0.5rem 0 0 0; line-height:1.4;"><?php echo htmlspecialchars(password_policy_help_text()); ?></p>
        </div>

        <div style="border-top: 1px solid var(--admin-border-light); padding-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="?section=usuarios" class="btn-admin-secondary">Cancelar</a>
            <button type="submit" class="btn-admin-primary">
                <?php if ($isEdit): ?>
                    <span class="material-symbols-outlined">save</span> Guardar Cambios
                <?php else: ?>
                    <span class="material-symbols-outlined">mail</span> Crear y Enviar Invitación
                <?php endif; ?>
            </button>
        </div>
    </form>
</div>
<script src="../../public/assets/js/admin-password.js" defer></script>
