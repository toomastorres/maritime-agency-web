<?php
/**
 * AMA — usuarios.php (Admin)
 * Gestión ABM de Accesos y Usuarios del Portal Administrativo.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_role('admin_global');
require_once dirname(__DIR__, 3) . '/src/core/database.php';
require_once dirname(__DIR__, 3) . '/src/core/entra_oidc.php';

// Con Entra activo no se invitan usuarios: se crean solos al primer ingreso.
$entraOn = EntraOidc::isConfigured();

$actionMensaje = '';
$actionError = '';

// ── Procesar Bloqueo/Desbloqueo ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $actionError = 'Token CSRF inválido.';
    } else {
        $target_id = (int)($_POST['user_id'] ?? 0);
        
        // Evitar que el admin se bloquee a sí mismo
        if ($target_id === (int)$_SESSION['user_id']) {
            $actionError = 'Por seguridad, no podés bloquear tu propia cuenta.';
        } else {
            try {
                // Obtener estado actual
                $stmt = $pdo->prepare("SELECT is_active, nombre FROM usuarios_admin WHERE id = ?");
                $stmt->execute([$target_id]);
                $userTarget = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userTarget) {
                    $new_status = ((int)$userTarget['is_active'] === 1) ? 0 : 1;
                    $update = $pdo->prepare("UPDATE usuarios_admin SET is_active = ? WHERE id = ?");
                    $update->execute([$new_status, $target_id]);
                    
                    $statusText = $new_status ? 'activada' : 'bloqueada';
                    $actionMensaje = "La cuenta de {$userTarget['nombre']} ha sido {$statusText} correctamente.";
                    
                    // Auditoría
                    $logStmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
                    $logStmt->execute([
                        $_SESSION['user_id'], 
                        $new_status ? 'user_unblock' : 'user_block', 
                        "Cambió estado de ID $target_id a $new_status", 
                        $_SERVER['REMOTE_ADDR']
                    ]);
                } else {
                    $actionError = 'El usuario no existe.';
                }
            } catch (\PDOException $e) {
                error_log('AMA usuarios toggle_status error: ' . $e->getMessage());
                $actionError = 'Error al actualizar el estado de la cuenta. Intente nuevamente.';
            }
        }
    }
}

// ── Listar Usuarios ──
$usuarios = [];
$tableError = '';
try {
    // Detectar columnas disponibles para compatibilidad con BD sin migrar
    $existingCols = [];
    $colStmt = $pdo->query("SHOW COLUMNS FROM usuarios_admin");
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $existingCols[] = $col['Field'];
    }

    // Columnas base siempre presentes
    $selectCols = ['id', 'nombre', 'email', 'rol_portal', 'creado_en'];
    // Columnas opcionales (agregadas por migración)
    foreach (['username', 'ultimo_login', 'is_active', 'password_updated_at', 'auth_provider', 'is_break_glass'] as $optCol) {
        if (in_array($optCol, $existingCols)) {
            $selectCols[] = $optCol;
        }
    }

    $stmt = $pdo->query("SELECT " . implode(', ', $selectCols) . " FROM usuarios_admin ORDER BY id ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log("AMA usuarios list error: " . $e->getMessage());
    $tableError = "Error al cargar la lista de usuarios. Revise los logs del servidor.";
}

// Helper diccionario roles formales
$rolesDict = [
    'admin_global' => 'Administrador Global',
    'caja' => 'Caja / Finanzas',
    'cs' => 'Customer Service',
    'rrhh' => 'Recursos Humanos',
    'documentacion' => 'Documentación'
];
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Usuarios del Portal</h1>
        <p class="admin-page-sub admin-page-sub--ink">
            Administración de accesos, roles y auditoría de cuentas internas.
        </p>
    </div>
    <?php if (!$entraOn): ?>
        <a href="?section=usuarios_form" class="btn-admin-primary">
            <span class="material-symbols-outlined">person_add</span>
            Invitar Usuario
        </a>
    <?php else: ?>
        <div style="text-align:right; max-width:280px;">
            <p style="margin:0; font-size:.82rem; color:var(--admin-text-muted); line-height:1.55;">
                <span class="material-symbols-outlined" style="font-size:1rem; vertical-align:middle; color:#0069CC;">badge</span>
                Las cuentas se crean solas al primer ingreso con Microsoft.
                Los accesos se otorgan en <strong>Entra ID</strong>.
            </p>
        </div>
    <?php endif; ?>
</div>

<?php if ($actionMensaje): ?>
    <div class="admin-alert admin-alert-success"><span class="material-symbols-outlined">check_circle</span> <?php echo $actionMensaje; ?></div>
<?php endif; ?>
<?php if ($actionError): ?>
    <div class="admin-alert admin-alert-error"><span class="material-symbols-outlined">error</span> <?php echo $actionError; ?></div>
<?php endif; ?>
<?php if ($tableError): ?>
    <div class="admin-alert admin-alert-error"><span class="material-symbols-outlined">error</span> <?php echo htmlspecialchars($tableError); ?></div>
<?php endif; ?>

<?php
// Flags de columnas disponibles para renderizado condicional
$hasUsername = !empty($usuarios) && array_key_exists('username', $usuarios[0]);
$hasIsActive = !empty($usuarios) && array_key_exists('is_active', $usuarios[0]);
$hasUltimoLogin = !empty($usuarios) && array_key_exists('ultimo_login', $usuarios[0]);
$hasPasswordUpdated = !empty($usuarios) && array_key_exists('password_updated_at', $usuarios[0]);
$hasAuthProvider = !empty($usuarios) && array_key_exists('auth_provider', $usuarios[0]);
// Con la migración aplicada, "Acceso" reemplaza a "Cambio de Clave": con Entra
// la fecha de cambio de contraseña ya no significa nada para casi ninguna cuenta.
$mostrarClave = $hasPasswordUpdated && !$hasAuthProvider;
?>

<div class="admin-card">
    <div class="admin-table-wrapper" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nombre y Rol</th>
                    <th>Email Corporativo</th>
                    <?php if ($hasIsActive): ?><th>Estado</th><?php endif; ?>
                    <?php if ($hasUltimoLogin): ?><th>Último Ingreso</th><?php endif; ?>
                    <?php if ($hasAuthProvider): ?><th>Acceso</th><?php endif; ?>
                    <?php if ($mostrarClave): ?><th>Cambio de Clave</th><?php endif; ?>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u):
                    $isActive = $hasIsActive ? ((int)($u['is_active'] ?? 1) === 1) : true;
                    $isSelf = (int)$u['id'] === (int)$_SESSION['user_id'];
                ?>
                <tr>
                    <td>
                        <strong style="display:block; color: var(--admin-text);"><?php echo htmlspecialchars($u['nombre']); ?></strong>
                        <span style="font-size:0.8rem; color: var(--admin-text-muted);"><?php if ($hasUsername): ?>@<?php echo htmlspecialchars($u['username']); ?> • <?php endif; ?><?php echo $rolesDict[$u['rol_portal']] ?? $u['rol_portal']; ?></span>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($u['email']); ?>
                    </td>
                    <?php if ($hasIsActive): ?>
                    <td>
                        <?php if ($isActive): ?>
                            <span class="badge badge-green">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-red">Bloqueado</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($hasUltimoLogin): ?>
                    <td class="admin-meta">
                        <?php echo ($u['ultimo_login'] ?? null) ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : 'Nunca'; ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($hasAuthProvider): ?>
                    <td class="is-sm">
                        <?php if ((int)($u['is_break_glass'] ?? 0) === 1): ?>
                            <span class="badge" style="background:rgba(138,90,0,.12); color:#8A5A00; border:1px solid #8A5A00;"
                                  title="Cuenta de emergencia: conserva contraseña local">Emergencia</span>
                        <?php elseif (($u['auth_provider'] ?? '') === 'entra'): ?>
                            <span class="badge badge-blue" title="Ingresa con su cuenta de Microsoft 365">Microsoft</span>
                        <?php else: ?>
                            <span class="badge" style="background:rgba(128,128,131,.14); color:var(--admin-text-muted); border:1px solid rgba(128,128,131,.45);"
                                  title="Todavía tiene contraseña local. Se convierte a Microsoft en su próximo ingreso">Contraseña</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($mostrarClave): ?>
                    <td class="admin-meta">
                        <?php echo ($u['password_updated_at'] ?? null) ? date('d/m/Y H:i', strtotime($u['password_updated_at'])) : 'No registra'; ?>
                    </td>
                    <?php endif; ?>
                    <td style="text-align: right; white-space: nowrap;">
                        <!-- Botón Editar -->
                        <a href="?section=usuarios_form&id=<?php echo $u['id']; ?>" class="btn-admin-secondary btn-admin-sm"  title="Editar">
                            <span class="material-symbols-outlined is-lg" >edit</span>
                        </a>

                        <!-- Formulario Bloquear/Desbloquear -->
                        <?php if (!$isSelf && $hasIsActive): ?>
                        <form method="POST" action="" style="display:inline-block; margin:0;" onsubmit="return confirm('¿Seguro que deseas cambiar el estado de acceso de este usuario?');">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">

                            <?php if ($isActive): ?>
                                <button type="submit" class="btn-admin-danger btn-admin-sm"  title="Bloquear Acceso">
                                    <span class="material-symbols-outlined is-lg" >block</span>
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-admin-primary btn-admin-sm"  title="Reactivar Acceso">
                                    <span class="material-symbols-outlined is-lg" >verified_user</span>
                                </button>
                            <?php endif; ?>
                        </form>
                        <?php elseif ($isSelf): ?>
                            <span style="display:inline-block; width: 32px; text-align:center;">
                                <span class="material-symbols-outlined" style="font-size: 1.1rem; color: var(--admin-border-light);" title="No podés bloquearte a vos mismo">lock</span>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($usuarios) && !$tableError): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">No hay usuarios registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
