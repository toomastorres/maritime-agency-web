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
require_once dirname(__DIR__, 3) . '/src/core/entra_oidc.php';

$error = '';
$success = '';

// Con Entra ID activo, las cuentas se aprovisionan solas en el primer ingreso
// con Microsoft y el rol lo define el App Role del token. El panel no emite
// credenciales: la única excepción es la cuenta break-glass.
$entraOn = EntraOidc::isConfigured();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$user = [
    'nombre' => '',
    'username' => '',
    'email' => '',
    'rol_portal' => 'cs',
    'is_break_glass' => 0,
    'auth_provider' => 'entra',
];

if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT id, nombre, username, email, rol_portal, is_break_glass, auth_provider FROM usuarios_admin WHERE id = ?");
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

// ── Qué se puede tocar según el proveedor de identidad ────
$esBreakGlass  = $isEdit && (int)($user['is_break_glass'] ?? 0) === 1;
$puedeCrear    = !$entraOn;                   // las cuentas nacen del primer login
$gestionaClave = !$entraOn || $esBreakGlass;  // sólo break-glass conserva contraseña
$gestionaRol   = !$entraOn || $esBreakGlass;  // el rol lo manda Entra

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token inválido. Intente de nuevo.";
    } elseif (!$isEdit && !$puedeCrear) {
        // Bloqueo del lado del servidor, no sólo de la interfaz.
        $error = "La creación manual de usuarios está deshabilitada. Las cuentas se crean "
               . "automáticamente cuando la persona ingresa por primera vez con Microsoft. "
               . "Para darle acceso, asignale el rol en Microsoft Entra ID.";
    } elseif (!empty($_POST['forced_password']) && !$gestionaClave) {
        $error = "Esta cuenta ingresa con Microsoft: no se le puede asignar una contraseña. "
               . "Su acceso se gestiona desde Microsoft Entra ID.";
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        // Un <select> deshabilitado no viaja en el POST: si Entra gobierna el rol,
        // se conserva el actual y se ignora cualquier valor recibido.
        $rol = $gestionaRol ? ($_POST['rol_portal'] ?? '') : ($user['rol_portal'] ?? '');
        $forced_password = $gestionaClave ? ($_POST['forced_password'] ?? '') : '';

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
                            admin_redirect('?section=usuarios');
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
                                    $subject = "🌟 Bienvenido al Portal Interno de Atlantic Maritime Agency";
                                    $htmlBody = EmailService::buildHtmlTemplate("Configuración de Cuenta", "
                                        <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                                        <p>El administrador <strong>{$_SESSION['user_name']}</strong> ha creado tu cuenta en el Sistema de Gestión Interna de Atlantic Maritime Agency.</p>
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
                                admin_redirect("?section=usuarios&msg={$successTitle}");
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

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title"><?php echo $isEdit ? 'Editar Usuario' : 'Nuevo Usuario / Enviar Invitación'; ?></h1>
        <p class="admin-page-sub admin-page-sub--ink">
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

<?php if (!$isEdit && !$puedeCrear): ?>

    <!-- ── Alta manual deshabilitada: las cuentas vienen de Entra ID ── -->
    <div class="admin-card" style="max-width: 640px; padding: 2rem; border-left: 4px solid #0069CC;">
        <h2 style="margin:0 0 1rem; font-size:1.15rem; color:var(--admin-text);">
            <span class="material-symbols-outlined" style="vertical-align:middle; color:#0069CC;">badge</span>
            Las cuentas se crean solas
        </h2>
        <p style="color:var(--admin-text-muted); line-height:1.7; margin:0 0 1rem;">
            Este portal usa <strong>Microsoft Entra ID</strong>. No se emiten usuarios ni contraseñas:
            la cuenta se crea automáticamente la primera vez que la persona entra con Microsoft,
            y el rol sale del que le asignaste en Entra.
        </p>
        <p style="color:var(--admin-text); font-weight:500; margin:0 0 .5rem;">Para darle acceso a alguien:</p>
        <ol style="color:var(--admin-text-muted); line-height:1.8; margin:0 0 1.25rem; padding-left:1.25rem;">
            <li>Entrá a <strong>entra.microsoft.com</strong></li>
            <li><strong>Identity → Groups →</strong> el grupo del área (por ejemplo <code>GRP-Portal-CS</code>)</li>
            <li><strong>Members → Add members →</strong> elegí a la persona</li>
        </ol>
        <p style="color:var(--admin-text-muted); font-size:.9rem; line-height:1.6; margin:0 0 1.5rem;">
            En su próximo ingreso ya tendrá acceso. Si necesitás bloquear a alguien de inmediato,
            usá el botón 🚫 del listado de usuarios.
        </p>
        <div style="border-top:1px solid var(--admin-border-light); padding-top:1.25rem;">
            <a href="?section=usuarios" class="btn-admin-primary">
                <span class="material-symbols-outlined">arrow_back</span> Volver al listado
            </a>
        </div>
    </div>

<?php else: ?>

<div class="admin-card admin-card--form admin-card--pad">
    <?php if ($isEdit && $entraOn && !$esBreakGlass): ?>
        <div style="margin-bottom:1.5rem; padding:.9rem 1rem; background:rgba(0,105,204,.08); border-left:3px solid #0069CC;">
            <p style="margin:0; font-size:.88rem; color:var(--admin-text); line-height:1.6;">
                <strong>Cuenta de Microsoft.</strong> El nombre y el rol se sincronizan desde Entra ID
                en cada ingreso, así que los cambios que hagas acá se pisan. Para cambiarle el rol,
                movela de grupo en Entra.
            </p>
        </div>
    <?php elseif ($esBreakGlass): ?>
        <div style="margin-bottom:1.5rem; padding:.9rem 1rem; background:rgba(138,90,0,.10); border-left:3px solid #8A5A00;">
            <p style="margin:0; font-size:.88rem; color:var(--admin-text); line-height:1.6;">
                <strong>Cuenta de emergencia (break-glass).</strong> Es la única que conserva
                contraseña local, para poder entrar si Microsoft no está disponible.
                Guardá la clave en un lugar físico seguro.
            </p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div>
            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--admin-text);">Nombre Completo</label>
            <input type="text" name="nombre" required value="<?php echo htmlspecialchars($user['nombre']); ?>" style="width:100%; padding:0.75rem; border:1px solid rgba(128,128,131,.5); border-radius:0; box-sizing:border-box;">
        </div>

        <div>
            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--admin-text);">Nombre de Usuario (Para Iniciar Sesión)</label>
            <input type="text" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="ej: jperez o of.caja" style="width:100%; padding:0.75rem; border:1px solid rgba(128,128,131,.5); border-radius:0; box-sizing:border-box;" title="Solo letras, números, puntos y guiones">
        </div>

        <div>
            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--admin-text);">Correo Electrónico (Envío de Invitación/Recuperación)</label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>" style="width:100%; padding:0.75rem; border:1px solid rgba(128,128,131,.5); border-radius:0; box-sizing:border-box;">
        </div>

        <div>
            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--admin-text);">Rol en el Sistema</label>
            <p style="font-size:0.85rem; color:var(--admin-text-muted); margin-top:0; margin-bottom:0.5rem;">
                <?php if ($gestionaRol): ?>
                    Define qué secciones del menú lateral están disponibles para ver y modificar.
                <?php else: ?>
                    Lo define el App Role asignado en Microsoft Entra ID. Para cambiarlo, movela de grupo allá.
                <?php endif; ?>
            </p>
            <select name="rol_portal" required <?php echo $gestionaRol ? '' : 'disabled'; ?>
                    style="width:100%; padding:0.75rem; border:1px solid rgba(128,128,131,.5); border-radius:0; background:<?php echo $gestionaRol ? '#fff' : 'var(--admin-bg)'; ?>; box-sizing:border-box; <?php echo $gestionaRol ? '' : 'color:var(--admin-text-muted); cursor:not-allowed;'; ?>">
                <option value="cs" <?php echo $user['rol_portal'] === 'cs' ? 'selected' : ''; ?>>Customer Service (Schedules / Rutas)</option>
                <option value="caja" <?php echo $user['rol_portal'] === 'caja' ? 'selected' : ''; ?>>Caja / Finanzas (Tipo de Cambio)</option>
                <option value="rrhh" <?php echo $user['rol_portal'] === 'rrhh' ? 'selected' : ''; ?>>Recursos Humanos (Vacantes, Empleados)</option>
                <option value="documentacion" <?php echo $user['rol_portal'] === 'documentacion' ? 'selected' : ''; ?>>Documentación Oficial (Descargas de Sistema)</option>
                <hr>
                <option value="admin_global" <?php echo $user['rol_portal'] === 'admin_global' ? 'selected' : ''; ?>>Administrador Global (Acceso Total)</option>
            </select>
        </div>

        <?php if ($gestionaClave): ?>
        <div style="margin-bottom: 2rem; padding: 1.25rem; border: 1px dashed rgba(15,57,108,.55); border-radius: 0; background-color: var(--admin-bg);">
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
            <input type="password" name="forced_password" placeholder="Mín. <?php echo AMA_PASSWORD_MIN_LENGTH; ?> caracteres..." autocomplete="new-password" minlength="<?php echo AMA_PASSWORD_MIN_LENGTH; ?>" style="width:100%; padding:0.75rem; border:1px solid rgba(128,128,131,.5); border-radius:0; box-sizing:border-box; font-size:16px;" data-pw-meter>
            <p style="font-size:0.8rem; color:var(--admin-text-muted); margin: 0.5rem 0 0 0; line-height:1.4;"><?php echo htmlspecialchars(password_policy_help_text()); ?></p>
        </div>
        <?php endif; ?>

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

<?php endif; ?>

<?php if ($gestionaClave): ?>
<script src="../../public/assets/js/admin-password.js" defer></script>
<?php endif; ?>
