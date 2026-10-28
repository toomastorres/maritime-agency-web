<?php
/**
 * AMA — rrhh_postulantes.php (Admin)
 * Bandeja de postulaciones recibidas por el sitio público.
 *
 * Las postulaciones ya se guardaban en la tabla applicants desde
 * src/logic/api/postulantes.php, pero no había ninguna pantalla para verlas:
 * el único aviso era el mail a RRHH, y el CV sólo se podía recuperar entrando
 * al servidor. Esta vista es la bandeja: filtra, cambia el estado y descarga
 * el CV por el endpoint autenticado.
 */
if (!function_exists('is_logged_in')) exit;
if (!isset($pdo)) require_once dirname(__DIR__) . '/includes/db.php';

const POSTULANTES_POR_PAGINA = 25;

$ESTADOS = [
    'nuevo'      => ['label' => 'Nuevo',      'badge' => 'badge-blue'],
    'revision'   => ['label' => 'En revisión', 'badge' => 'badge-gold'],
    'contratado' => ['label' => 'Contratado',  'badge' => 'badge-green'],
    'descartado' => ['label' => 'Descartado',  'badge' => 'badge-gray'],
];

// ── POST: cambiar estado ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['msj'] = 'Error: token CSRF inválido.';
    } else {
        $pid    = (int) $_POST['cambiar_estado'];
        $nuevo  = $_POST['estado'] ?? '';
        if (!isset($ESTADOS[$nuevo])) {
            $_SESSION['msj'] = 'Error: estado no válido.';
        } else {
            try {
                $nom = $pdo->prepare("SELECT nombre FROM applicants WHERE id = ?");
                $nom->execute([$pid]);
                $quien = ($r = $nom->fetch()) ? $r['nombre'] : "#{$pid}";

                $pdo->prepare("UPDATE applicants SET estado = ? WHERE id = ?")->execute([$nuevo, $pid]);
                $_SESSION['msj'] = "{$quien}: estado actualizado a «{$ESTADOS[$nuevo]['label']}».";

                try {
                    $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                        ->execute([$_SESSION['user_id'], 'postulante_estado', "{$quien} → {$nuevo}", $_SERVER['REMOTE_ADDR']]);
                } catch (\PDOException $e) { }
            } catch (\PDOException $e) {
                $_SESSION['msj'] = 'Error al actualizar el estado.';
            }
        }
    }
    admin_redirect('dashboard.php?section=rrhh_postulantes' . (isset($_POST['volver_a']) ? '&' . $_POST['volver_a'] : ''));
}

// ── POST: eliminar postulación (y su CV del disco) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_postulante'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['msj'] = 'Error: token CSRF inválido.';
    } else {
        $pid = (int) $_POST['eliminar_postulante'];
        try {
            $stmt = $pdo->prepare("SELECT nombre, cv_path FROM applicants WHERE id = ?");
            $stmt->execute([$pid]);
            $row = $stmt->fetch();

            $pdo->prepare("DELETE FROM applicants WHERE id = ?")->execute([$pid]);

            // El CV también se borra: es un dato personal y la Ley 25.326 da
            // derecho a supresión. Dejarlo en disco sería conservar el dato
            // después de borrar el registro que lo justifica.
            if ($row && !empty($row['cv_path'])) {
                $base = realpath(dirname(__DIR__, 3) . '/public/uploads/cv');
                $file = realpath(dirname(__DIR__, 3) . '/public' . $row['cv_path']);
                if ($base && $file && strpos($file, $base . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
                    @unlink($file);
                }
            }

            $_SESSION['msj'] = 'Postulación eliminada, junto con el CV adjunto.';
            try {
                $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                    ->execute([$_SESSION['user_id'], 'postulante_delete', ($row['nombre'] ?? "#{$pid}") . " (#{$pid})", $_SERVER['REMOTE_ADDR']]);
            } catch (\PDOException $e) { }
        } catch (\PDOException $e) {
            $_SESSION['msj'] = 'Error al eliminar la postulación.';
        }
    }
    admin_redirect('dashboard.php?section=rrhh_postulantes');
}

// ── Filtros ───────────────────────────────────────────────────────────────────
$fEstado  = $_GET['estado'] ?? '';
$fOrigen  = $_GET['origen'] ?? '';   // vacante | espontanea
$fBuscar  = trim($_GET['q'] ?? '');
$pagina   = max(1, (int) ($_GET['p'] ?? 1));

