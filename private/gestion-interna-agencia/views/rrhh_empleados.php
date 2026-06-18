<?php
/**
 * AMA — rrhh_empleados.php (Admin)
 * Vista de listado y gestión de empleados.
 */
if (!function_exists('is_logged_in')) exit;

// POST-only: Borrar Empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_empleado'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['msj'] = 'Error: Token CSRF inválido al eliminar.';
    } else {
        try {
            $empId = (int)$_POST['delete_empleado'];
            // Fetch name for audit before deleting
            $nameStmt = $pdo->prepare("SELECT nombre, apellido FROM employees WHERE id = ?");
            $nameStmt->execute([$empId]);
            $empRow = $nameStmt->fetch();
            $empNombre = $empRow ? ($empRow['apellido'] . ', ' . $empRow['nombre']) : "ID #{$empId}";

            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$empId]);
            $_SESSION['msj'] = 'Empleado dado de baja permanentemente.';

            // Audit log
            try {
                $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                    ->execute([$_SESSION['user_id'], 'empleado_delete', "Baja: {$empNombre} (ID #{$empId})", $_SERVER['REMOTE_ADDR']]);
            } catch (\PDOException $e) { /* audit no-crítico */ }
        } catch (\PDOException $e) {
            $_SESSION['msj'] = 'Error de base de datos SQL al eliminar.';
        }
    }
    header("Location: dashboard.php?section=rrhh&action=empleados");
    exit;
}

// Listar empleados agrupados por departamento o jerarquía
$empleados = [];
try {
    $empleados = $pdo->query("SELECT * FROM employees ORDER BY departamento ASC, jerarquia ASC, apellido ASC")->fetchAll();
} catch (\PDOException $e) { }

?>

<div class="admin-card">
    <div class="admin-card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.6rem;">
            <span class="material-symbols-outlined">badge</span>
            <h3 style="margin:0; font-size:1rem;">Directorio de Contactos (Staff Interno)</h3>
        </div>
        <a href="?section=rrhh&action=empleados_form" class="btn-admin-primary" style="padding:0.5rem 1rem; font-size:0.85rem;">
            <span class="material-symbols-outlined">person_add</span> Alta Empleado
        </a>
    </div>
    <div class="admin-table-wrapper" style="border:none; box-shadow:none; margin-bottom:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Posición</th>
                    <th>Departamento</th>
                    <th>Contacto</th>
                    <th>Jerarquía</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($empleados)): ?>
                    <tr><td colspan="6" style="text-align:center; color:var(--admin-text-muted);">No hay empleados registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($empleados as $e): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars(strtoupper($e['apellido']) . ', ' . $e['nombre']); ?></strong></td>
                        <td style="color:var(--admin-blue);"><?php echo htmlspecialchars($e['puesto']); ?></td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($e['departamento']); ?></span></td>
                        <td style="font-size:0.85rem;">
                            <?php echo htmlspecialchars($e['email']); ?><br>
                            <span style="color:var(--admin-text-muted);"><?php echo htmlspecialchars($e['telefono'] ?? ''); ?></span>
                        </td>
                        <td style="text-align:center;"><?php echo htmlspecialchars($e['jerarquia']); ?></td>
                        <td>
                            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                <a href="?section=rrhh&action=empleados_form&edit=<?php echo $e['id']; ?>" class="btn-admin-secondary" style="padding:0.3rem 0.65rem; font-size:0.8rem;">Modificar</a>
                                <form method="POST" action="?section=rrhh&action=empleados" style="display:inline;" onsubmit="return confirm('ATENCIÓN: Se eliminará al empleado permanentemente. ¿Continuar?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="delete_empleado" value="<?php echo $e['id']; ?>">
                                    <button type="submit" class="btn-admin-danger" style="padding:0.3rem 0.65rem; font-size:0.8rem;">Baja</button>
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
