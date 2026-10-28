<?php
require_once __DIR__ . '/includes/assets.php';
/**
 * AMA — dashboard.php (Admin)
 * Ruteador principal del portal: carga la sección según query param.
 */

/*
 * Buffer de salida. Las vistas se incluyen dentro del HTML de esta página
 * (línea ~190), así que cuando una vista hace header('Location: ...') tras un
 * POST ya se mandaron el <head> y el sidebar: PHP tira "headers already sent",
 * no redirige, y el usuario recibe una página cortada a la mitad después de
 * cada alta, baja o cambio de estado. Afecta a vacantes, usuarios y
 * postulaciones por igual. Con el buffer abierto los header() vuelven a
 * funcionar y el patrón POST-redirect-GET de las vistas se cumple.
 */
ob_start();

require_once 'includes/auth.php';

// Exigir login para ver esta página
require_login();

/**
 * Redirección desde una vista: descarta el HTML ya acumulado en el buffer y
 * manda el 302 limpio.
 */
function admin_redirect(string $url): void {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Location: ' . $url);
    exit;
}

// Obtener sección actual desde query param
$section = $_GET['section'] ?? '';

// Definir permisos por rol (RBAC)
$permissions = [
    'caja'          => ['caja', 'perfil'],
    'cs'            => ['cs', 'perfil'],
    'rrhh'          => ['rrhh', 'rrhh_vacantes', 'rrhh_vacantes_form', 'rrhh_postulantes', 'perfil'],
    'documentacion' => ['perfil'],
    'admin_global'  => [
        'home', 'caja', 'cs', 'novedades', 'rrhh', 'rrhh_vacantes', 'rrhh_vacantes_form',
        'rrhh_postulantes', 'rrhh_dashboard',
        'actividad', 'mantenimiento', 'usuarios', 'usuarios_form', 'perfil'
    ]
];

$user_role = $_SESSION['user_role'] ?? 'invitado';

// Fail-closed: un rol sin entrada en el mapa no habilita ninguna sección.
$allowed_sections = $permissions[$user_role] ?? [];

// Postulaciones sin revisar: el número va en el menú para que RRHH vea que
// hay CV esperando sin tener que entrar a la sección a mirar.
$postulaciones_nuevas = 0;
if (in_array($user_role, ['rrhh', 'admin_global'], true)) {
    try {
        require_once 'includes/db.php';
        $postulaciones_nuevas = (int) $pdo->query("SELECT COUNT(*) FROM applicants WHERE estado = 'nuevo'")->fetchColumn();
    } catch (\Throwable $e) {
        $postulaciones_nuevas = 0;
    }
}

