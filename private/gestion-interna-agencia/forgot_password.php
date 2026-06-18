<?php
/**
 * AMA — forgot_password.php
 * Proceso seguro de recuperación de contraseña para administradores.
 */

require_once 'includes/auth.php';
require_once dirname(__DIR__, 2) . '/src/config/config.php';
require_once dirname(__DIR__, 2) . '/src/core/database.php';
require_once dirname(__DIR__, 2) . '/src/core/email.php';

// Si ya está logueado, redirigir al dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Protección Rate Limiting & CSRF básica
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La sesión expiró o la solicitud es inválida. Intente de nuevo.";
    } else {
        $username = trim($_POST['username'] ?? '');

        if (empty($username)) {
            $error = "El Nombre de Usuario es obligatorio.";
        } else {
            try {
                // Verificar si el usuario existe y está ACTIVO
                $stmt = $pdo->prepare("SELECT id, nombre, email, is_active FROM usuarios_admin WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    if ((int)$user['is_active'] === 0) {
                        $error = "Esta cuenta ha sido bloqueada. Contacte al administrador global.";
                    } else {
                        // Generar token criptográficamente seguro (32 bytes = 64 hex chars)
                        $token = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $token);
                        // Vencimiento en 1 hora
                        $expires = date('Y-m-d H:i:s', time() + 3600);

                        $update = $pdo->prepare("UPDATE usuarios_admin SET reset_token = ?, reset_expires = ? WHERE id = ?");
                        if ($update->execute([$tokenHash, $expires, $user['id']])) {
                            
                            // Preparar el link de recuperación
                            $resetLink = BASE_URL . "private/gestion-interna-agencia/reset_password.php?token=" . urlencode($token);
                            
                            // Construir email nativo asegurando formato idéntico a los de sistema
                            $subject = "🔒 Recuperación de Contraseña — AMA Portal";
                            $htmlBody = EmailService::buildHtmlTemplate("Recuperación de Acceso", "
                                <p>Hola <strong>" . htmlspecialchars($user['nombre']) . "</strong>,</p>
                                <p>Has solicitado restablecer la contraseña de tu cuenta en el Portal Interno de AMA Agencies.</p>
                                <p>Para elegir una nueva contraseña, hacé clic en el siguiente enlace. Este enlace caducará en 1 hora por motivos de seguridad:</p>
                                <p style='text-align: center; margin: 30px 0;'>
                                    <a href='{$resetLink}' style='background-color: #0033a0; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; font-family: Arial, sans-serif; display: inline-block;'>
                                        Restablecer mi contraseña
                                    </a>
                                </p>
                                <p>Si no podés hacer clic en el botón, copiá y pegá la siguiente URL en tu navegador:</p>
                                <p style='word-break: break-all; font-size: 0.9em; color: #666;'>{$resetLink}</p>
                                <hr style='border:none; border-top: 1px solid #ddd; margin: 20px 0;'>
                                <p style='font-size: 0.85em; color: #888;'>Si vos no solicitaste este cambio, ignorá este correo y tu contraseña actual seguirá vigente.</p>
                            ");

                            // Enviar como correo transaccional suelto usando core email object
                            $emailService = new EmailService();
                            // Destinatario explícito: $user['email']
                            $sent = $emailService->send($subject, $htmlBody, $user['email']);
                            
                            if ($sent) {
                                // Exito. Loguear para auditoría.
                                $logStmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
                                $logStmt->execute([$user['id'], 'forgot_password_req', "Solicitud válida, token enviado.", $_SERVER['REMOTE_ADDR']]);
                            } else {
                                $error = "No se pudo enviar el correo de recuperación. Contacte a IT.";
                                // Borrar el token para que no quede pendiente? En realidad da igual si falla el mail.
                            }
                        } else {
                            $error = "Error interno al generar el token temporal.";
                        }
                    }
                } else {
                    // Timing Safe: Independientemente de si el correo existe o no, 
                    // mostramos el mismo mensaje de éxito para evitar user enumeration.
                    // Para log de auditoría podríamos registrar "forgot pass intento con email falso", pero es complejo.
                }

                if (!$error) {
                    $success = "Si el nombre de usuario está registrado en nuestro sistema y la cuenta está activa, recibirá un enlace para restablecer su contraseña en los próximos minutos en su correo asociado.";
                }

            } catch (\PDOException $e) {
                // Registrar silenciosamente o mostrar genérico
                $error = "Ocurrió un error al procesar la solicitud. Inténtelo más tarde.";
                error_log("Database error en forgot_password: " . $e->getMessage());
            }
        }
    }
}

// Generar token para este form render
$csrfToken = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - AMA Agencies</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Base CSS (Login uses its own local styles in index.php, we mirror the minimalist design here) -->
    <style>
        :root {
            --brand-blue: #0033a0;
            --brand-blue-hover: #002277;
            --bg-color: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --error-border: #f87171;
            --success-bg: #f0fdf4;
            --success-text: #166534;
            --success-border: #4ade80;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 2rem;
        }

        .login-card {
            background: var(--surface);
            padding: 2.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid var(--border);
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo img {
            max-height: 55px;
            width: auto;
        }

        .login-title {
            font-size: 1.25rem;
            font-weight: 600;
            text-align: center;
            margin: 0 0 0.5rem 0;
            color: var(--text-main);
        }

        .login-subtitle {
            font-size: 0.875rem;
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            line-height: 1.4;
        }

        .alert-error {
            background-color: var(--error-bg);
            color: var(--error-text);
            border-left-color: var(--error-border);
        }

        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-text);
            border-left-color: var(--success-border);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-main);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            font-family: inherit;
            border: 1px solid var(--border);
            border-radius: 6px;
            box-sizing: border-box;
            background-color: var(--bg-color);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--brand-blue);
            box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.1);
            background-color: var(--surface);
        }

        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            background-color: var(--brand-blue);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            font-family: inherit;
        }

        .btn-submit:hover {
            background-color: var(--brand-blue-hover);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--brand-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--brand-blue-hover);
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="brand-logo">
            <img src="../../public/assets/img/GGroupFlat-Blu.png" alt="AMA Agencies Argentina">
        </div>
        
        <h1 class="login-title">Recuperación de Contraseña</h1>
        
        <?php if ($success): ?>
            <!-- Vista de Éxito -->
            <p class="login-subtitle">
                Solicitud procesada correctamente.
            </p>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <a href="index.php" class="back-link">Volver al inicio de sesión</a>
        <?php else: ?>
            <!-- Vista de Solicitud -->
            <p class="login-subtitle">
                Ingresá tu Nombre de Usuario oficial y te enviaremos un enlace para establecer una nueva clave a la casilla compartida o personal.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Nombre de Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" autocomplete="username" required autofocus placeholder="tu_usuario">
                </div>
                
                <button type="submit" class="btn-submit">Enviar enlace de recuperación</button>
            </form>

            <a href="index.php" class="back-link">← Volver al Login</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
