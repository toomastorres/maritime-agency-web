<?php
/**
 * AMA — reset_password.php
 * Verificador de token temporal y asignación de nueva contraseña.
 */

require_once 'includes/auth.php';
require_once dirname(__DIR__, 2) . '/src/config/config.php';
require_once dirname(__DIR__, 2) . '/src/core/database.php';
require_once 'includes/password_policy.php';

// Rate limiting: máximo 5 intentos por hora por IP
checkRateLimit(5, 3600);

// Si ya está logueado, redirigir al dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$user = null;

// Validación inicial del token
if (!$token) {
    die("Petición inválida. Enlace de recuperación incompleto o corrupto.");
}

try {
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT id, nombre, username, email, reset_expires FROM usuarios_admin WHERE reset_token = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "El enlace de recuperación es inválido, ya fue utilizado o la cuenta está desactivada.";
    } else {
        // Verificar caducidad
        $expiresTime = strtotime($user['reset_expires']);
        if (time() > $expiresTime) {
            $error = "El enlace ha caducado. Debe volver a solicitar la recuperación de contraseña.";
        }
    }
} catch (\PDOException $e) {
    error_log("Database error en reset_password: " . $e->getMessage());
    $error = "Ocurrió un error de base de datos.";
}

// Procesar el cambio
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$error) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La sesión expiró. Recargue la página e intente de nuevo.";
    } else {
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if ($pass1 !== $pass2) {
            $error = "Las contraseñas no coinciden.";
        } elseif (!empty($pwdErrors = validate_password_strength($pass1, $user['username'], $user['email']))) {
            $error = $pwdErrors[0];
        } else {
            try {
                // Hashear nueva contraseña
                $hash = password_hash($pass1, PASSWORD_DEFAULT);

                // Actualizar DB y borrar token
                $update = $pdo->prepare("UPDATE usuarios_admin SET password_hash = ?, reset_token = NULL, reset_expires = NULL, password_updated_at = NOW() WHERE id = ?");
                
                if ($update->execute([$hash, $user['id']])) {
                    $success = "¡Contraseña actualizada con éxito! Ya podés iniciar sesión con tu nueva clave.";
                    
                    // Auditoría
                    $logStmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
                    $logStmt->execute([$user['id'], 'password_reset_success', "Contraseña restablecida vía link email", $_SERVER['REMOTE_ADDR']]);
                } else {
                    $error = "Hubo un problema al guardar tu nueva contraseña.";
                }
            } catch (\PDOException $e) {
                error_log("Database error actualizando password: " . $e->getMessage());
                $error = "Ocurrió un error al procesar el cambio de contraseña.";
            }
        }
    }
}

$csrfToken = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Nueva Contraseña - AMA Agencies</title>
    <!-- Mismos estilos de forgot_password.php -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand-blue: #0033a0; --brand-blue-hover: #002277; --bg-color: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --border: #e2e8f0; --error-bg: #fef2f2; --error-text: #991b1b; --error-border: #f87171; --success-bg: #f0fdf4; --success-text: #166534; --success-border: #4ade80; }
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: var(--bg-color); color: var(--text-main); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-wrapper { width: 100%; max-width: 440px; padding: 2rem; }
        .login-card { background: var(--surface); padding: 2.5rem 2rem; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid var(--border); }
        .brand-logo { text-align: center; margin-bottom: 2rem; }
        .brand-logo img { max-height: 55px; width: auto; }
        .login-title { font-size: 1.25rem; font-weight: 600; text-align: center; margin: 0 0 0.5rem 0; }
        .login-subtitle { font-size: 0.875rem; text-align: center; color: var(--text-muted); margin-bottom: 2rem; line-height: 1.5; }
        .alert { padding: 1rem; border-radius: 6px; font-size: 0.875rem; margin-bottom: 1.5rem; border-left: 4px solid; line-height: 1.4; }
        .alert-error { background-color: var(--error-bg); color: var(--error-text); border-left-color: var(--error-border); }
        .alert-success { background-color: var(--success-bg); color: var(--success-text); border-left-color: var(--success-border); }
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem 1rem; font-size: 0.95rem; font-family: inherit; border: 1px solid var(--border); border-radius: 6px; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--brand-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.1); }
        .btn-submit { width: 100%; padding: 0.875rem; font-size: 0.95rem; font-weight: 600; color: #ffffff; background-color: var(--brand-blue); border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s, transform 0.1s; }
        .btn-submit:hover { background-color: var(--brand-blue-hover); }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: var(--brand-blue); text-decoration: none; font-weight: 500; }
        .back-link:hover { color: var(--brand-blue-hover); text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="brand-logo">
            <img src="../../public/assets/img/logo-blue.svg" alt="AMA Agencies Argentina">
        </div>
        
        <h1 class="login-title">Establecer Nueva Contraseña</h1>
        
        <?php if ($success): ?>
            <!-- Mostrar éxito puro -->
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <a href="index.php" class="btn-submit" style="text-decoration: none; text-align: center; display: block; box-sizing: border-box;">Continuar al Login</a>
            
        <?php elseif ($error && !$user): ?>
            <!-- Token inválido terminal -->
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="forgot_password.php" class="back-link">Solicitar otro enlace</a>
            <a href="index.php" class="back-link" style="margin-top: 0.5rem; color: var(--text-muted);">Ir al Login</a>
            
        <?php else: ?>
            <!-- Formulario de reseteo -->
            <p class="login-subtitle">
                Hola, <strong><?php echo htmlspecialchars($user['nombre']); ?></strong>. Ingresá tu nueva contraseña para el sistema.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars(urlencode($token)); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="password" class="form-label">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" required minlength="<?php echo AMA_PASSWORD_MIN_LENGTH; ?>" placeholder="Mínimo <?php echo AMA_PASSWORD_MIN_LENGTH; ?> caracteres" data-pw-meter>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin: 0.5rem 0 0 0; line-height: 1.4;"><?php echo htmlspecialchars(password_policy_help_text()); ?></p>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" autocomplete="new-password" required minlength="<?php echo AMA_PASSWORD_MIN_LENGTH; ?>" placeholder="Repetir nueva clave">
                </div>
                
                <button type="submit" class="btn-submit">Guardar y Restaurar Acceso</button>
            </form>

            <a href="index.php" class="back-link">← Cancelar y volver al Login</a>
        <?php endif; ?>
    </div>
</div>

<script src="../../public/assets/js/admin-password.js" defer></script>
</body>
</html>