if ($section && in_array($section, $allowed_sections, true)) {
    $view = $section;
} else {
    // Vista por defecto basada en el rol
    if ($user_role === 'caja') $view = 'caja';
    elseif ($user_role === 'cs') $view = 'cs';
    elseif ($user_role === 'rrhh') $view = 'rrhh';
    elseif ($user_role === 'admin_global') $view = 'home'; // Admin ve Dashboard por defecto
    else $view = 'bienvenida'; // Vista fallback
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Atlantic Maritime Agency</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo admin_asset('css/admin-portal.css'); ?>">
</head>
<body>

    <!-- Sidebar de Navegación Basado en Rol -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="../../public/assets/img/logo-white.svg" alt="" style="width:32px;height:32px;object-fit:contain">
            <div class="sidebar-brand-text">
                <strong>Portal AMA</strong>
                <span>Gestión interna</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <?php if ($user_role === 'admin_global'): ?>
                <h3>Panel de Control</h3>
                <ul>
                    <li>
                        <a href="?section=home" class="<?php echo $view === 'home' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">dashboard</span>
                            Inicio
                        </a>
                    </li>
                    <li>
                        <a href="?section=actividad" class="<?php echo $view === 'actividad' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">history</span>
                            Registro de Actividad
                        </a>
                    </li>
                    <li>
                        <a href="?section=mantenimiento" class="<?php echo $view === 'mantenimiento' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">settings_suggest</span>
                            Salud del Sistema
                        </a>
                    </li>
                </ul>

                <h3>Operaciones</h3>
                <ul>
                    <li>
                        <a href="?section=caja" class="<?php echo $view === 'caja' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">currency_exchange</span>
                            Tipo de Cambio
                        </a>
                    </li>
                    <li>
                        <a href="?section=cs" class="<?php echo $view === 'cs' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">calendar_month</span>
                            Schedules
                        </a>
                    </li>
                </ul>

                <h3>RRHH</h3>
                <ul>
                    <li>
                        <a href="?section=rrhh_vacantes" class="<?php echo in_array($view, ['rrhh_vacantes', 'rrhh_vacantes_form']) ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">work</span>
                            Vacantes
                        </a>
                    </li>
                    <li>
                        <a href="?section=rrhh_postulantes" class="<?php echo $view === 'rrhh_postulantes' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">contact_page</span>
                            Postulaciones
                            <?php if ($postulaciones_nuevas > 0): ?>
                            <span class="sidebar-count" title="<?php echo $postulaciones_nuevas; ?> sin revisar"><?php echo $postulaciones_nuevas; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>

                <h3>Contenido</h3>
                <ul>
                    <li>
                        <a href="?section=novedades" class="<?php echo $view === 'novedades' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">newspaper</span>
                            Novedades
                        </a>
                    </li>
                </ul>

                <h3>Sistema</h3>
                <ul>
                    <li>
                        <a href="?section=usuarios" class="<?php echo in_array($view, ['usuarios', 'usuarios_form']) ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">manage_accounts</span>
                            Usuarios Admin
                        </a>
                    </li>
                </ul>
            <?php else: ?>
                <h3>Mi Área</h3>
                <ul>
                    <?php if (has_role('caja')): ?>
                        <li>
                            <a href="?section=caja" class="<?php echo $view === 'caja' ? 'active' : ''; ?>">
                                <span class="material-symbols-outlined">currency_exchange</span>
                                Tipo de Cambio
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (has_role('cs')): ?>
                        <li>
                            <a href="?section=cs" class="<?php echo $view === 'cs' ? 'active' : ''; ?>">
                                <span class="material-symbols-outlined">calendar_month</span>
                                Schedules
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (has_role('rrhh')): ?>
                        <li>
                            <a href="?section=rrhh_vacantes" class="<?php echo in_array($view, ['rrhh_vacantes', 'rrhh_vacantes_form']) ? 'active' : ''; ?>">
                                <span class="material-symbols-outlined">work</span>
                                Vacantes
                            </a>
                        </li>
                        <li>
                            <a href="?section=rrhh_postulantes" class="<?php echo $view === 'rrhh_postulantes' ? 'active' : ''; ?>">
                                <span class="material-symbols-outlined">contact_page</span>
                                Postulaciones
                                <?php if ($postulaciones_nuevas > 0): ?>
                                <span class="sidebar-count" title="<?php echo $postulaciones_nuevas; ?> sin revisar"><?php echo $postulaciones_nuevas; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <h3>Cuenta</h3>
                <ul>
                    <li>
                        <a href="?section=perfil" class="<?php echo $view === 'perfil' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">person</span>
                            Mi Perfil
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <?php if ($user_role === 'admin_global'): ?>
            <a href="?section=perfil" style="display:flex; align-items:center; gap:0.5rem; padding:0.5rem 1rem; color:rgba(255,255,255,0.7); text-decoration:none; font-size:0.9rem; border-radius:6px; margin-bottom:0.5rem; transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'" class="<?php echo $view === 'perfil' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined" style="font-size:1.2rem;">person</span>
                Mi Perfil
            </a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">
                <span class="material-symbols-outlined">logout</span>
                Cerrar Sesión
            </a>
        </div>
    </aside>

    <!-- Área de Contenido -->
    <main class="main-content">
        <header class="header">
            <!-- FIX: moved inline styles to .user-info-icon and .badge--spaced classes in admin-portal.css -->
            <div class="user-info">
                <span class="material-symbols-outlined user-info-icon">account_circle</span>
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                <span class="badge badge-blue badge--spaced"><?php echo htmlspecialchars($user_role); ?></span>
                <?php if (!is_entra_session()): ?>
                    <span class="badge badge--spaced"
                          style="background:rgba(138,90,0,.12); color:#8A5A00; border:1px solid #8A5A00;"
                          title="Sesión abierta con contraseña local, no con Microsoft">sesión local</span>
                <?php endif; ?>
            </div>
        </header>

        <div class="view-container">
            <?php
            // Ruteador para inyectar la vista correspondiente
            // Defense-in-depth: basename() previene path traversal aunque $view ya viene de whitelist
            $view_file = 'views/' . basename($view) . '.php';
            if (file_exists($view_file)) {
                include $view_file;
            } else {
                echo "<h3>Bienvenido al Portal</h3>";
                echo "<p>Seleccione una opción del menú lateral para comenzar.</p>";
            }
            ?>
        </div>
    </main>
</body>
</html>
