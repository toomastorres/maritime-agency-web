<?php
/**
 * AMA — auth.php (Admin)
 * Autenticación, sesiones, CSRF y control de acceso por rol.
 */

// ── Security Headers ──────────────────────────────────────

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ── Configuración de Sesión Segura ────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly'  => true,
        'samesite'  => 'Strict',
    ]);
    session_start();
}

// ── Regeneración de ID de Sesión ──────────────────────────

if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} else {
    $interval = 60 * 15;
    if (time() - $_SESSION['last_regeneration'] >= $interval) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// ── Fingerprint de Sesión (protección contra session hijacking) ──

$sessionFingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

if (!isset($_SESSION['fingerprint'])) {
    $_SESSION['fingerprint'] = $sessionFingerprint;
} elseif (!hash_equals($_SESSION['fingerprint'], $sessionFingerprint)) {
    // Posible session hijacking — destruir sesión
    session_unset();
    session_destroy();
    session_start();
    $_SESSION = [];
    header('Location: index.php');
    exit;
}

// ── Timeout de Inactividad (30 min) ──────────────────────

if (isset($_SESSION['user_id'])) {
    $maxInactivity = 30 * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxInactivity)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION = [];
        header('Location: index.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// ── Funciones de Autenticación ────────────────────────────

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function has_role($role) {
    if (!is_logged_in()) return false;
    if ($_SESSION['user_role'] === 'admin_global') return true;
    return $_SESSION['user_role'] === $role;
}

function require_role($role) {
    if (!has_role($role)) {
        http_response_code(403);
        die('Acceso Denegado: No tienes los permisos necesarios para ver esta sección.');
    }
}

// ── Protección CSRF ──────────────────────────────────────

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    // Rotar token cada 30 minutos
    if (time() - $_SESSION['csrf_token_time'] > 1800) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