$where = [];
$params = [];
if (isset($ESTADOS[$fEstado])) { $where[] = 'a.estado = ?'; $params[] = $fEstado; }
if ($fOrigen === 'vacante')    { $where[] = 'a.vacancy_id IS NOT NULL'; }
if ($fOrigen === 'espontanea') { $where[] = 'a.vacancy_id IS NULL'; }
if ($fBuscar !== '') {
    $where[] = '(a.nombre LIKE ? OR a.email LIKE ?)';
    $params[] = '%' . $fBuscar . '%';
    $params[] = '%' . $fBuscar . '%';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$postulantes = [];
$total = 0;
$conteos = ['todos' => 0];
try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM applicants a {$whereSql}");
    $cnt->execute($params);
    $total = (int) $cnt->fetchColumn();

    $offset = ($pagina - 1) * POSTULANTES_POR_PAGINA;
    $sql = "SELECT a.*, v.titulo AS vacante_titulo
            FROM applicants a
            LEFT JOIN vacancies v ON v.id = a.vacancy_id
            {$whereSql}
            ORDER BY a.fecha_postulacion DESC
            LIMIT " . POSTULANTES_POR_PAGINA . " OFFSET " . (int) $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $postulantes = $stmt->fetchAll();

    // Conteo por estado para las pestañas (sin los filtros de estado aplicados).
    $conteos['todos'] = (int) $pdo->query("SELECT COUNT(*) FROM applicants")->fetchColumn();
    foreach ($pdo->query("SELECT estado, COUNT(*) c FROM applicants GROUP BY estado") as $r) {
        $conteos[$r['estado']] = (int) $r['c'];
    }
} catch (\PDOException $e) {
    $_SESSION['msj'] = 'Error al leer las postulaciones.';
}

$totalPaginas = max(1, (int) ceil($total / POSTULANTES_POR_PAGINA));

$msj = '';
if (isset($_SESSION['msj'])) {
    $esError = strpos($_SESSION['msj'], 'Error') !== false;
    $msj = "<div class='admin-alert " . ($esError ? 'admin-alert-error' : 'admin-alert-success') . "'>"
         . htmlspecialchars($_SESSION['msj'], ENT_QUOTES, 'UTF-8') . '</div>';
    unset($_SESSION['msj']);
}

