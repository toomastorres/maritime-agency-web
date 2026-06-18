<?php
/**
 * AMA — home.php (Admin)
 * Dashboard de inicio: salud del sitio, alertas de seguridad, KPIs y actividad reciente.
 * Solo accesible para admin_global.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_role('admin_global');
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__, 3) . '/src/config/config.php';

// ── 1. Estado del Sistema ───────────────────────────────────────
$dbOk = true;
try { $pdo->query("SELECT 1"); } catch (\Exception $e) { $dbOk = false; }

$smtpFrom = defined('SMTP_FROM') ? SMTP_FROM : '';
$smtpUser = defined('SMTP_USER') ? SMTP_USER : '';
$emailMode = ($smtpUser !== '') ? 'SMTP Auth' : (($smtpFrom !== '') ? 'mail() nativo' : 'No configurado');
$emailOk = $smtpFrom !== '';

$maintenanceFile = ROOT_DIR . '.maintenance';
$isMaintenanceOn = file_exists($maintenanceFile);

$uploadsDir = ROOT_DIR . 'public/uploads';
$docsDir = ROOT_DIR . 'public/assets/docs';
$uploadsSize = 0;
$docsSize = 0;
if (is_dir($uploadsDir)) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) { $uploadsSize += $file->getSize(); }
}
if (is_dir($docsDir)) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($docsDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) { $docsSize += $file->getSize(); }
}
$totalStorageMB = round(($uploadsSize + $docsSize) / 1024 / 1024, 1);

function formatBytesDash($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

// ── 2. Alertas de Seguridad ─────────────────────────────────────
$failedLogins24h = 0;
$failedLoginDetails = [];
try {
    $failedLogins24h = (int)$pdo->query("SELECT COUNT(*) FROM admin_audit_logs WHERE accion = 'login_fallido' AND creado_en > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    if ($failedLogins24h > 0) {
        $stmt = $pdo->query("SELECT ip_address, detalles, creado_en FROM admin_audit_logs WHERE accion = 'login_fallido' AND creado_en > DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY creado_en DESC LIMIT 10");
        $failedLoginDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (\PDOException $e) { /* tabla puede no existir aún */ }

// ── 3. KPIs ─────────────────────────────────────────────────────
$stats = [
    'usuarios'    => 0,
    'empleados'   => 0,
    'vacantes'    => 0
];
try {
    $stats['usuarios']    = (int)$pdo->query("SELECT COUNT(*) FROM usuarios_admin WHERE is_active = 1")->fetchColumn();
    $stats['empleados']   = (int)$pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    $stats['vacantes']    = (int)$pdo->query("SELECT COUNT(*) FROM vacancies WHERE activo = 1")->fetchColumn();
} catch (\PDOException $e) { /* silencioso */ }

// ── 4. Actividad Reciente ───────────────────────────────────────
$recentActivity = [];
try {
    $stmt = $pdo->query("SELECT a.*, u.nombre AS autor FROM admin_audit_logs a LEFT JOIN usuarios_admin u ON a.user_id = u.id ORDER BY a.creado_en DESC LIMIT 15");
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) { /* silencioso */ }

