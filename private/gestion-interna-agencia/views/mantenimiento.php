<?php
/**
 * AMA — mantenimiento.php (Admin)
 * Panel de Control del Sistema (System Health, Logs & Mantenimiento).
 * Solo accesible para admin_global.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_role('admin_global');
require_once dirname(__DIR__, 3) . '/src/config/config.php';
require_once dirname(__DIR__, 3) . '/src/core/database.php';

$appLogPath = ROOT_DIR . 'src/data/logs/app.log';
$maintenanceFile = ROOT_DIR . '.maintenance';
$actionMensaje = '';
$actionError = '';

// ─── PROCESAR ACCIONES ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $actionError = 'Token CSRF inválido.';
    } else {
        $action = $_POST['action'];

        if ($action === 'clear_app_log') {
            if (file_exists($appLogPath)) {
                file_put_contents($appLogPath, "--- LOG Vaciado por Admin el " . date('Y-m-d H:i:s') . " ---\n");
                $actionMensaje = "El log de la aplicación ha sido vaciado.";
                // Audit log
                try {
                    $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                        ->execute([$_SESSION['user_id'], 'clear_app_log', 'app.log vaciado manualmente', $_SERVER['REMOTE_ADDR']]);
                } catch (\PDOException $e) { /* audit no-crítico */ }
            } else {
                $actionError = "El archivo de log no existe.";
            }
        } elseif ($action === 'toggle_maintenance') {
            if (file_exists($maintenanceFile)) {
                unlink($maintenanceFile);
                $actionMensaje = "Modo Mantenimiento DESACTIVADO. El sitio público vuelve a estar online.";
                $maintState = 'OFF';
            } else {
                file_put_contents($maintenanceFile, "Maintenance mode active");
                $actionMensaje = "Modo Mantenimiento ACTIVADO. Los visitantes verán una pantalla de mantenimiento.";
                $maintState = 'ON';
            }
            // Audit log
            try {
                $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                    ->execute([$_SESSION['user_id'], 'toggle_maintenance', "Modo mantenimiento: {$maintState}", $_SERVER['REMOTE_ADDR']]);
            } catch (\PDOException $e) { /* audit no-crítico */ }
        }
    }
}

// ─── OBTENER DATOS DEL ENTORNO ────────────────────────────────────────

// 1. Salud del Servidor
$serverInfo = [
    'PHP Version' => phpversion(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
    'Zend Version' => zend_version(),
    'Memoria Límite' => ini_get('memory_limit'),
    'Max Upload Size' => ini_get('upload_max_filesize'),
    'Max Post Size' => ini_get('post_max_size'),
    'Max Execution Time' => ini_get('max_execution_time') . 's'
];

// 2. Almacenamiento
$dirDocs = ROOT_DIR . 'public/assets/docs';
$dirUploads = ROOT_DIR . 'public/uploads';

$sizeDocs = function_exists('get_dir_size') ? get_dir_size($dirDocs) : 0;
$sizeUploads = function_exists('get_dir_size') ? get_dir_size($dirUploads) : 0;

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}

// 3. Auditoría de Seguridad (Últimos 50)
$auditLogs = [];
try {
    $stmt = $pdo->query("SELECT a.*, u.nombre AS user_nombre, u.email AS user_email FROM admin_audit_logs a LEFT JOIN usuarios_admin u ON a.user_id = u.id ORDER BY a.creado_en DESC LIMIT 50");
    $auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $auditLogs = [['creado_en' => date('Y-m-d H:i:s'), 'accion' => 'Error', 'user_nombre' => '-', 'user_email' => '-', 'ip_address' => '-', 'detalles' => 'Tabla admin_audit_logs no encontrada.']];
}

// 3b. Estadísticas de seguridad y cuentas (solo lectura)
$secStats = [
    'users_total' => 0, 'users_active' => 0, 'users_blocked' => 0,
    'by_role' => [], 'fails_24h' => 0, 'top_fail_ip' => null, 'db_size_mb' => null,
];
try {
    $secStats['users_total']   = (int) $pdo->query("SELECT COUNT(*) FROM usuarios_admin")->fetchColumn();
    $secStats['users_active']  = (int) $pdo->query("SELECT COUNT(*) FROM usuarios_admin WHERE is_active = 1")->fetchColumn();
    $secStats['users_blocked'] = $secStats['users_total'] - $secStats['users_active'];
    foreach ($pdo->query("SELECT rol_portal, COUNT(*) c FROM usuarios_admin GROUP BY rol_portal")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $secStats['by_role'][$r['rol_portal']] = (int) $r['c'];
    }
} catch (\PDOException $e) {}
try {
    $secStats['fails_24h'] = (int) $pdo->query("SELECT COUNT(*) FROM admin_audit_logs WHERE accion = 'login_fallido' AND creado_en > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    $topIp = $pdo->query("SELECT ip_address, COUNT(*) c FROM admin_audit_logs WHERE accion = 'login_fallido' AND creado_en > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY ip_address ORDER BY c DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($topIp) $secStats['top_fail_ip'] = $topIp['ip_address'] . ' (' . (int) $topIp['c'] . ' intentos)';
} catch (\PDOException $e) {}
try {
    $dbRow = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) mb FROM information_schema.tables WHERE table_schema = DATABASE()")->fetch(PDO::FETCH_ASSOC);
    if ($dbRow) $secStats['db_size_mb'] = $dbRow['mb'];
} catch (\PDOException $e) {}

