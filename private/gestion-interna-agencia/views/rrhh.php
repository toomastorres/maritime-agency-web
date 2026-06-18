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

<div style="padding: 0.5rem 0 1.5rem;">
    <h2 style="margin: 0 0 0.35rem; font-size: 1.4rem; color: var(--admin-blue);">
        <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 1.5rem; margin-right: 0.3rem;">group</span>
        Recursos Humanos
    </h2>
    <p style="margin: 0; color: var(--admin-text-muted); font-size: 0.92rem;">
        Desde aquí podés gestionar las búsquedas laborales activas y el directorio de contactos de la agencia.
    </p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">

    <!-- Card: Vacantes -->
    <a href="?section=rrhh_vacantes" style="text-decoration: none; color: inherit;">
        <div class="admin-card" style="cursor: pointer; transition: transform 0.18s, box-shadow 0.18s; border-left: 4px solid var(--admin-blue);"
             onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(0,51,160,0.13)';"
             onmouseout="this.style.transform=''; this.style.boxShadow='';">
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(0,51,160,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="material-symbols-outlined" style="font-size: 1.75rem; color: var(--admin-blue);">work</span>
                </div>
                <div>
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--admin-text);">Vacantes</div>
                    <div style="font-size: 0.82rem; color: var(--admin-text-muted); margin-top: 0.2rem;">
                        Gestión de búsquedas laborales activas y archivadas
                    </div>
                </div>
            </div>
            <div style="padding: 0.75rem 1.5rem 1.25rem; border-top: 1px solid var(--admin-border);">
                <span style="font-size: 0.82rem; color: var(--admin-blue); font-weight: 500; display: flex; align-items: center; gap: 0.3rem;">
                    <span class="material-symbols-outlined" style="font-size: 1rem;">arrow_forward</span>
                    Abrir Vacantes
                </span>
            </div>
        </div>
    </a>

    <!-- Card: Directorio de Contactos -->
    <a href="?section=rrhh_empleados" style="text-decoration: none; color: inherit;">
        <div class="admin-card" style="cursor: pointer; transition: transform 0.18s, box-shadow 0.18s; border-left: 4px solid var(--admin-gold, #b8962e);"
             onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(184,150,46,0.13)';"
             onmouseout="this.style.transform=''; this.style.boxShadow='';">
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(184,150,46,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="material-symbols-outlined" style="font-size: 1.75rem; color: var(--admin-gold, #b8962e);">badge</span>
                </div>
                <div>
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--admin-text);">Directorio de Contactos</div>
                    <div style="font-size: 0.82rem; color: var(--admin-text-muted); margin-top: 0.2rem;">
                        Alta, baja y modificación de empleados de la agencia
                    </div>
                </div>
            </div>
            <div style="padding: 0.75rem 1.5rem 1.25rem; border-top: 1px solid var(--admin-border);">
                <span style="font-size: 0.82rem; color: var(--admin-gold, #b8962e); font-weight: 500; display: flex; align-items: center; gap: 0.3rem;">
                    <span class="material-symbols-outlined" style="font-size: 1rem;">arrow_forward</span>
                    Abrir Directorio
                </span>
            </div>
        </div>
    </a>

</div>
