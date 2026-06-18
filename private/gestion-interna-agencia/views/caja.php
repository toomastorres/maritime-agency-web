<?php
/**
 * AMA — caja.php (Admin)
 * Vista del módulo Caja: tipo de cambio, datos bancarios, historial.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php'; // Requerimos la conexión a BD

// Validar que el usuario tenga el rol requerido
require_role('caja');

$mensaje = '';

// Procesamiento de formulario (Guardar en MySQL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dolar_valor'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $mensaje = "<div style='color:red;'>Token de seguridad inválido.</div>";
    } else {
        $nuevo_dolar = filter_input(INPUT_POST, 'dolar_valor', FILTER_VALIDATE_FLOAT);
        $nuevo_euro = filter_input(INPUT_POST, 'euro_valor', FILTER_VALIDATE_FLOAT);
        $nuevos_datos_bancarios = $_POST['datos_bancarios'] ?? '';
        
        if ($nuevo_dolar !== false && $nuevo_dolar > 0) {
            try {
                $pdo->beginTransaction();

                // 1. Insertar el nuevo tipo de cambio en el histórico
                $stmt = $pdo->prepare("INSERT INTO exchange_rates (moneda, valor, fecha_cotizacion, usuario_id) VALUES ('USD', :valord, CURDATE(), :uid)");
                $stmt->execute(['valord' => $nuevo_dolar, 'uid' => $_SESSION['user_id']]);

                if ($nuevo_euro) {
                    $stmt = $pdo->prepare("INSERT INTO exchange_rates (moneda, valor, fecha_cotizacion, usuario_id) VALUES ('EUR', :valore, CURDATE(), :uid)");
                    $stmt->execute(['valore' => $nuevo_euro, 'uid' => $_SESSION['user_id']]);
                }

                // 2. Actualizar la tabla de un solo registro para detalles bancarios
                $stmt = $pdo->prepare("UPDATE bank_details SET contenido_html = :contenido, actualizado_por = :uid WHERE id = 1");
                $stmt->execute([
                    'contenido' => htmlspecialchars($nuevos_datos_bancarios, ENT_QUOTES, 'UTF-8'),
                    'uid' => $_SESSION['user_id']
                ]);

                $pdo->commit();
                
                // Send Internal Email Notification
                try {
                    require_once dirname(__DIR__, 3) . '/src/config/config.php';
                    require_once dirname(__DIR__, 3) . '/src/core/email.php';
                    $emailService = new EmailService();
                    $subject = "Actualización de Tipo de Cambio - Caja";
                    $htmlBody = EmailService::buildHtmlTemplate($subject, "
                        <p>Se ha actualizado el tipo de cambio oficial del día en el sistema:</p>
                        <ul>
                            <li><strong>Dólar (USD):</strong> $" . number_format($nuevo_dolar, 2) . "</li>
                            " . ($nuevo_euro ? "<li><strong>Euro (EUR):</strong> $" . number_format($nuevo_euro, 2) . "</li>" : "") . "
                        </ul>
                        <p><em>Esta actualización ya se encuentra visible en la web pública.</em></p>
                    ");
                    $emailService->sendNotification('caja', $subject, $htmlBody);
                } catch (\Exception $e) {
                    error_log("Email error (caja): " . $e->getMessage());
                }
                
                // Audit log
                try {
                    $logStmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
                    $detalle = "USD: " . number_format($nuevo_dolar, 2) . ($nuevo_euro ? " | EUR: " . number_format($nuevo_euro, 2) : "");
                    $logStmt->execute([$_SESSION['user_id'], 'caja_update', $detalle, $_SERVER['REMOTE_ADDR']]);
                } catch (\PDOException $e) { /* audit no-crítico */ }

                $mensaje = "<div style='color:green; padding: 10px; border: 1px solid green; border-radius: 4px; background: #e6ffe6;'>Cotización actualizada e impactada en la base de datos pública.</div>";

            } catch (\PDOException $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $mensaje = "<div style='color:red;'>Error de base de datos al guardar la cotización.</div>";
            }
        } else {
            $mensaje = "<div style='color:red;'>Valor ingresado inválido. (Use \".\" para decimales)</div>";
        }
    }
}

// Cargar estado actual de BD para poblar el formulario
$data_actual = ['dolar_valor' => '', 'euro_valor' => '', 'datos_bancarios' => '', 'ultima_actualizacion' => '-'];

try {
    // Buscar dolar de hoy
    $stmtD = $pdo->query("SELECT valor FROM exchange_rates WHERE moneda = 'USD' AND fecha_cotizacion = CURDATE() ORDER BY creado_en DESC LIMIT 1");
    if ($row = $stmtD->fetch()) $data_actual['dolar_valor'] = $row['valor'];

    // Buscar euro de hoy
    $stmtE = $pdo->query("SELECT valor FROM exchange_rates WHERE moneda = 'EUR' AND fecha_cotizacion = CURDATE() ORDER BY creado_en DESC LIMIT 1");
    if ($row = $stmtE->fetch()) $data_actual['euro_valor'] = $row['valor'];

    // Buscar datos bancarios (fila 1 obligatoria)
    $stmtB = $pdo->query("SELECT contenido_html, ultima_actualizacion FROM bank_details WHERE id = 1");
    if ($row = $stmtB->fetch()) {
        $data_actual['datos_bancarios'] = htmlspecialchars_decode($row['contenido_html'], ENT_QUOTES);
        $data_actual['ultima_actualizacion'] = $row['ultima_actualizacion'];
    }

} catch (\PDOException $e) {
    $mensaje = "<div style='color:red;'>Advertencia: Las tablas de la base de datos no han sido creadas. Ejecute el <code>schema.sql</code>.</div>";
}

