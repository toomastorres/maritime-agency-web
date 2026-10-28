<?php
/**
 * AMA — logout.php (Admin)
 * Destruye la sesión local y, si el ingreso fue con Microsoft,
 * cierra también la sesión en Entra ID (RP-initiated logout).
 */
require_once 'includes/auth.php';
require_once dirname(__DIR__, 2) . '/src/core/entra_oidc.php';

// Leer el proveedor ANTES de destruir la sesión.
$wasEntra = is_entra_session();

// Dejar constancia del cierre antes de perder el user_id.
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'includes/db.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (:uid, 'logout', :det, :ip)")
                ->execute([
                    'uid' => $_SESSION['user_id'],
                    'det' => $wasEntra ? 'via Microsoft Entra ID' : 'sesión local',
                    'ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
        }
    } catch (\PDOException $e) {
        // El cierre de sesión nunca debe fallar por la auditoría.
    }
}

// Destruir todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

// Sesión federada: cerrar también del lado de Microsoft. Sin esto el
// usuario vuelve a entrar con un click porque Entra lo recuerda.
if ($wasEntra && EntraOidc::isConfigured()) {
    header('Location: ' . EntraOidc::logoutUrl());
    exit;
}

header('Location: /gestion-interna-agencia/index.php');
exit;
