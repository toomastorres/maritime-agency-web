<?php
/**
 * AMA — rrhh_vacantes.php (Admin)
 * Vista de listado y gestión de vacantes laborales.
 * Las vacantes con activo=1 se muestran en la página pública institucional.php
 */
if (!function_exists('is_logged_in')) exit;

// ── POST: Eliminar Vacante ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_vacante'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['msj'] = 'Error: Token CSRF inválido al eliminar.';
    } else {
        try {
            $vacId = (int)$_POST['delete_vacante'];
            // Fetch title for audit before deleting
            $titleStmt = $pdo->prepare("SELECT titulo FROM vacancies WHERE id = ?");
            $titleStmt->execute([$vacId]);
            $vacTitle = ($r = $titleStmt->fetch()) ? $r['titulo'] : "ID #{$vacId}";

            $pdo->prepare("DELETE FROM vacancies WHERE id = ?")->execute([$vacId]);
            $_SESSION['msj'] = "Vacante \"{$vacTitle}\" eliminada permanentemente.";

            // Audit log (no-crítico)
            try {
                $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                    ->execute([$_SESSION['user_id'], 'vacante_delete', "Eliminada: {$vacTitle} (ID #{$vacId})", $_SERVER['REMOTE_ADDR']]);
            } catch (\PDOException $e) { }
        } catch (\PDOException $e) {
            $_SESSION['msj'] = 'Error de base de datos al eliminar. Puede haber registros relacionados.';
        }
    }
    header("Location: dashboard.php?section=rrhh_vacantes");
    exit;
}

// ── POST: Toggle visibilidad pública (activo) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_vacante'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['msj'] = 'Error: Token CSRF inválido.';
    } else {
        try {
            $vacId  = (int)$_POST['toggle_vacante'];
            $activo = (int)$_POST['activo']; // 0 or 1 — explicit value sent by form

            $pdo->prepare("UPDATE vacancies SET activo = ? WHERE id = ?")->execute([$activo, $vacId]);
            $_SESSION['msj'] = $activo
                ? 'Vacante activada: ahora es visible en la web pública.'
                : 'Vacante desactivada: ya no es visible en la web pública.';

            // Audit log (no-crítico)
            try {
                $label = $activo ? 'activada' : 'desactivada';
                $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                    ->execute([$_SESSION['user_id'], 'vacante_toggle', "Vacante ID #{$vacId} {$label}", $_SERVER['REMOTE_ADDR']]);
            } catch (\PDOException $e) { }
        } catch (\PDOException $e) {
            $_SESSION['msj'] = 'Error al cambiar la visibilidad de la vacante.';
        }
    }
    header("Location: dashboard.php?section=rrhh_vacantes");
    exit;
}

// ── Listar vacantes ───────────────────────────────────────────────────────────
$vacantes = [];
try {
    $vacantes = $pdo->query("SELECT * FROM vacancies ORDER BY creado_en DESC")->fetchAll();
} catch (\PDOException $e) { }

// Mensaje de sesión
$msj = '';
if (isset($_SESSION['msj'])) {
    $is_error = strpos($_SESSION['msj'], 'Error') !== false;
    $msj = "<div class='admin-alert " . ($is_error ? 'admin-alert-error' : 'admin-alert-success') . "'>"
         . htmlspecialchars($_SESSION['msj'], ENT_QUOTES, 'UTF-8') . "</div>";
    unset($_SESSION['msj']);
}
?>

<?php echo $msj; ?>

<div class="admin-card">
    <div class="admin-card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.6rem;">
            <span class="material-symbols-outlined">work</span>
            <h3 style="margin:0; font-size:1rem;">Gestión de Vacantes Web</h3>
        </div>
        <a href="?section=rrhh_vacantes_form" class="btn-admin-primary" style="padding:0.5rem 1rem; font-size:0.85rem;">
            <span class="material-symbols-outlined">add</span> Nueva Vacante
        </a>
    </div>

    <div style="padding: 0.75rem 1.25rem; background: rgba(0,51,160,0.04); border-bottom: 1px solid var(--admin-border); font-size: 0.82rem; color: var(--admin-text-muted);">
        <span class="material-symbols-outlined" style="font-size:1rem; vertical-align:middle; margin-right:0.25rem;">info</span>
        Las vacantes con estado <strong>Visible</strong> aparecen en la sección pública <em>Institucional</em>. Usá el botón <strong>Activar / Desactivar</strong> para controlar su visibilidad.
    </div>

    <div class="admin-table-wrapper" style="border:none; box-shadow:none; margin-bottom:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Departamento</th>
                    <th>Ubicación</th>
                    <th>Web Pública</th>
                    <th>Estado</th>
                    <th>Creada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vacantes)): ?>
                    <tr><td colspan="8" style="text-align:center; color:var(--admin-text-muted); padding:2rem;">No hay vacantes registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($vacantes as $v): ?>
                    <?php
                        // Visibilidad pública (activo field)
                        $isActivo     = isset($v['activo']) ? (bool)$v['activo'] : false;
                        $toggleText   = $isActivo ? 'Desactivar' : 'Activar';
                        $toggleClass  = $isActivo ? 'btn-admin-warning' : 'btn-admin-success';
                        $nextActivo   = $isActivo ? 0 : 1;

                        // Estado interno (abierta/cerrada)
                        $isAbierta    = isset($v['estado']) ? (bool)$v['estado'] : false;

                        // Fecha
                        $fechaDisplay = isset($v['creado_en'])
                            ? date('d/m/Y', strtotime($v['creado_en']))
                            : '—';
                    ?>
                    <tr>
                        <td>#<?php echo $v['id']; ?></td>
                        <td style="font-weight:500; color:var(--admin-blue);"><?php echo htmlspecialchars($v['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($v['departamento'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($v['ubicacion'] ?? '—'); ?></td>
                        <td>
                            <span class="badge <?php echo $isActivo ? 'badge-green' : 'badge-red'; ?>">
                                <?php echo $isActivo ? 'Visible' : 'Oculta'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $isAbierta ? 'badge-blue' : 'badge-gray'; ?>">
                                <?php echo $isAbierta ? 'ABIERTA' : 'CERRADA'; ?>
                            </span>
                        </td>
                        <td style="font-size:0.82rem; color:var(--admin-text-muted);"><?php echo $fechaDisplay; ?></td>
                        <td>
                            <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">

                                <!-- Editar -->
                                <a href="?section=rrhh_vacantes_form&edit=<?php echo $v['id']; ?>"
                                   class="btn-admin-secondary"
                                   style="padding:0.3rem 0.65rem; font-size:0.8rem;">Editar</a>

                                <!-- Toggle visibilidad pública -->
                                <form method="POST" action="?section=rrhh_vacantes" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="toggle_vacante" value="<?php echo $v['id']; ?>">
                                    <input type="hidden" name="activo" value="<?php echo $nextActivo; ?>">
                                    <button type="submit" class="<?php echo $toggleClass; ?>"
                                            style="padding:0.3rem 0.65rem; font-size:0.8rem;">
                                        <?php echo $toggleText; ?>
                                    </button>
                                </form>

                                <!-- Eliminar -->
                                <form method="POST" action="?section=rrhh_vacantes" style="display:inline;"
                                      onsubmit="return confirm('¿Eliminar esta vacante permanentemente? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="delete_vacante" value="<?php echo $v['id']; ?>">
                                    <button type="submit" class="btn-admin-danger"
                                            style="padding:0.3rem 0.65rem; font-size:0.8rem;">Eliminar</button>
                                </form>

                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