// Paginador e Histórico
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$filtro_moneda = $_GET['moneda'] ?? '';
$where = "1=1";
$params = [];
if ($filtro_moneda === 'USD' || $filtro_moneda === 'EUR') {
    $where .= " AND e.moneda = :moneda";
    $params['moneda'] = $filtro_moneda;
}

// Contar totales para paginación
$historico = [];
$total_rows = 0;
try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM exchange_rates e WHERE $where");
    $stmtCount->execute($params);
    $total_rows = $stmtCount->fetchColumn();

    $sqlHist = "SELECT e.id, e.moneda, e.valor, e.fecha_cotizacion, e.creado_en, u.nombre AS autor
                FROM exchange_rates e
                LEFT JOIN usuarios_admin u ON e.usuario_id = u.id
                WHERE $where
                ORDER BY e.creado_en DESC LIMIT :lim OFFSET :off";
    $stmtHist = $pdo->prepare($sqlHist);
    $stmtHist->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmtHist->bindValue(':off', $offset, PDO::PARAM_INT);
    foreach ($params as $k => $v) { $stmtHist->bindValue(':' . $k, $v); }
    $stmtHist->execute();
    $historico = $stmtHist->fetchAll();
} catch (\PDOException $e) {
    // Silencioso; si no existe la tabla ya lo advirtió arriba.
}
$total_pages = ceil($total_rows / $limit);
?>

<div style="display:flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-start;">

    <!-- Formulario Principal -->
    <div class="admin-card" style="flex: 1; min-width: 280px;">
        <div class="admin-card-header">
            <span class="material-symbols-outlined">currency_exchange</span>
            <h2 style="margin:0; font-size:1.1rem;">Gestión de Tipo de Cambio</h2>
        </div>
        <p style="color:var(--admin-text-muted); margin:0 0 1rem; font-size:0.9rem;">Solo visible entre 09:00 y 17:00 hs (Lunes a Viernes) para el público.</p>

        <?php echo $mensaje; ?>

        <form method="POST" action="?section=caja">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="form-group">
                <label class="form-label" for="dolar_valor">Cotización DÓLAR — <?php echo date('d/m/Y'); ?></label>
                <input type="number" step="0.01" id="dolar_valor" name="dolar_valor" class="admin-input"
                    value="<?php echo htmlspecialchars($data_actual['dolar_valor']); ?>" required placeholder="Ej: 1050.50">
            </div>

            <div class="form-group">
                <label class="form-label" for="euro_valor">Cotización EURO (Opcional)</label>
                <input type="number" step="0.01" id="euro_valor" name="euro_valor" class="admin-input"
                    value="<?php echo htmlspecialchars($data_actual['euro_valor']); ?>" placeholder="Ej: 1140.00">
            </div>

            <div class="form-group">
                <label class="form-label" for="datos_bancarios">Datos Bancarios / Instrucciones</label>
                <textarea id="datos_bancarios" name="datos_bancarios" class="admin-textarea" rows="4" required><?php echo htmlspecialchars($data_actual['datos_bancarios']); ?></textarea>
            </div>

            <button type="submit" class="btn-admin-primary" style="width:100%;">
                <span class="material-symbols-outlined">publish</span>
                Publicar Cotización
            </button>
        </form>

        <p style="margin-top:1rem; font-size:0.8rem; color:var(--admin-text-light);">
            Última actualización: <?php echo htmlspecialchars($data_actual['ultima_actualizacion']); ?>
        </p>
    </div>

    <!-- Histórico -->
    <div class="admin-card" style="flex: 2; min-width: 320px;">
        <div class="admin-card-header">
            <span class="material-symbols-outlined">history</span>
            <h3 style="margin:0; font-size:1rem;">Histórico de Cargas</h3>
        </div>

        <form method="GET" action="dashboard.php" style="display:flex; gap:0.75rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
            <input type="hidden" name="section" value="caja">
            <select name="moneda" class="admin-select" style="width:auto;">
                <option value="">Todas las Divisas</option>
                <option value="USD" <?php echo $filtro_moneda === 'USD' ? 'selected' : ''; ?>>Dólar (USD)</option>
                <option value="EUR" <?php echo $filtro_moneda === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
            </select>
            <button type="submit" class="btn-admin-secondary">Filtrar</button>
        </form>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Moneda</th>
                        <th>Valor</th>
                        <th>Fecha Cot.</th>
                        <th>Hora Carga</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr><td colspan="6" style="text-align:center; color:var(--admin-text-muted);">No hay registros históricos.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historico as $h): ?>
                        <tr>
                            <td>#<?php echo $h['id']; ?></td>
                            <td><span class="badge <?php echo $h['moneda'] === 'USD' ? 'badge-green' : 'badge-blue'; ?>"><?php echo $h['moneda']; ?></span></td>
                            <td style="font-family:monospace; font-weight:600;">$<?php echo number_format($h['valor'], 2); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($h['fecha_cotizacion'])); ?></td>
                            <td style="font-size:0.85em; color:var(--admin-text-muted);"><?php echo date('H:i', strtotime($h['creado_en'])); ?></td>
                            <td><?php echo htmlspecialchars($h['autor'] ?? 'Desconocido'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div style="margin-top:1rem; display:flex; gap:0.375rem; justify-content:center; flex-wrap:wrap;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?section=caja&moneda=<?php echo $filtro_moneda; ?>&p=<?php echo $i; ?>"
                   style="padding:0.3rem 0.65rem; background:<?php echo $i == $page ? 'var(--admin-blue)' : 'var(--admin-border-light)'; ?>; color:<?php echo $i == $page ? '#fff' : 'var(--admin-text)'; ?>; text-decoration:none; border-radius:6px; font-size:0.85rem; font-weight:500;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

</div>
