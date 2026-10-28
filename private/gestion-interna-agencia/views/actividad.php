<?php
/**
 * AMA — actividad.php (Admin)
 * Registro completo de actividad: audit logs con filtros y paginación.
 * Solo accesible para admin_global.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_role('admin_global');
require_once dirname(__DIR__) . '/includes/db.php';

// ── Parámetros de filtro ────────────────────────────────────────
$filterUser   = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
$filterAccion = trim($_GET['accion'] ?? '');
$filterDesde  = trim($_GET['desde'] ?? '');
$filterHasta  = trim($_GET['hasta'] ?? '');
$filterBuscar = trim($_GET['buscar'] ?? '');
$page         = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 25;
$offset       = ($page - 1) * $perPage;

// ── Construir query dinámica ────────────────────────────────────
$where = "1=1";
$params = [];

if ($filterUser !== null) {
    $where .= " AND a.user_id = ?";
    $params[] = $filterUser;
}
if ($filterAccion !== '') {
    $where .= " AND a.accion = ?";
    $params[] = $filterAccion;
}
if ($filterDesde !== '') {
    $where .= " AND a.creado_en >= ?";
    $params[] = $filterDesde . ' 00:00:00';
}
if ($filterHasta !== '') {
    $where .= " AND a.creado_en <= ?";
    $params[] = $filterHasta . ' 23:59:59';
}
if ($filterBuscar !== '') {
    $where .= " AND a.detalles LIKE ?";
    $params[] = '%' . $filterBuscar . '%';
}

// ── Contar total ────────────────────────────────────────────────
$countSql = "SELECT COUNT(*) FROM admin_audit_logs a WHERE $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// ── Obtener registros ───────────────────────────────────────────
$sql = "SELECT a.*, u.nombre AS autor
        FROM admin_audit_logs a
        LEFT JOIN usuarios_admin u ON a.user_id = u.id
        WHERE $where
        ORDER BY a.creado_en DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Obtener listas para filtros ─────────────────────────────────
$usuarios = $pdo->query("SELECT id, nombre, username FROM usuarios_admin ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$acciones = $pdo->query("SELECT DISTINCT accion FROM admin_audit_logs ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);

// ── Helper de color por acción ──────────────────────────────────
function badgeClassForAction($accion) {
    if (strpos($accion, 'login_exitoso') !== false) return 'badge-green';
    if (strpos($accion, 'login_fallido') !== false || strpos($accion, 'block') !== false) return 'badge-red';
    if (strpos($accion, 'create') !== false || strpos($accion, 'novedad_create') !== false) return 'badge-blue';
    if (strpos($accion, 'edit') !== false || strpos($accion, 'update') !== false || strpos($accion, 'profile') !== false) return 'badge-yellow';
    if (strpos($accion, 'delete') !== false) return 'badge-red';
    if (strpos($accion, 'password') !== false) return 'badge-green';
    if (strpos($accion, 'unblock') !== false) return 'badge-green';
    return 'badge-blue';
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Registro de Actividad</h1>
        <p style="margin:8px 0 0; color:var(--admin-text); font-size:16px;">
            Historial completo de acciones realizadas en el portal.
            <span class="badge badge-gray" style="margin-left:0.5rem;"><?php echo number_format($totalRows); ?> registros</span>
        </p>
    </div>
</div>

<!-- Filtros -->
<div class="admin-card" style="margin-bottom:1.5rem; padding:1rem 1.25rem;">
    <form method="GET" action="" style="display:flex; gap:0.75rem; align-items:flex-end; flex-wrap:wrap;">
        <input type="hidden" name="section" value="actividad">

        <div class="admin-toolbar__field">
            <label style="display:block; font-size:0.75rem; font-weight:600; text-transform:uppercase; color:var(--admin-text-muted); margin-bottom:0.25rem;">Usuario</label>
            <select name="user_id" class="admin-select is-full" >
                <option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo $filterUser === (int)$u['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['nombre']); ?> (@<?php echo htmlspecialchars($u['username']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="admin-toolbar__field">
            <label style="display:block; font-size:0.75rem; font-weight:600; text-transform:uppercase; color:var(--admin-text-muted); margin-bottom:0.25rem;">Acción</label>
            <select name="accion" class="admin-select is-full" >
                <option value="">Todas</option>
                <?php foreach ($acciones as $a): ?>
                    <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $filterAccion === $a ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($a); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="is-minw">
            <label style="display:block; font-size:0.75rem; font-weight:600; text-transform:uppercase; color:var(--admin-text-muted); margin-bottom:0.25rem;">Desde</label>
            <input type="date" name="desde" class="admin-input" value="<?php echo htmlspecialchars($filterDesde); ?>" class="is-full">
        </div>

        <div class="is-minw">
            <label style="display:block; font-size:0.75rem; font-weight:600; text-transform:uppercase; color:var(--admin-text-muted); margin-bottom:0.25rem;">Hasta</label>
            <input type="date" name="hasta" class="admin-input" value="<?php echo htmlspecialchars($filterHasta); ?>" class="is-full">
        </div>

        <div class="admin-toolbar__field">
            <label style="display:block; font-size:0.75rem; font-weight:600; text-transform:uppercase; color:var(--admin-text-muted); margin-bottom:0.25rem;">Buscar en detalles</label>
            <input type="text" name="buscar" class="admin-input" placeholder="Texto libre..." value="<?php echo htmlspecialchars($filterBuscar); ?>" class="is-full">
        </div>

        <div style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn-admin-primary is-nowrap" >
                <span class="material-symbols-outlined">filter_alt</span> Filtrar
            </button>
            <a href="?section=actividad" class="btn-admin-secondary is-nowrap" >
                <span class="material-symbols-outlined">filter_alt_off</span> Limpiar
            </a>
        </div>
    </form>
</div>

<!-- Tabla de Registros -->
<div class="admin-card">
    <div class="admin-table-wrapper" style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:160px;">Fecha / Hora</th>
                    <th style="width:180px;">Acción</th>
                    <th>Usuario</th>
                    <th style="width:130px;">IP</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="is-sm is-nowrap">
                        <?php echo date('d/m/Y H:i:s', strtotime($log['creado_en'])); ?>
                    </td>
                    <td>
                        <span class="badge <?php echo badgeClassForAction($log['accion']); ?>">
                            <?php echo htmlspecialchars($log['accion']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($log['autor']): ?>
                            <strong><?php echo htmlspecialchars($log['autor']); ?></strong>
                        <?php else: ?>
                            <span class="is-muted">— Sistema —</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-family:monospace; font-size:0.85rem;">
                        <?php echo htmlspecialchars($log['ip_address']); ?>
                    </td>
                    <td style="font-size:0.85rem; color:var(--admin-text-muted); max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($log['detalles'] ?? ''); ?>">
                        <?php echo htmlspecialchars($log['detalles'] ?? '—'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" class="is-empty-cell">
                        <span class="material-symbols-outlined" style="font-size:2rem; display:block; margin-bottom:0.5rem;">search_off</span>
                        No se encontraron registros con los filtros aplicados.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex; justify-content:center; align-items:center; gap:0.5rem; padding:1rem; border-top:1px solid var(--admin-border-light);">
        <?php
        $queryBase = $_GET;
        unset($queryBase['p']);
        $qs = http_build_query($queryBase);
        ?>
        <?php if ($page > 1): ?>
            <a href="?<?php echo $qs; ?>&p=<?php echo $page - 1; ?>" class="btn-admin-secondary btn-admin-sm" >&laquo; Anterior</a>
        <?php endif; ?>

        <span class="admin-meta">
            Página <?php echo $page; ?> de <?php echo $totalPages; ?>
        </span>

        <?php if ($page < $totalPages): ?>
            <a href="?<?php echo $qs; ?>&p=<?php echo $page + 1; ?>" class="btn-admin-secondary btn-admin-sm" >Siguiente &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
