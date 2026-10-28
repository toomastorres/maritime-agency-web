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
        $stmt = $pdo->query("SELECT ip_address, detalles, creado_en FROM admin_audit_logs WHERE accion = 'login_fallido' AND creado_en > DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY creado_en DESC LIMIT 5");
        $failedLoginDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (\PDOException $e) { /* tabla puede no existir aún */ }

// ── 3. KPIs ─────────────────────────────────────────────────────
$stats = [
    'usuarios'    => 0,
    'vacantes'    => 0
];
try {
    $stats['usuarios']    = (int)$pdo->query("SELECT COUNT(*) FROM usuarios_admin WHERE is_active = 1")->fetchColumn();
    $stats['vacantes']    = (int)$pdo->query("SELECT COUNT(*) FROM vacancies WHERE activo = 1")->fetchColumn();
    $stats['postulantes'] = (int)$pdo->query("SELECT COUNT(*) FROM applicants WHERE estado = 'nuevo'")->fetchColumn();
} catch (\PDOException $e) { /* silencioso */ }

// ── 4. Actividad Reciente ───────────────────────────────────────
$recentActivity = [];
try {
    $stmt = $pdo->query("SELECT a.*, u.nombre AS autor FROM admin_audit_logs a LEFT JOIN usuarios_admin u ON a.user_id = u.id ORDER BY a.creado_en DESC LIMIT 6");
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
.status-indicator { display:flex; align-items:center; gap:14px; padding:22px 24px; border-radius:0; border:1px solid var(--admin-border-light); background:#fff; }
.status-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.status-dot--green { background:#0069CC; box-shadow:none; }
.status-dot--yellow { background:#8a5a00; box-shadow:none; }
.status-dot--red { background:var(--admin-alert); box-shadow:none; }
.status-label { font-weight:500; font-size:16px; color:var(--admin-text); }
.status-detail { font-size:14px; color:var(--admin-text-muted); margin-top:2px; }
.home-log-viewer { background:#1e1e1e; color:#d4d4d4; font-family:'Consolas','Monaco',monospace; font-size:0.8rem; padding:0.75rem; border-radius:0; max-height:200px; overflow-y:auto; white-space:pre-wrap; word-break:break-all; border:1px solid #333; }
.home-log-line { display:block; margin-bottom:2px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:2px; }
.home-log-error { color:#f87171; font-weight:bold; }
.home-log-warning { color:#fbbf24; }
</style>

<div style="margin-bottom:32px;">
    <h1 class="admin-page-title">Panel de Control</h1>
    <p style="margin:8px 0 0; color:var(--admin-text); font-size:16px;">
        Estado general del sitio, alertas y actividad reciente.
    </p>
</div>

<!-- Sección 1: Estado del Sistema -->
<div class="admin-status-grid">
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
<div class="admin-card">
    <div class="admin-card-header">
        <span class="material-symbols-outlined">shield</span>
        <h2 class="admin-card-title">Alertas de Seguridad (24h)</h2>
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
        <div class="admin-table-wrapper">
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
                        <td class="is-sm is-nowrap"><?php echo date('d/m/Y H:i:s', strtotime($fail['creado_en'])); ?></td>
                        <td style="font-family:monospace; font-size:0.85rem;"><?php echo htmlspecialchars($fail['ip_address']); ?></td>
                        <td class="admin-meta"><?php echo htmlspecialchars($fail['detalles'] ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Sección 3: KPIs Rápidos -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-card__top">
            <div>
                <p class="admin-stat-label">Usuarios activos</p>
                <p class="admin-stat-value"><?php echo $stats['usuarios']; ?></p>
            </div>
            <div class="admin-stat-icon"><span class="material-symbols-outlined">group</span></div>
        </div>
        <a href="?section=usuarios" class="admin-stat-link">Gestionar usuarios &rarr;</a>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-card__top">
            <div>
                <p class="admin-stat-label">Vacantes visibles</p>
                <p class="admin-stat-value"><?php echo $stats['vacantes']; ?></p>
            </div>
            <div class="admin-stat-icon"><span class="material-symbols-outlined">work</span></div>
        </div>
        <a href="?section=rrhh_vacantes" class="admin-stat-link">Gestionar vacantes &rarr;</a>
    </div>

    <!-- Postulaciones sin revisar: es lo único del panel que se acumula solo,
         sin que nadie del equipo haga nada, así que merece estar a la vista. -->
    <div class="admin-stat-card">
        <div class="admin-stat-card__top">
            <div>
                <p class="admin-stat-label">Postulaciones sin revisar</p>
                <p class="admin-stat-value"><?php echo $stats['postulantes']; ?></p>
            </div>
            <div class="admin-stat-icon"><span class="material-symbols-outlined">contact_page</span></div>
        </div>
        <a href="?section=rrhh_postulantes" class="admin-stat-link">Ver postulaciones &rarr;</a>
    </div>
</div>

<!-- Acciones Rápidas -->
<!-- Fila suelta, no tarjeta: tres botones no son contenido y pesaban
     visualmente lo mismo que un panel de datos. -->
<div class="admin-quick-actions">
    <div class="admin-quick-actions__inner">
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
<div class="admin-card">
    <div class="admin-card-header">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <span class="material-symbols-outlined">history</span>
            <h2 class="admin-card-title">Actividad Reciente</h2>
        </div>
        <a href="?section=actividad" style="font-size:0.85rem; color:var(--admin-gold); text-decoration:none;">Ver historial completo &rarr;</a>
    </div>

    <div class="admin-table-wrapper">
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
                    <td class="is-sm is-nowrap"><?php echo date('d/m/Y H:i', strtotime($log['creado_en'])); ?></td>
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

<!-- Sección 5: Errores del Servidor ─────────────────────────────
     El volcado crudo del log (terminal negra con stack traces) vivía acá y
     además en Salud del Sistema, duplicado. En el panel de inicio no es
     accionable —a un operador no le dice qué hacer— y era lo más ruidoso de
     la pantalla. Queda el aviso de que hay errores, con el enlace al detalle
     en la sección donde corresponde. -->
<?php if (!empty($appErrors) || !empty($recentErrors)): ?>
<?php $totalErrores = count($appErrors ?? []) + count($recentErrors ?? []); ?>
<div class="admin-card">
    <div class="admin-card-header">
        <span class="material-symbols-outlined">bug_report</span>
        <h2 class="admin-card-title">Errores recientes del servidor</h2>
    </div>
    <div class="admin-card-note admin-card-note--warn">
        <span class="material-symbols-outlined">warning</span>
        <span>
            Hay <strong><?php echo $totalErrores; ?></strong> línea<?php echo $totalErrores === 1 ? '' : 's'; ?>
            de error en los registros del servidor. El detalle completo está en
            <a href="?section=mantenimiento" class="admin-link">Salud del Sistema</a>.
        </span>
    </div>
</div>
<?php endif; ?>