$rolesDictPanel = ['admin_global' => 'Admin Global', 'caja' => 'Caja', 'cs' => 'CS', 'rrhh' => 'RRHH', 'documentacion' => 'Docs'];

// 5. Email Audit Log (JSONL)
$emailAuditEntries = [];
try {
    require_once dirname(__DIR__, 3) . '/src/core/email.php';
    $emailService = new EmailService();
    $emailAuditEntries = $emailService->getAuditTail(30);
} catch (\Exception $e) {
    // EmailService not available or audit file missing
}

// 4. Logs de Aplicación
$appLogLines = function_exists('tail_file') ? tail_file($appLogPath, 50) : ["Función tail_file no disponible."];

// PHP Error Log (si estuviera accesible por configuración php.ini)
$phpErrorLogPath = ini_get('error_log');
$phpErrorLines = [];
if ($phpErrorLogPath && file_exists($phpErrorLogPath)) {
    $phpErrorLines = function_exists('tail_file') ? tail_file($phpErrorLogPath, 50) : ["Función tail_file no disponible."];
} else {
    $phpErrorLines = ["La ruta del error_log de PHP no está configurada o el archivo no es accesible vía web.", "Ruta detectada: " . ($phpErrorLogPath ?: 'Ninguna')];
}

// Estado del Mantenimiento
$isMaintenanceOn = file_exists($maintenanceFile);
?>

