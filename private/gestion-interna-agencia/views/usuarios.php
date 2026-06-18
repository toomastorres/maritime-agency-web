<?php
/**
 * AMA — usuarios.php (Admin)
 * Gestión ABM de Accesos y Usuarios del Portal Administrativo.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_role('admin_global');
require_once dirname(__DIR__, 3) . '/src/core/database.php';

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
    foreach (['username', 'ultimo_login', 'is_active', 'password_updated_at'] as $optCol) {
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

<div class="header-actions">
    <div>
        <h1 style="margin:0; font-size:1.5rem; color:var(--admin-text);">Usuarios del Portal</h1>
        <p style="margin: 0.2rem 0 0; color:var(--admin-text-muted); font-size: 0.9rem;">
            Administración de accesos, roles y auditoría de cuentas internas.
        </p>
    </div>
    <a href="?section=usuarios_form" class="btn-admin-primary">
        <span class="material-symbols-outlined">person_add</span>
        Invitar Usuario
    </a>
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
                    <?php if ($hasPasswordUpdated): ?><th>Cambio de Clave</th><?php endif; ?>
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
                    <td style="font-size:0.85rem; color: var(--admin-text-muted);">
                        <?php echo ($u['ultimo_login'] ?? null) ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : 'Nunca'; ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($hasPasswordUpdated): ?>
                    <td style="font-size:0.85rem; color: var(--admin-text-muted);">
                        <?php echo ($u['password_updated_at'] ?? null) ? date('d/m/Y H:i', strtotime($u['password_updated_at'])) : 'No registra'; ?>
                    </td>
                    <?php endif; ?>
                    <td style="text-align: right; white-space: nowrap;">
                        <!-- Botón Editar -->
                        <a href="?section=usuarios_form&id=<?php echo $u['id']; ?>" class="btn-admin-secondary" style="padding: 0.25rem 0.5rem;" title="Editar">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">edit</span>
                        </a>

                        <!-- Formulario Bloquear/Desbloquear -->
                        <?php if (!$isSelf && $hasIsActive): ?>
                        <form method="POST" action="" style="display:inline-block; margin:0;" onsubmit="return confirm('¿Seguro que deseas cambiar el estado de acceso de este usuario?');">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">

                            <?php if ($isActive): ?>
                                <button type="submit" class="btn-admin-danger" style="padding: 0.25rem 0.5rem;" title="Bloquear Acceso">
                                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">block</span>
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-admin-primary" style="padding: 0.25rem 0.5rem; background: #10b981; border: none;" title="Reactivar Acceso">
                                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">verified_user</span>
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
