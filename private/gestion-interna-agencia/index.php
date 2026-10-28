<?php
require_once __DIR__ . '/includes/assets.php';
/**
 * AMA — index.php (Admin)
 * Página de login del portal de gestión interna.
 */

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once dirname(__DIR__, 2) . '/src/core/entra_oidc.php';
require_once dirname(__DIR__, 2) . '/src/core/entra_roles.php';

if (is_logged_in()) {
    header('Location: /gestion-interna-agencia/dashboard.php');
    exit;
}

$error = '';
$entraOn = EntraOidc::isConfigured();

// Con Entra activo el formulario de contraseña queda fuera de la vista: los
// empleados sólo ven el botón de Microsoft. La cuenta de emergencia llega al
// formulario con ?emergencia=1. Es sólo presentación — el servidor ya rechaza
// el login local de cualquier cuenta que no sea break-glass.
$modoEmergencia = !$entraOn || isset($_GET['emergencia']) || $_SERVER['REQUEST_METHOD'] === 'POST';

// Destino tras el login, validado contra lista blanca (anti open redirect).
$returnTo = entra_safe_redirect($_GET['return_to'] ?? null);

// ── Mensajes provenientes del flujo de Entra ID ───────────
if (!empty($_SESSION['entra_error'])) {
    $error = $_SESSION['entra_error'];
    unset($_SESSION['entra_error']);
} elseif (isset($_GET['expired'])) {
    $error = 'Tu sesión expiró por inactividad. Ingresá nuevamente.';
} elseif (($_GET['err'] ?? '') === 'entra_off') {
    $error = 'El acceso con Microsoft no está configurado. Contactá al administrador.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'Token de seguridad inválido. Recargue la página.';
    } else {
        // ── Rate Limiting Progresivo ──────────────────────────
        $ip = $_SERVER['REMOTE_ADDR'];
        $rate_limited = false;
        $fail_count = 0;
        try {
            $rl = $pdo->prepare("SELECT COUNT(*) FROM admin_audit_logs WHERE ip_address = :ip AND accion = 'login_fallido' AND creado_en > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            $rl->execute(['ip' => $ip]);
            $fail_count = (int) $rl->fetchColumn();

            // Bloqueo progresivo: 3 intentos → esperar, 5+ → bloqueo 30 min
            if ($fail_count >= 10) {
                $rate_limited = true;
                $error = 'Cuenta temporalmente bloqueada por seguridad. Contacte al administrador.';
            } elseif ($fail_count >= 5) {
                $rate_limited = true;
                $error = 'Demasiados intentos fallidos. Intente nuevamente en 30 minutos.';
            } elseif ($fail_count >= 3) {
                // Delay progresivo: 2s, 4s, 8s...
                sleep(min(pow(2, $fail_count - 2), 8));
            }
        } catch (\PDOException $e) {
            // Fallback: file-based rate limiting si la BD falla
            require_once dirname(__DIR__, 2) . '/src/core/utils.php';
            checkRateLimit(10, 1800); // 10 intentos por 30 min via archivo
        }

        $username_input = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // ── Throttle por cuenta (ataques distribuidos a un mismo usuario) ──
        $acct_fails = 0;
        if ($username_input !== '') {
            try {
                $acc = $pdo->prepare("SELECT COUNT(*) FROM admin_audit_logs WHERE accion = 'login_fallido' AND detalles = :d AND creado_en > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
                $acc->execute(['d' => 'user: ' . mb_substr($username_input, 0, 100)]);
                $acct_fails = (int) $acc->fetchColumn();
            } catch (\PDOException $e) { $acct_fails = 0; }
        }

        // ── Validación de entrada ────────────────────────────
        if ($rate_limited) {
            // Error ya definido arriba
        } elseif (empty($username_input) || empty($password)) {
            $error = 'Por favor ingrese usuario y contraseña.';
        } elseif (strlen($username_input) > 100 || strlen($password) > 200) {
            $error = 'Entrada demasiado larga.';
        } elseif ($acct_fails >= 10) {
            $error = 'Cuenta temporalmente bloqueada por seguridad. Intente más tarde o contacte al administrador.';
        } else {
            // ── Autenticación Timing-Safe ─────────────────────
            $stmt = $pdo->prepare("SELECT id, nombre, username, email, password_hash, rol_portal, is_active, is_break_glass FROM usuarios_admin WHERE username = :user LIMIT 1");
            $stmt->execute(['user' => $username_input]);
            $user = $stmt->fetch();

            // Siempre ejecutar password_verify() para mantener timing constante
            $dummyHash = '$2y$10$dummyHashToPreventTimingAttacksOnUserEnumeration123456';
            // Las cuentas federadas tienen password_hash NULL: se compara
            // igual contra el hash dummy para no variar el timing.
            $hashToVerify = ($user && !empty($user['password_hash'])) ? $user['password_hash'] : $dummyHash;
            $passwordValid = password_verify($password, $hashToVerify);

            if ($user && $passwordValid) {
                if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                    $error = 'Esta cuenta ha sido desactivada por un administrador.';
                } elseif ($entraOn && (int)($user['is_break_glass'] ?? 0) !== 1) {
                    // Con Entra operativo, la contraseña local queda reservada
                    // a la cuenta de emergencia. El resto entra con Microsoft.
                    $error = 'Esta cuenta debe ingresar con Microsoft. Usá el botón de arriba.';
                    try {
                        $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (:uid, 'login_local_rechazado', 'cuenta no break-glass', :ip)")
                            ->execute(['uid' => $user['id'], 'ip' => $ip]);
                    } catch (\PDOException $e) { /* no bloquear */ }
                } else {
                    // ── Login Exitoso ────────────────────────────
                    session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_role'] = $user['rol_portal'];
                $_SESSION['auth_provider'] = 'local';
                $_SESSION['login_ip'] = $ip;

                try {
                    $log_stmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (:uid, 'login_exitoso', :det, :ip)");
                    $log_stmt->execute([
                        'uid' => $user['id'],
                        'det' => (int)($user['is_break_glass'] ?? 0) === 1 ? 'CUENTA BREAK-GLASS (login local)' : 'login local',
                        'ip'  => $ip,
                    ]);
                    $pdo->prepare("UPDATE usuarios_admin SET ultimo_login = NOW() WHERE id = ?")->execute([$user['id']]);
                } catch (\PDOException $e) { /* logging failure no debe bloquear login */ }

                    header('Location: ' . $returnTo);
                    exit;
                }
            } else {
                // ── Login Fallido ────────────────────────────
                $error = 'Credenciales incorrectas.';

                try {
                    $log_stmt = $pdo->prepare("INSERT INTO admin_audit_logs (accion, ip_address, detalles) VALUES ('login_fallido', :ip, :detalles)");
                    $log_stmt->execute([
                        'ip' => $ip,
                        'detalles' => 'user: ' . mb_substr($username_input, 0, 100)
                    ]);
                } catch (\PDOException $e) { /* logging failure silenciado */ }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Interno - Atlantic Maritime Agency</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo admin_asset('css/admin-login.css'); ?>">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="login-brand-icon">
                    <img src="../../public/assets/img/logo-mark.svg" alt="" style="width:40px;height:40px;object-fit:contain">
                </div>
                <h1 class="login-brand-title">Portal AMA</h1>
                <p class="login-brand-subtitle">Acceso seguro al sistema interno</p>
            </div>

            <?php if ($error): ?>
                <div class="login-error">
                    <!-- FIX: font-size moved to .login-error .material-symbols-outlined in admin-login.css -->
                    <span class="material-symbols-outlined">error</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- ── Acceso corporativo con Microsoft ── -->
            <?php if ($entraOn): ?>
                <a class="login-ms-btn"
                   href="auth/login.php?return_to=<?php echo urlencode($returnTo); ?>">
                    <span class="login-ms-logo" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                    Ingresar con Microsoft
                </a>
                <p class="login-ms-hint">
                    Usá tu cuenta de AMA, la misma del correo.
                </p>
            <?php endif; ?>

            <?php if ($modoEmergencia): ?>
                <?php if ($entraOn): ?>
                    <div class="login-divider">Acceso de emergencia</div>
                    <p class="login-fallback-note">
                        Reservado a la cuenta de emergencia. El resto del personal ingresa
                        con su cuenta de Microsoft.
                    </p>
                <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="login-field">
                    <label for="username" class="login-label">Nombre de Usuario</label>
                    <div class="login-input-wrap">
                        <span class="material-symbols-outlined login-input-icon">person</span>
                        <input type="text" id="username" name="username" class="login-input" required placeholder="tu_usuario">
                    </div>
                </div>
                <div class="login-field">
                    <label for="password" class="login-label">Contraseña</label>
                    <div class="login-input-wrap">
                        <span class="material-symbols-outlined login-input-icon">lock</span>
                        <input type="password" id="password" name="password" class="login-input" required placeholder="••••••••">
                    </div>
                </div>

                <div style="text-align: right; margin-bottom: 1.5rem;">
                    <a href="forgot_password.php" style="color: #0069CC; text-decoration: none; font-size: 0.85rem; font-weight: 500;">¿Olvidaste tu contraseña?</a>
                </div>


                <button type="submit" class="login-btn">
                    <span class="material-symbols-outlined">login</span>
                    Ingresar al Portal
                </button>
            </form>

            <?php endif; ?>

            <?php if ($entraOn && !$modoEmergencia): ?>
                <p class="login-emergency-link">
                    <a href="?emergencia=1<?php echo $returnTo !== '/gestion-interna-agencia/dashboard.php' ? '&amp;return_to=' . urlencode($returnTo) : ''; ?>">Acceso de emergencia</a>
                </p>
            <?php endif; ?>

            <p class="login-footer">Atlantic Maritime Agency &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
</body>
</html>