// ── 5. Errores recientes del servidor ───────────────────────────
$phpErrorLogPath = ini_get('error_log');
$recentErrors = [];
if ($phpErrorLogPath && file_exists($phpErrorLogPath) && is_readable($phpErrorLogPath)) {
    $lines = file($phpErrorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentErrors = array_slice($lines, -10);
}
$appLogPath = ROOT_DIR . 'src/data/logs/app.log';
$appErrors = [];
if (file_exists($appLogPath) && is_readable($appLogPath)) {
    $lines = file($appLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $appErrors = array_slice($lines, -10);
}

// Helper color para acciones
function homeBadgeClass($accion) {
    if (strpos($accion, 'login_exitoso') !== false) return 'badge-green';
    if (strpos($accion, 'login_fallido') !== false) return 'badge-red';
    if (strpos($accion, 'create') !== false) return 'badge-blue';
    if (strpos($accion, 'edit') !== false || strpos($accion, 'update') !== false) return 'badge-yellow';
    if (strpos($accion, 'delete') !== false || strpos($accion, 'block') !== false) return 'badge-red';
    if (strpos($accion, 'password') !== false || strpos($accion, 'unblock') !== false) return 'badge-green';
    return 'badge-blue';
}
?>

<style>
.status-indicator { display:flex; align-items:center; gap:0.75rem; padding:1rem; border-radius:8px; border:1px solid var(--admin-border-light); background:#fff; }
.status-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
.status-dot--green { background:#22c55e; box-shadow:0 0 6px rgba(34,197,94,0.4); }
.status-dot--yellow { background:#f59e0b; box-shadow:0 0 6px rgba(245,158,11,0.4); }
.status-dot--red { background:#ef4444; box-shadow:0 0 6px rgba(239,68,68,0.4); }
.status-label { font-weight:600; font-size:0.9rem; color:var(--admin-text); }
.status-detail { font-size:0.8rem; color:var(--admin-text-muted); }
.home-log-viewer { background:#1e1e1e; color:#d4d4d4; font-family:'Consolas','Monaco',monospace; font-size:0.8rem; padding:0.75rem; border-radius:6px; max-height:200px; overflow-y:auto; white-space:pre-wrap; word-break:break-all; border:1px solid #333; }
.home-log-line { display:block; margin-bottom:2px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:2px; }
.home-log-error { color:#f87171; font-weight:bold; }
.home-log-warning { color:#fbbf24; }
</style>

<div style="margin-bottom:1.5rem;">
    <h1 style="margin:0; font-size:1.5rem; color:var(--admin-text);">Panel de Control</h1>
    <p style="margin:0.2rem 0 0; color:var(--admin-text-muted); font-size:0.9rem;">
        Estado general del sitio, alertas y actividad reciente.
    </p>
</div>

<!-- Sección 1: Estado del Sistema -->
<div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; margin-bottom:1.5rem;">
    <!-- BD -->
    <div class="status-indicator">
        <div class="status-dot <?php echo $dbOk ? 'status-dot--green' : 'status-dot--red'; ?>"></div>
        <div>
            <div class="status-label">Base de Datos</div>
            <div class="status-detail"><?php echo $dbOk ? 'Conectada' : 'Sin conexión'; ?></div>
        </div>
    </div>
    <!-- Email -->
    <div class="status-indicator">
        <div class="status-dot <?php echo $emailOk ? 'status-dot--green' : 'status-dot--red'; ?>"></div>
        <div>
            <div class="status-label">Email</div>
            <div class="status-detail"><?php echo htmlspecialchars($emailMode); ?></div>
        </div>
    </div>
    <!-- Mantenimiento -->
    <div class="status-indicator">
        <div class="status-dot <?php echo $isMaintenanceOn ? 'status-dot--yellow' : 'status-dot--green'; ?>"></div>
        <div>
            <div class="status-label">Sitio Público</div>
            <div class="status-detail"><?php echo $isMaintenanceOn ? 'En mantenimiento' : 'Activo y online'; ?></div>
        </div>
    </div>
    <!-- Almacenamiento -->
    <div class="status-indicator">
        <div class="status-dot status-dot--green"></div>
        <div>
            <div class="status-label">Almacenamiento</div>
            <div class="status-detail"><?php echo $totalStorageMB; ?> MB en uso</div>
        </div>
    </div>
</div>

<!-- Sección 2: Alertas de Seguridad -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <div class="admin-card-header">
        <span class="material-symbols-outlined">shield</span>
        <h2 style="margin:0; font-size:1.1rem;">Alertas de Seguridad (24h)</h2>
        <?php if ($failedLogins24h > 0): ?>
            <span class="badge badge-red" style="margin-left:auto;"><?php echo $failedLogins24h; ?> intentos fallidos</span>
        <?php endif; ?>
    </div>

    <?php if ($failedLogins24h === 0): ?>
        <div style="padding:1rem; color:#16a34a; display:flex; align-items:center; gap:0.5rem;">
            <span class="material-symbols-outlined">verified_user</span>
            Sin intentos de acceso sospechosos en las últimas 24 horas.
        </div>
    <?php else: ?>
        <div class="admin-table-wrapper" style="max-height:200px; overflow-y:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>IP</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($failedLoginDetails as $fail): ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:0.85rem;"><?php echo date('d/m/Y H:i:s', strtotime($fail['creado_en'])); ?></td>
                        <td style="font-family:monospace; font-size:0.85rem;"><?php echo htmlspecialchars($fail['ip_address']); ?></td>
                        <td style="font-size:0.85rem; color:var(--admin-text-muted);"><?php echo htmlspecialchars($fail['detalles'] ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Sección 3: KPIs Rápidos -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; margin-bottom:1.5rem;">
    <div class="admin-stat-card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <p style="margin:0 0 0.5rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--admin-text-muted);">Usuarios Activos</p>
                <p class="admin-stat-value"><?php echo $stats['usuarios']; ?></p>
            </div>
            <div class="admin-stat-icon"><span class="material-symbols-outlined">group</span></div>
        </div>
        <a href="?section=usuarios" style="font-size:0.82rem; color:var(--admin-gold); text-decoration:none; margin-top:0.75rem; display:block;">Gestionar Usuarios &rarr;</a>
    </div>

    <div class="admin-stat-card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <p style="margin:0 0 0.5rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--admin-text-muted);">Staff Registrado</p>
                <p class="admin-stat-value"><?php echo $stats['empleados']; ?></p>
            </div>
            <div class="admin-stat-icon"><span class="material-symbols-outlined">badge</span></div>
        </div>
        <a href="?section=rrhh_empleados" style="font-size:0.82rem; color:var(--admin-gold); text-decoration:none; margin-top:0.75rem; display:block;">Ver Directorio &rarr;</a>
    </div>

    <div class="admin-stat-card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <p style="margin:0 0 0.5rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--admin-text-muted);">Vacantes Visibles</p>
                <p class="admin-stat-value"><?php echo $stats['vacantes']; ?></p>
            </div>
            <div class="admin-stat-icon"><span class="material-symbols-outlined">work</span></div>
        </div>
        <a href="?section=rrhh_vacantes" style="font-size:0.82rem; color:var(--admin-gold); text-decoration:none; margin-top:0.75rem; display:block;">Gestionar Vacantes &rarr;</a>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="admin-card" style="margin-bottom:1.5rem; padding:1rem 1.25rem;">
    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <a href="?section=rrhh_empleados_form" class="btn-admin-primary" style="text-decoration:none;">
            <span class="material-symbols-outlined">add</span> Nuevo Empleado
        </a>
        <a href="?section=rrhh_vacantes_form" class="btn-admin-gold" style="text-decoration:none;">
            <span class="material-symbols-outlined">add</span> Nueva Vacante
        </a>
        <a href="?section=usuarios" class="btn-admin-secondary" style="text-decoration:none;">
            <span class="material-symbols-outlined">manage_accounts</span> Gestionar Usuarios
        </a>
        <a href="?section=mantenimiento" class="btn-admin-secondary" style="text-decoration:none;">
            <span class="material-symbols-outlined">settings_suggest</span> Salud del Sistema
        </a>
    </div>
</div>

<!-- Sección 4: Actividad Reciente -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <div class="admin-card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <span class="material-symbols-outlined">history</span>
            <h2 style="margin:0; font-size:1.1rem;">Actividad Reciente</h2>
        </div>
        <a href="?section=actividad" style="font-size:0.85rem; color:var(--admin-gold); text-decoration:none;">Ver historial completo &rarr;</a>
    </div>

    <div class="admin-table-wrapper" style="max-height:350px; overflow-y:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Acción</th>
                    <th>Usuario</th>
                    <th>IP</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentActivity as $log): ?>
                <tr>
                    <td style="white-space:nowrap; font-size:0.85rem;"><?php echo date('d/m/Y H:i', strtotime($log['creado_en'])); ?></td>
                    <td>
                        <span class="badge <?php echo homeBadgeClass($log['accion']); ?>">
                            <?php echo htmlspecialchars($log['accion']); ?>
                        </span>
                    </td>
                    <td><strong><?php echo htmlspecialchars($log['autor'] ?? '—'); ?></strong></td>
                    <td style="font-family:monospace; font-size:0.85rem;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    <td style="font-size:0.85rem; color:var(--admin-text-muted); max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($log['detalles'] ?? ''); ?>">
                        <?php echo htmlspecialchars($log['detalles'] ?? '—'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentActivity)): ?>
                <tr><td colspan="5" style="text-align:center; padding:1.5rem; color:var(--admin-text-muted);">Sin actividad registrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sección 5: Errores del Servidor -->
<?php if (!empty($appErrors) || !empty($recentErrors)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <span class="material-symbols-outlined">bug_report</span>
        <h2 style="margin:0; font-size:1.1rem;">Errores Recientes del Servidor</h2>
    </div>

    <?php if (!empty($appErrors)): ?>
    <p style="font-size:0.8rem; font-weight:600; color:var(--admin-text-muted); margin:0 0 0.5rem; text-transform:uppercase;">App Log (últimas 10 líneas)</p>
    <div class="home-log-viewer" style="margin-bottom:1rem;">
        <?php foreach ($appErrors as $line):
            $cls = 'home-log-line';
            $low = strtolower($line);
            if (strpos($low, 'error') !== false || strpos($low, 'exception') !== false) $cls .= ' home-log-error';
            elseif (strpos($low, 'warning') !== false) $cls .= ' home-log-warning';
        ?>
        <span class="<?php echo $cls; ?>"><?php echo htmlspecialchars($line); ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($recentErrors)): ?>
    <p style="font-size:0.8rem; font-weight:600; color:var(--admin-text-muted); margin:0 0 0.5rem; text-transform:uppercase;">PHP Error Log (últimas 10 líneas)</p>
    <div class="home-log-viewer">
        <?php foreach ($recentErrors as $line):
            $cls = 'home-log-line';
            $low = strtolower($line);
            if (strpos($low, 'fatal') !== false || strpos($low, 'error') !== false) $cls .= ' home-log-error';
            elseif (strpos($low, 'warning') !== false || strpos($low, 'notice') !== false) $cls .= ' home-log-warning';
        ?>
        <span class="<?php echo $cls; ?>"><?php echo htmlspecialchars($line); ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
