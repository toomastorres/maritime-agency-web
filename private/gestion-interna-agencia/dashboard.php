<?php
/**
 * AMA — dashboard.php (Admin)
 * Ruteador principal del portal: carga la sección según query param.
 */

require_once 'includes/auth.php';

// Exigir login para ver esta página
require_login();

// Obtener sección actual desde query param
$section = $_GET['section'] ?? '';

// Definir permisos por rol (RBAC)
$permissions = [
    'caja'         => ['caja', 'perfil'],
    'cs'           => ['cs', 'perfil'],
    'rrhh'         => ['rrhh', 'rrhh_vacantes', 'rrhh_vacantes_form', 'rrhh_empleados', 'rrhh_empleados_form', 'perfil'],
    'admin_global' => [
        'home', 'caja', 'cs', 'rrhh', 'rrhh_vacantes', 'rrhh_vacantes_form',
        'rrhh_empleados', 'rrhh_empleados_form', 'rrhh_dashboard',
        'actividad', 'mantenimiento', 'usuarios', 'usuarios_form', 'perfil'
    ]
];

$user_role = $_SESSION['user_role'] ?? 'invitado';

if ($section && in_array($section, $permissions[$user_role])) {
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
    <title>Dashboard - AMA Agencies</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/admin-portal.css">
</head>
<body>

    <!-- Sidebar de Navegación Basado en Rol -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <span class="material-symbols-outlined">directions_boat</span>
            </div>
            <div class="sidebar-brand-text">
                <strong>Portal AMA</strong>
                <span>Gestión Interna</span>
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
                        <a href="?section=rrhh_empleados" class="<?php echo in_array($view, ['rrhh_empleados', 'rrhh_empleados_form']) ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">badge</span>
                            Directorio de Contactos
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
                            <a href="?section=rrhh_empleados" class="<?php echo in_array($view, ['rrhh_empleados', 'rrhh_empleados_form']) ? 'active' : ''; ?>">
                                <span class="material-symbols-outlined">badge</span>
                                Directorio de Contactos
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
