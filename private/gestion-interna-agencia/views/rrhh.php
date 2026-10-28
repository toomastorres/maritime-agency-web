<?php
/**
 * AMA — rrhh.php (Admin)
 * Vista principal del módulo RRHH: hub de acceso rápido.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_role('rrhh');

// Mostrar mensajes de sesión si los hubiera
$msj = '';
if (isset($_SESSION['msj'])) {
    $is_error = strpos($_SESSION['msj'], 'Error') !== false;
    $msj = "<div class='admin-alert " . ($is_error ? 'admin-alert-error' : 'admin-alert-success') . "'>" . htmlspecialchars($_SESSION['msj'], ENT_QUOTES, 'UTF-8') . "</div>";
    unset($_SESSION['msj']);
}
?>

<?php echo $msj; ?>

<div style="margin-bottom: 32px;">
    <h1 style="font-family:var(--font-admin-serif); margin:0 0 8px; font-size:36px; font-weight:400; color:var(--admin-blue); line-height:1.1;">Recursos Humanos</h1>
    <p style="margin: 0; color: var(--admin-text); font-size: 16px;">
        Desde aquí podés gestionar las búsquedas laborales activas de la agencia.
    </p>
</div>

<div class="admin-grid-2">

    <!-- Card: Vacantes -->
    <a href="?section=rrhh_vacantes" style="text-decoration: none; color: inherit;">
        <div class="admin-card" style="cursor: pointer; margin-bottom:0;">
            <div style="display: flex; align-items: center; gap: 1rem; padding: 24px 24px 18px;">
                <span class="material-symbols-outlined" style="font-size: 28px; color: var(--admin-blue); flex-shrink:0;">work</span>
                <div>
                    <div style="font-family:var(--font-admin-serif); font-size: 21px; font-weight: 400; color: var(--admin-blue);">Vacantes</div>
                    <div style="font-size: 14px; color: var(--admin-text-muted); margin-top: 4px;">
                        Gestión de búsquedas laborales activas y archivadas
                    </div>
                </div>
            </div>
            <div style="padding: 14px 24px 18px; border-top: 1px solid var(--admin-border-light);">
                <span style="font-size: 14px; color: var(--admin-blue-hover); font-weight: 500; display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                    Abrir Vacantes
                </span>
            </div>
        </div>
    </a>

</div>