/** Conserva los filtros al armar un enlace. */
function post_url(array $cambios = []): string {
    $base = ['estado' => $_GET['estado'] ?? '', 'origen' => $_GET['origen'] ?? '', 'q' => $_GET['q'] ?? ''];
    $q = array_filter(array_merge($base, $cambios), fn($v) => $v !== '' && $v !== null);
    return 'dashboard.php?section=rrhh_postulantes' . ($q ? '&' . http_build_query($q) : '');
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Postulaciones</h1>
        <p class="admin-page-sub">CV recibidos desde el formulario público de Institucional.</p>
    </div>
    <a href="?section=rrhh_vacantes" class="btn-admin-secondary">
        <span class="material-symbols-outlined">work</span> Ver vacantes
    </a>
</div>

<?php echo $msj; ?>

<div class="admin-card">
    <!-- Pestañas por estado: el conteo evita tener que filtrar para saber
         cuántos hay sin revisar. -->
    <nav class="admin-tabs" aria-label="Filtrar por estado">
        <a href="<?php echo htmlspecialchars(post_url(['estado' => '', 'p' => null])); ?>"
           class="admin-tab <?php echo $fEstado === '' ? 'is-active' : ''; ?>">
            Todas <span class="admin-tab__count"><?php echo $conteos['todos']; ?></span>
        </a>
        <?php foreach ($ESTADOS as $key => $meta): ?>
        <a href="<?php echo htmlspecialchars(post_url(['estado' => $key, 'p' => null])); ?>"
           class="admin-tab <?php echo $fEstado === $key ? 'is-active' : ''; ?>">
            <?php echo $meta['label']; ?>
            <span class="admin-tab__count"><?php echo $conteos[$key] ?? 0; ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <form method="GET" class="admin-toolbar">
        <input type="hidden" name="section" value="rrhh_postulantes">
        <input type="hidden" name="estado" value="<?php echo htmlspecialchars($fEstado); ?>">
        <div class="admin-toolbar__field">
            <label class="form-label" for="pf-q">Buscar</label>
            <input type="search" id="pf-q" name="q" class="admin-input"
                   value="<?php echo htmlspecialchars($fBuscar); ?>" placeholder="Nombre o correo">
        </div>
        <div class="admin-toolbar__field admin-toolbar__field--narrow">
            <label class="form-label" for="pf-origen">Origen</label>
            <select id="pf-origen" name="origen" class="admin-select">
                <option value="">Todos</option>
                <option value="vacante" <?php echo $fOrigen === 'vacante' ? 'selected' : ''; ?>>A una vacante</option>
                <option value="espontanea" <?php echo $fOrigen === 'espontanea' ? 'selected' : ''; ?>>Espontánea</option>
            </select>
        </div>
        <div class="admin-toolbar__actions">
            <button type="submit" class="btn-admin-primary">
                <span class="material-symbols-outlined">filter_alt</span> Filtrar
            </button>
            <?php if ($fBuscar !== '' || $fOrigen !== '' || $fEstado !== ''): ?>
            <a href="dashboard.php?section=rrhh_postulantes" class="btn-admin-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="admin-table-wrapper admin-table-wrapper--flush">
        <table class="admin-table admin-table--postulantes">
            <thead>
                <tr>
                    <th>Postulante</th>
                    <th>Contacto</th>
                    <th>Se postuló a</th>
                    <th>Recibida</th>
                    <th>Estado</th>
                    <th class="is-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($postulantes)): ?>
                <tr>
                    <td colspan="6">
                        <div class="admin-empty">
                            <span class="material-symbols-outlined admin-empty__icon">inbox</span>
                            <p class="admin-empty__title">
                                <?php echo $conteos['todos'] === 0
                                    ? 'Todavía no se recibieron postulaciones.'
                                    : 'Ninguna postulación coincide con el filtro.'; ?>
                            </p>
                            <p class="admin-empty__desc">
                                <?php if ($conteos['todos'] === 0): ?>
                                    Los CV que se envíen desde el formulario de Institucional aparecen acá automáticamente.
                                <?php else: ?>
                                    Probá con otro estado o limpiá la búsqueda.
                                <?php endif; ?>
                            </p>
                            <?php if ($conteos['todos'] > 0): ?>
                            <a href="dashboard.php?section=rrhh_postulantes" class="btn-admin-secondary">Ver todas</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($postulantes as $p): ?>
                <?php
                    $fecha  = $p['fecha_postulacion'] ? strtotime($p['fecha_postulacion']) : null;
                    $dias   = $fecha ? (int) floor((time() - $fecha) / 86400) : null;
                    $ext    = strtoupper(pathinfo($p['cv_path'], PATHINFO_EXTENSION) ?: 'CV');
                ?>
                <tr>
                    <td>
                        <span class="td-main"><?php echo htmlspecialchars($p['nombre']); ?></span>
                        <?php if (!empty($p['area'])): ?>
                        <span class="td-sub">Área de interés: <?php echo htmlspecialchars($p['area']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="mailto:<?php echo htmlspecialchars($p['email']); ?>" class="admin-link">
                            <?php echo htmlspecialchars($p['email']); ?>
                        </a>
                        <?php if (!empty($p['telefono'])): ?>
                        <span class="td-sub"><?php echo htmlspecialchars($p['telefono']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($p['vacante_titulo'])): ?>
                            <span class="td-main"><?php echo htmlspecialchars($p['vacante_titulo']); ?></span>
                        <?php elseif (!empty($p['vacancy_id'])): ?>
                            <span class="td-sub">Vacante #<?php echo (int) $p['vacancy_id']; ?> (eliminada)</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Espontánea</span>
                        <?php endif; ?>
                    </td>
                    <td class="is-nowrap">
                        <span class="td-main"><?php echo $fecha ? date('d/m/Y', $fecha) : '—'; ?></span>
                        <?php if ($dias !== null): ?>
                        <span class="td-sub"><?php echo $dias === 0 ? 'hoy' : "hace {$dias} día" . ($dias === 1 ? '' : 's'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- El estado es el propio control: antes iba un badge de
                             sólo lectura y al lado un select con el mismo dato,
                             repitiendo la información y comiéndose el ancho de
                             la columna de acciones. -->
                        <form method="POST" action="?section=rrhh_postulantes" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="cambiar_estado" value="<?php echo (int) $p['id']; ?>">
                            <select name="estado" class="admin-select admin-select--sm admin-estado admin-estado--<?php echo htmlspecialchars($p['estado']); ?>"
                                    onchange="this.form.submit()"
                                    aria-label="Estado de <?php echo htmlspecialchars($p['nombre']); ?>">
                                <?php foreach ($ESTADOS as $k => $m): ?>
                                <option value="<?php echo $k; ?>" <?php echo $p['estado'] === $k ? 'selected' : ''; ?>>
                                    <?php echo $m['label']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button type="submit" class="btn-admin-secondary btn-admin-sm">Guardar</button></noscript>
                        </form>
                    </td>
                    <td>
                        <div class="admin-row-actions">
                            <!-- La descarga pasa por un endpoint con sesión: el
                                 directorio de CV ya no se sirve por URL. -->
                            <a href="descargar-cv.php?id=<?php echo (int) $p['id']; ?>"
                               class="btn-admin-primary btn-admin-sm"
                               title="Descargar el CV de <?php echo htmlspecialchars($p['nombre']); ?>">
                                <span class="material-symbols-outlined">download</span> <?php echo htmlspecialchars($ext); ?>
                            </a>

                            <form method="POST" action="?section=rrhh_postulantes" class="admin-inline-form"
                                  onsubmit="return confirm('¿Eliminar la postulación de <?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?> y su CV? No se puede deshacer.');">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="eliminar_postulante" value="<?php echo (int) $p['id']; ?>">
                                <button type="submit" class="btn-admin-danger btn-admin-sm" title="Eliminar postulación y CV">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPaginas > 1): ?>
    <div class="admin-pagination">
        <span class="admin-pagination__info">
            <?php echo $total; ?> postulacion<?php echo $total === 1 ? '' : 'es'; ?> · página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?>
        </span>
        <div class="admin-pagination__links">
            <?php if ($pagina > 1): ?>
            <a href="<?php echo htmlspecialchars(post_url(['p' => $pagina - 1])); ?>" class="btn-admin-secondary btn-admin-sm">Anterior</a>
            <?php endif; ?>
            <?php if ($pagina < $totalPaginas): ?>
            <a href="<?php echo htmlspecialchars(post_url(['p' => $pagina + 1])); ?>" class="btn-admin-secondary btn-admin-sm">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