<!-- Estilos específicos para terminal y logs -->
<style>
.log-viewer {
    background: #1e1e1e;
    color: #d4d4d4;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 0.85rem;
    padding: 1rem;
    border-radius: 0;
    height: 300px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
    border: 1px solid #333;
}
.log-line { margin-bottom: 4px; display: block; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 2px;}
.log-error { color: #f87171; font-weight: bold; }
.log-warning { color: #fbbf24; }
.log-info { color: #60a5fa; }
</style>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Mantenimiento y Salud del Sistema</h1>
        <p class="admin-page-sub admin-page-sub--ink">
            Diagnóstico profundo, auditoría y control de variables del servidor.
        </p>
    </div>
    
    <!-- Botón Mantenimiento -->
    <form method="POST" action="" style="margin: 0;">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="action" value="toggle_maintenance">
        <?php if ($isMaintenanceOn): ?>
            <button type="submit" class="btn-admin-danger" onclick="return confirm('¿Seguro que deseas DESACTIVAR el modo mantenimiento y que la web vuelva a verse pública?');">
                <span class="material-symbols-outlined">public_off</span>
                Sitio en Mantenimiento (Desactivar)
            </button>
        <?php else: ?>
            <button type="submit" class="btn-admin-warning" onclick="return confirm('¿Seguro que deseas ACTIVAR el modo mantenimiento? Nadie podrá ver la web pública temporalmente (nosotros sí a este panel interno).');">
                <span class="material-symbols-outlined">construction</span>
                Activar Modo Mantenimiento
            </button>
        <?php endif; ?>
    </form>
</div>

<?php if ($actionMensaje): ?>
    <div class="admin-alert admin-alert-success"><span class="material-symbols-outlined">check_circle</span> <?php echo $actionMensaje; ?></div>
<?php endif; ?>
<?php if ($actionError): ?>
    <div class="admin-alert admin-alert-error"><span class="material-symbols-outlined">error</span> <?php echo $actionError; ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    
    <!-- Tarjeta: Salud del Servidor -->
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="material-symbols-outlined">dns</span>
            <h2 class="admin-card-title">Entorno PHP & Servidor</h2>
        </div>
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <tbody>
                    <?php foreach ($serverInfo as $key => $val): ?>
                    <tr>
                        <th style="width: 40%; text-align:left;"><?php echo htmlspecialchars($key); ?></th>
                        <td style="font-family: monospace; color: var(--admin-blue);"><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tarjeta: Almacenamiento -->
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="material-symbols-outlined">storage</span>
            <h2 class="admin-card-title">Espacio en Disco (Archivos)</h2>
        </div>
        
        <div style="padding: 1rem; border: 1px solid var(--admin-border-light); border-radius:0; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span class="is-medium">/public/uploads</span>
                <span class="is-mono">(Novedades, Imágenes)</span>
            </div>
            <div class="admin-figure">
                <?php echo formatBytes($sizeUploads); ?>
            </div>
        </div>

        <div style="padding: 1rem; border: 1px solid var(--admin-border-light); border-radius:0;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span class="is-medium">/public/assets/docs</span>
                <span class="is-mono">(Documentación Oficial)</span>
            </div>
            <div class="admin-figure">
                <?php echo formatBytes($sizeDocs); ?>
            </div>
        </div>
    </div>
</div>

<!-- Tarjeta: Resumen de Seguridad -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="material-symbols-outlined">shield</span>
        <h2 class="admin-card-title">Resumen de Seguridad y Cuentas</h2>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; padding: 0.5rem 0;">

        <div class="admin-box">
            <div class="admin-meta admin-meta--mb">Usuarios del portal</div>
            <div class="admin-figure"><?php echo $secStats['users_total']; ?></div>
            <div class="form-hint">
                <span class="badge badge-green"><?php echo $secStats['users_active']; ?> activos</span>
                <?php if ($secStats['users_blocked'] > 0): ?>
                    <span class="badge badge-red"><?php echo $secStats['users_blocked']; ?> bloqueados</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-box">
            <div class="admin-meta admin-meta--mb">Logins fallidos (24 h)</div>
            <div style="font-size:1.6rem; font-weight:700; color:<?php echo $secStats['fails_24h'] >= 10 ? 'var(--admin-alert)' : 'var(--admin-blue-hover)'; ?>;"><?php echo $secStats['fails_24h']; ?></div>
            <div class="form-hint">
                <?php echo $secStats['top_fail_ip'] ? 'IP más activa: ' . htmlspecialchars($secStats['top_fail_ip']) : 'Sin intentos recientes'; ?>
            </div>
        </div>

        <div class="admin-box">
            <div class="admin-meta admin-meta--mb">Tamaño de la base de datos</div>
            <div style="font-size:1.6rem; font-weight:700; color:#8b5cf6;"><?php echo $secStats['db_size_mb'] !== null ? htmlspecialchars($secStats['db_size_mb']) . ' MB' : '—'; ?></div>
            <div class="form-hint">Datos + índices</div>
        </div>

        <div class="admin-box">
            <div style="font-size:0.85rem; color:var(--admin-text-muted); margin-bottom:0.5rem;">Cuentas por rol</div>
            <?php if (empty($secStats['by_role'])): ?>
                <div class="admin-meta">Sin datos</div>
            <?php else: foreach ($secStats['by_role'] as $rol => $c): ?>
                <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.2rem;">
                    <span><?php echo htmlspecialchars($rolesDictPanel[$rol] ?? $rol); ?></span>
                    <strong><?php echo (int) $c; ?></strong>
                </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<!-- Tarjeta: Auditoría Completa del Sistema -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="material-symbols-outlined">security</span>
        <h2 class="admin-card-title">Auditoría del Sistema (Últimas 50 acciones)</h2>
    </div>
    <div class="admin-table-wrapper" style="max-height: 400px; overflow-y: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Acción</th>
                    <th>Usuario</th>
                    <th>IP Address</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditLogs as $log):
                    $accion = strtolower($log['accion'] ?? '');
                    $badgeClass = 'badge-green';
                    if (strpos($accion, 'fail') !== false || strpos($accion, 'falli') !== false || strpos($accion, 'error') !== false) {
                        $badgeClass = 'badge-red';
                    } elseif (strpos($accion, 'delete') !== false || strpos($accion, 'clear') !== false) {
                        $badgeClass = 'badge-red';
                    } elseif (strpos($accion, 'toggle') !== false || strpos($accion, 'update') !== false) {
                        $badgeClass = 'badge-gold';
                    } elseif (strpos($accion, 'create') !== false || strpos($accion, 'subido') !== false || strpos($accion, 'upload') !== false) {
                        $badgeClass = 'badge-blue';
                    }
                    $userName = $log['user_nombre'] ?? $log['user_email'] ?? 'Sistema';
                ?>
                <tr>
                    <td style="white-space: nowrap; font-size:0.85rem;"><?php echo date('d/m/Y H:i:s', strtotime($log['creado_en'])); ?></td>
                    <td>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars($log['accion']); ?>
                        </span>
                    </td>
                    <td><strong><?php echo htmlspecialchars($userName); ?></strong></td>
                    <td style="font-family: monospace; font-size:0.85rem;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    <td style="font-size: 0.85rem; color: var(--admin-text-muted);"><?php echo htmlspecialchars($log['detalles'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($auditLogs)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">No hay registros de auditoría aún.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tarjeta: Auditoría de Emails Enviados -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="material-symbols-outlined">mail</span>
        <h2 class="admin-card-title">Auditoría de Emails — Últimos 30 envíos</h2>
    </div>
    <?php if (empty($emailAuditEntries)): ?>
        <p style="padding:1rem; color: var(--admin-text-muted);">No hay envíos de email registrados aún. Los envíos aparecerán aquí tras el primer uso.</p>
    <?php else: ?>
    <div class="admin-table-wrapper" style="max-height: 350px; overflow-y: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Canal</th>
                    <th>Destinatario</th>
                    <th>Asunto</th>
                    <th>Estado</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emailAuditEntries as $entry): ?>
                <tr>
                    <td class="is-sm is-nowrap"><?php echo htmlspecialchars(substr($entry['timestamp'] ?? '', 0, 19)); ?></td>
                    <td><span class="badge badge-blue"><?php echo htmlspecialchars($entry['channel'] ?? '—'); ?></span></td>
                    <td class="is-sm"><?php echo htmlspecialchars($entry['recipient'] ?? ''); ?></td>
                    <td class="is-sm"><?php echo htmlspecialchars(mb_substr($entry['subject'] ?? '', 0, 60)); ?></td>
                    <td>
                        <?php if (($entry['status'] ?? '') === 'success'): ?>
                            <span class="badge badge-green">OK</span>
                        <?php else: ?>
                            <span class="badge badge-red">Error</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem; color:#888; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?php echo htmlspecialchars(mb_substr($entry['error'] ?? '', 0, 120)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Tarjeta: Logs de Sistema -->
<div class="admin-card">
    <div class="admin-card-header">
        <div style="display:flex; align-items:center; gap: 0.5rem;">
            <span class="material-symbols-outlined">terminal</span>
            <h2 class="admin-card-title">Logs Nativos del Sistema (app.log)</h2>
        </div>
        <form method="POST" action="" onsubmit="return confirm('¿Seguro que deseas vaciar el archivo de log? Esta acción es irreversible.');" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="clear_app_log">
            <button type="submit" class="btn-admin-danger" style="padding: 0.3rem 0.75rem; font-size: 0.8rem;">
                <span class="material-symbols-outlined" style="font-size: 1rem;">delete</span> Vaciar Log
            </button>
        </form>
    </div>
    
    <div class="log-viewer">
        <?php
        if (empty($appLogLines)) {
            echo "<span class='log-line log-info'>El archivo está vacío.</span>";
        } else {
            foreach ($appLogLines as $line) {
                $class = "log-line";
                $lLower = strtolower($line);
                if (strpos($lLower, 'error') !== false || strpos($lLower, 'exception') !== false) {
                    $class .= " log-error";
                } elseif (strpos($lLower, 'warning') !== false) {
                    $class .= " log-warning";
                } elseif (strpos($lLower, 'info') !== false) {
                    $class .= " log-info";
                }
                echo "<span class='{$class}'>" . htmlspecialchars($line) . "</span>";
            }
        }
        ?>
    </div>
</div>

<!-- Tarjeta: PHP Error Log -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <div class="admin-card-header">
        <div style="display:flex; align-items:center; gap: 0.5rem;">
            <span class="material-symbols-outlined">bug_report</span>
            <h2 class="admin-card-title">PHP Error Log (error_log del servidor)</h2>
        </div>
    </div>
    <div class="log-viewer">
        <?php
        if (empty($phpErrorLines)) {
            echo "<span class='log-line log-info'>No hay errores PHP registrados o el archivo no es accesible.</span>";
        } else {
            foreach ($phpErrorLines as $line) {
                $class = "log-line";
                $lLower = strtolower($line);
                if (strpos($lLower, 'fatal') !== false || strpos($lLower, 'error') !== false) {
                    $class .= " log-error";
                } elseif (strpos($lLower, 'warning') !== false || strpos($lLower, 'deprecated') !== false) {
                    $class .= " log-warning";
                } elseif (strpos($lLower, 'notice') !== false || strpos($lLower, 'info') !== false) {
                    $class .= " log-info";
                }
                echo "<span class='{$class}'>" . htmlspecialchars($line) . "</span>";
            }
        }
        ?>
    </div>
</div>
