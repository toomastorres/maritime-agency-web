<?php
/**
 * AMA — cs.php (Admin)
 * Vista del módulo Customer Service: novedades, schedules, empleados.
 */
if (!function_exists('is_logged_in')) require_once __DIR__ . '/../includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_role('cs');

$mensaje = '';

// Autoload de librerías nativas para Excel sin dependencias
require_once dirname(__DIR__, 3) . '/src/core/SimpleXLSX.php';
require_once dirname(__DIR__, 3) . '/src/core/SimpleXLS.php';

/**
 * Convierte un valor de celda de PhpSpreadsheet a DATE MySQL (Y-m-d).
 * Maneja seriales numéricos de Excel y strings con formatos dd/mm o dd/mm/YYYY.
 */
function parseExcelDate($value): ?string {
    if ($value === null || $value === '') return null;
    if (is_numeric($value) && $value > 1) {
        try {
            $ts = ($value - 25569) * 86400; // UNIX Epoch vs Excel Epoch
            if ($ts > 0) return gmdate('Y-m-d', (int)$ts);
            return null;
        } catch (\Exception $e) { return null; }
    }
    $str = trim((string)$value);
    // Quitar el sufijo (*) si quedara
    $str = preg_replace('/\s*\(\*\).*$/', '', $str);
    $str = trim($str);
    if ($str === '') return null;
    // Inferir año actual si el string es solo dd/mm
    if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', $str, $m)) {
        $str = $m[1] . '/' . $m[2] . '/' . date('Y');
    }
    foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'j/n/Y', 'j/n/y', 'd/m/y'] as $fmt) {
        $dt = \DateTime::createFromFormat($fmt, $str);
        if ($dt !== false) return $dt->format('Y-m-d');
    }
    $ts = strtotime($str);
    return $ts ? date('Y-m-d', $ts) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    
    // Validar si la accion es purgar
    if (isset($_POST['purgar_hash'])) {
        if (!verify_csrf_token($csrf)) {
            $mensaje = "<div class='admin-alert admin-alert-error'>Token CSRF inválido.</div>";
        } else {
            try {
                // ON DELETE CASCADE elimina automáticamente los schedule_port_calls asociados
                $stmt = $pdo->prepare("DELETE FROM schedule_imports WHERE hash = ?");
                $stmt->execute([$_POST['purgar_hash']]);
                try {
                    $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                        ->execute([$_SESSION['user_id'], 'schedule_delete', "Hash: " . mb_substr($_POST['purgar_hash'], 0, 16), $_SERVER['REMOTE_ADDR']]);
                } catch (\PDOException $e) { /* audit no-critico */ }
                $mensaje = "<div class='admin-alert admin-alert-success'>Importación eliminada correctamente.</div>";
            } catch (\PDOException $e) {
                $mensaje = "<div class='admin-alert admin-alert-error'>Error al eliminar.</div>";
            }
        }
    }
    // Procesar subida de Excel
    elseif (isset($_FILES['excel_schedules'])) {
        if (!verify_csrf_token($csrf)) {
            $mensaje = "<div style='color:red;'>Token de seguridad inválido.</div>";
        } elseif (!class_exists('\Shuchkin\SimpleXLSX') || !class_exists('\Shuchkin\SimpleXLS')) {
            $mensaje = "<div style='color:red;'>Las librerías de procesamiento de Excel no están en src/core/. No se puede procesar el archivo.</div>";
        } else {
            $file = $_FILES['excel_schedules'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $tipos_permitidos = ['xlsx', 'xls'];

            if (in_array($ext, $tipos_permitidos) && $file['error'] === UPLOAD_ERR_OK) {
                try {
                    if ($ext === 'xlsx') {
                        $xlsx = \Shuchkin\SimpleXLSX::parse($file['tmp_name']);
                        if (!$xlsx) {
                            throw new \RuntimeException("Error leyendo archivo XLSX: " . \Shuchkin\SimpleXLSX::parseError());
                        }
                        $rows = $xlsx->rows();
                    } else {
                        $xls = \Shuchkin\SimpleXLS::parse($file['tmp_name']);
                        if (!$xls) {
                            throw new \RuntimeException("Error leyendo archivo XLS: " . \Shuchkin\SimpleXLS::parseError());
                        }
                        $rows = $xls->rows();
                    }

                    // ── Fila 0: nombre del servicio + número de semana ──
                    $title_raw   = trim((string)($rows[0][0] ?? ''));
                    $week_number = 0;
                    if (preg_match('/WEEK\s+(\d+)/i', $title_raw, $wm)) $week_number = (int)$wm[1];
                    $service_name = $title_raw ?: 'UNKNOWN SERVICE';

                    // ── Detección dinámica de bloques ──────────────────────────
                    // Un bloque empieza cuando las cols 2,5,8,11 contienen nombres
                    // de buques (no fechas, no ETA/ETS, no vacías).
                    $vessel_cols = [2, 5, 8, 11];
                    $reserved_headers = ['ETA', 'ETS', 'ETD', ''];
                    $blocks = [];
                    $total_rows = count($rows);

                    for ($r = 0; $r < $total_rows; $r++) {
                        $candidate_names = [];
                        foreach ($vessel_cols as $col) {
                            $val = trim((string)($rows[$r][$col] ?? ''));
                            if ($val === '') continue;
                            // Descartar si es header (ETA/ETS) o parece fecha
                            if (in_array(strtoupper($val), $reserved_headers)) continue;
                            if (preg_match('/^\d{1,2}[\/-]\d{1,2}([\/-]\d{2,4})?(\s*\(\*\))?$/', $val)) continue;
                            if (is_numeric($val)) continue;
                            $candidate_names[$col] = $val;
                        }
                        // Se necesitan al menos 2 nombres para confirmar un bloque
                        if (count($candidate_names) >= 2) {
                            // Cerrar el bloque anterior
                            if (!empty($blocks)) {
                                $blocks[count($blocks) - 1]['port_end'] = $r - 2;
                            }
                            // La fila siguiente tiene voyage codes, la +2 headers, port data desde +3
                            $block = [
                                'vessel_row' => $r,
                                'port_start' => $r + 3,
                                'port_end'   => $total_rows - 1, // se ajusta cuando aparece el siguiente bloque
                                'vessels'    => [],
                            ];
                            foreach ($vessel_cols as $col) {
                                $vname  = trim((string)($rows[$r][$col] ?? ''));
                                $voyage = trim((string)($rows[$r + 1][$col] ?? ''));
                                if ($vname !== '' && !in_array(strtoupper($vname), $reserved_headers)) {
                                    $block['vessels'][] = [
                                        'name'    => $vname,
                                        'voyage'  => $voyage,
                                        'eta_col' => $col,
                                        'ets_col' => $col + 1,
                                    ];
                                }
                            }
                            if (!empty($block['vessels'])) {
                                $blocks[] = $block;
                            }
                            $r += 2; // Saltar voyage codes y header ETA/ETS
                        }
                    }

                    if (empty($blocks)) {
                        throw new \RuntimeException('No se detectaron bloques de buques en el archivo. Verifique que el Excel tenga el formato correcto (nombres de buques en cols B, E, H, K).');
                    }

                    // ── Parsear port calls solo del primer bloque ─────────────
                    $port_calls    = [];
                    $all_vessels   = []; // Array plano de todos los buques con vessel_order

                    // Solo procesamos el primer bloque (4 buques principales)
                    $blocks_to_process = [$blocks[0]];

                    foreach ($blocks_to_process as $bi => $block) {
                        $global_vessel_offset = $bi * 4;

                        // Registrar vessels con su vessel_order global
                        foreach ($block['vessels'] as $vi => $vessel) {
                            $all_vessels[] = [
                                'name'         => $vessel['name'],
                                'voyage'       => $vessel['voyage'],
                                'vessel_order' => $global_vessel_offset + $vi,
                                'block'        => $bi,
                            ];
                        }

                        // Tracking de puertos ya vistos por cada buque en este bloque (para detectar leg return)
                        $seen_ports = []; // key: vessel_index_in_block => [port_name => true]

                        // port_order = fila del Excel relativa al inicio del bloque
                        // Todos los vessels en la misma fila comparten el mismo port_order
                        $row_order = 0;

                        for ($r = $block['port_start']; $r <= $block['port_end']; $r++) {
                            if (!isset($rows[$r])) break;
                            $port_name = trim((string)($rows[$r][0] ?? ''));
                            if ($port_name === '') continue;

                            // Determinar el leg globalmente por fila: si CUALQUIER buque ya vio este puerto,
                            // verificar si es la misma fila. Usamos tracking por buque individual.
                            $has_any_data = false;

                            foreach ($block['vessels'] as $vi => $vessel) {
                                $eta_raw = trim((string)($rows[$r][$vessel['eta_col']] ?? ''));
                                $ets_raw = trim((string)($rows[$r][$vessel['ets_col']] ?? ''));
                                if ($eta_raw === '' && $ets_raw === '') continue;

                                $has_any_data = true;

                                // Detección de feeder: la celda ETA contiene "(*)"
                                $is_feeder = (strpos($eta_raw, '(*)') !== false) ? 1 : 0;

                                // Detección de leg: si el puerto ya apareció para este buque, es 'return'
                                $vessel_key = $vi;
                                if (!isset($seen_ports[$vessel_key])) $seen_ports[$vessel_key] = [];
                                $leg = isset($seen_ports[$vessel_key][$port_name]) ? 'return' : 'outbound';
                                $seen_ports[$vessel_key][$port_name] = true;

                                $port_calls[] = [
                                    'vessel_idx'   => $global_vessel_offset + $vi,
                                    'vessel_name'  => $vessel['name'],
                                    'voyage'       => $vessel['voyage'],
                                    'port_name'    => $port_name,
                                    'eta'          => $is_feeder ? null : parseExcelDate($eta_raw),
                                    'ets'          => $is_feeder ? null : parseExcelDate($ets_raw),
                                    'is_feeder'    => $is_feeder,
                                    'feeder_code'  => $is_feeder ? ($ets_raw !== '' ? $ets_raw : null) : null,
                                    'port_order'   => $row_order, // Mismo valor para todos los vessels en esta fila
                                    'vessel_order' => $global_vessel_offset + $vi,
                                    'block_number' => $bi,
                                    'leg'          => $leg,
                                ];
                            }

                            // Solo incrementar row_order si esta fila tenía datos
                            if ($has_any_data) {
                                $row_order++;
                            }
                        }
                    }

                    // ── Hash SHA-256 para evitar reimportaciones duplicadas ─────
                    $hash = hash('sha256', file_get_contents($file['tmp_name']));
                    $chk  = $pdo->prepare("SELECT id FROM schedule_imports WHERE hash = ? LIMIT 1");
                    $chk->execute([$hash]);

                    if ($chk->fetch()) {
                        $mensaje = "<div class='admin-alert admin-alert-warning'>Este archivo ya fue importado anteriormente (mismo hash SHA-256). Elimine el lote anterior si desea reimportar.</div>";
                    } else {
                        $pdo->beginTransaction();

                        $stmt_imp = $pdo->prepare(
                            "INSERT INTO schedule_imports (service_name, week_number, hash, archivo_original, subido_por)
                             VALUES (?, ?, ?, ?, ?)"
                        );
                        $stmt_imp->execute([
                            $service_name,
                            $week_number,
                            $hash,
                            htmlspecialchars($file['name']),
                            $_SESSION['user_id'] ?? null,
                        ]);
                        $import_id = (int)$pdo->lastInsertId();

                        $stmt_pc = $pdo->prepare(
                            "INSERT INTO schedule_port_calls
                             (import_id, vessel_name, voyage_code, port_name, eta, ets, is_feeder, feeder_code, port_order, vessel_order, block_number, leg)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        foreach ($port_calls as $pc) {
                            $stmt_pc->execute([
                                $import_id,
                                $pc['vessel_name'],
                                $pc['voyage'],
                                $pc['port_name'],
                                $pc['eta'],
                                $pc['ets'],
                                $pc['is_feeder'],
                                $pc['feeder_code'],
                                $pc['port_order'],
                                $pc['vessel_order'],
                                $pc['block_number'],
                                $pc['leg'],
                            ]);
                        }

                        $pdo->commit();

                        $total_buques  = count($all_vessels);
                        $total_bloques = count($blocks);
                        $total_pc      = count($port_calls);

                        // Send Internal Email Notification
                        try {
                            require_once dirname(__DIR__, 3) . '/src/config/config.php';
                            require_once dirname(__DIR__, 3) . '/src/core/email.php';
                            $emailService = new EmailService();
                            $subject = "Actualización de Schedules - " . strtoupper($service_name);
                            $htmlBody = EmailService::buildHtmlTemplate($subject, "
                                <p>El equipo de Customer Service ha actualizado los schedules marítimos.</p>
                                <ul>
                                    <li><strong>Servicio:</strong> {$service_name}</li>
                                    <li><strong>Semana:</strong> {$week_number}</li>
                                    <li><strong>Buques Importados:</strong> {$total_buques}</li>
                                    <li><strong>Total de Escalas:</strong> {$total_pc}</li>
                                </ul>
                                <p><em>Los nuevos itinerarios ya están disponibles en la web pública.</em></p>
                            ");
                            $emailService->sendNotification('cs', $subject, $htmlBody);
                        } catch (\Exception $e) {
                            error_log("Email error (schedules): " . $e->getMessage());
                        }

                        // Audit log
                        try {
                            $pdo->prepare("INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)")
                                ->execute([$_SESSION['user_id'], 'schedule_upload', "Servicio: {$service_name} | Semana: {$week_number} | Buques: {$total_buques} | Escalas: {$total_pc}", $_SERVER['REMOTE_ADDR']]);
                        } catch (\PDOException $e) { /* audit no-critico */ }

                        $mensaje = "<div class='admin-alert admin-alert-success'>"
                                 . "Schedule importado correctamente: <strong>$total_bloques bloques</strong>, "
                                 . "<strong>$total_buques viajes de buques</strong>, "
                                 . "<strong>$total_pc escalas de puerto</strong>. "
                                 . "Semana $week_number — $service_name</div>";
                    }

                } catch (\Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $mensaje = "<div class='admin-alert admin-alert-error'>Error al procesar el Excel: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            } else {
                $mensaje = "<div style='color:red;'>Error al subir el archivo. Asegúrese de que sea .xlsx o .xls válido.</div>";
            }
        }
    }
}

// Histórico de importaciones
$importaciones = [];
try {
    $stmt = $pdo->query(
        "SELECT si.id, si.hash, si.service_name, si.week_number,
                si.archivo_original, si.creado_en,
                COUNT(spc.id)                  AS total_escalas,
                COUNT(DISTINCT spc.vessel_name) AS total_buques
         FROM schedule_imports si
         LEFT JOIN schedule_port_calls spc ON spc.import_id = si.id
         GROUP BY si.id
         ORDER BY si.creado_en DESC
         LIMIT 10"
    );
    $importaciones = $stmt->fetchAll();
} catch (\PDOException $e) {}

// Manejo del formulario de nueva noticia
$msg_novedad = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_novedad'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $msg_novedad = '<div class="admin-alert admin-alert-error">Token de seguridad inválido.</div>';
    } else {
        $titulo        = trim($_POST['nov_titulo']    ?? '');
        $bajada        = trim($_POST['nov_bajada']    ?? '');
        $cuerpo        = trim($_POST['nov_cuerpo']    ?? '');
        $categoria     = trim($_POST['nov_categoria'] ?? 'Servicios');
        $etiquetas_raw = trim($_POST['nov_etiquetas'] ?? '');
        $destacada     = isset($_POST['nov_destacada']) ? 1 : 0;

        // Validación de longitud máxima
        if (strlen($titulo) > 300 || strlen($bajada) > 500 || strlen($cuerpo) > 50000 || strlen($etiquetas_raw) > 500) {
            $msg_novedad = '<div class="admin-alert admin-alert-error">Uno o más campos exceden la longitud máxima permitida.</div>';
        }

        // Whitelist de categorías válidas
        $categorias_validas = ['Servicios', 'Tecnología', 'Sustentabilidad', 'Información', 'Eventos'];
        if (!in_array($categoria, $categorias_validas)) {
            $categoria = 'Servicios';
        }

        if ($msg_novedad !== '') {
            // Validation error — skip insert
        } elseif ($titulo && $bajada) {
            $etiquetas_arr = array_values(array_filter(array_map('trim', explode(',', $etiquetas_raw))));
            $etiquetas_json = json_encode($etiquetas_arr, JSON_UNESCAPED_UNICODE);

            // ── Procesar imagen ────────────────────────────────────────
            $imagen_url = null;
            $upload_base = dirname(__DIR__, 3) . '/public/uploads/novedades';
            if (!is_dir($upload_base)) @mkdir($upload_base, 0755, true);

            if (!empty($_FILES['nov_imagen']['tmp_name']) && $_FILES['nov_imagen']['error'] === UPLOAD_ERR_OK) {
                $img_tmp  = $_FILES['nov_imagen']['tmp_name'];
                $img_name = $_FILES['nov_imagen']['name'];
                $img_size = $_FILES['nov_imagen']['size'];
                $img_mime = (new finfo(FILEINFO_MIME_TYPE))->file($img_tmp);
                $allowed_img = ['image/jpeg', 'image/png', 'image/webp'];
                $img_ext_check = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
                $allowed_img_ext = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($img_mime, $allowed_img) || !in_array($img_ext_check, $allowed_img_ext)) {
                    $msg_novedad = '<div class="admin-alert admin-alert-warning">Formato de imagen no permitido. Use JPG, PNG o WebP.</div>';
                } elseif ($img_size > 2 * 1024 * 1024) {
                    $msg_novedad = '<div class="admin-alert admin-alert-warning">La imagen supera los 2 MB permitidos.</div>';
                } else {
                    $ext = pathinfo($img_name, PATHINFO_EXTENSION) ?: 'jpg';
                    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(substr($titulo, 0, 60)));
                    $img_filename = date('Ymd') . '_' . $slug . '.' . $ext;
                    $img_dest = $upload_base . '/' . $img_filename;
                    if (move_uploaded_file($img_tmp, $img_dest)) {
                        $imagen_url = '/uploads/novedades/' . $img_filename;
                    } else {
                        error_log("AMA upload failed: source=$img_tmp dest=$img_dest");
                        $msg_novedad = '<div class="admin-alert admin-alert-warning">Error al guardar la imagen. Verifique permisos del directorio uploads.</div>';
                    }
                }
            }

            if ($msg_novedad !== '') {
                // Validation error from image — skip insert
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO novedades (titulo, bajada, cuerpo, categoria, destacada, etiquetas, imagen_url, fecha_iso, publicado_por)
                         VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)"
                    );
                    $stmt->execute([
                        $titulo,
                        $bajada,
                        $cuerpo ?: null,
                        $categoria,
                        $destacada,
                        $etiquetas_json,
                        $imagen_url,
                        $_SESSION['user_id'] ?? null
                    ]);
                    $novedad_id = (int)$pdo->lastInsertId();

                    // ── Procesar documentos adjuntos ───────────────────────
                    $docs_dir = $upload_base . '/docs';
                    if (!is_dir($docs_dir)) @mkdir($docs_dir, 0755, true);

                    $allowed_docs = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ];

                    if (!empty($_FILES['nov_documentos']['tmp_name'][0])) {
                        $doc_count = count($_FILES['nov_documentos']['tmp_name']);
                        $doc_stmt = $pdo->prepare(
                            "INSERT INTO novedad_documentos (novedad_id, nombre_original, archivo_path, tipo_mime, tamano_bytes)
                             VALUES (?, ?, ?, ?, ?)"
                        );
                        for ($i = 0; $i < $doc_count; $i++) {
                            if ($_FILES['nov_documentos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                            $doc_tmp  = $_FILES['nov_documentos']['tmp_name'][$i];
                            $doc_name = $_FILES['nov_documentos']['name'][$i];
                            $doc_size = $_FILES['nov_documentos']['size'][$i];
                            $doc_mime = (new finfo(FILEINFO_MIME_TYPE))->file($doc_tmp);
                            $doc_ext_check = strtolower(pathinfo($doc_name, PATHINFO_EXTENSION));
                            $allowed_doc_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

                            if (!in_array($doc_mime, $allowed_docs) || !in_array($doc_ext_check, $allowed_doc_ext) || $doc_size > 10 * 1024 * 1024) continue;

                            $doc_ext = pathinfo($doc_name, PATHINFO_EXTENSION) ?: 'pdf';
                            $doc_filename = date('Ymd') . '_' . $novedad_id . '_' . preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($doc_name, PATHINFO_FILENAME))) . '.' . $doc_ext;
                            $doc_dest = $docs_dir . '/' . $doc_filename;
                            if (move_uploaded_file($doc_tmp, $doc_dest)) {
                                $doc_stmt->execute([
                                    $novedad_id,
                                    $doc_name,
                                    '/uploads/novedades/docs/' . $doc_filename,
                                    $doc_mime,
                                    $doc_size
                                ]);
                            } else {
                                error_log("AMA doc upload failed: source=$doc_tmp dest=$doc_dest");
                            }
                        }
                    }

                    $msg_novedad = '<div class="admin-alert admin-alert-success">Noticia publicada correctamente.</div>';

                    // Send Internal Email Notification
                    try {
                        require_once dirname(__DIR__, 3) . '/src/config/config.php';
                        require_once dirname(__DIR__, 3) . '/src/core/email.php';
                        $emailService = new EmailService();
                        $destacadaTxt = $destacada ? ' ⭐ DESTACADA' : '';
                        $subject = "Nueva Noticia Publicada{$destacadaTxt} — {$titulo}";
                        $htmlBody = EmailService::buildHtmlTemplate($subject, "
                            <p>Se ha publicado una nueva noticia corporativa en el sitio web.</p>
                            <ul>
                                <li><strong>Título:</strong> " . htmlspecialchars($titulo) . "</li>
                                <li><strong>Bajada:</strong> " . htmlspecialchars($bajada) . "</li>
                                <li><strong>Categoría:</strong> {$categoria}</li>
                                <li><strong>Destacada:</strong> " . ($destacada ? 'Sí' : 'No') . "</li>
                            </ul>
                            <p><em>La noticia ya se encuentra visible en la sección de Novedades del sitio.</em></p>
                        ");
                        $emailService->sendNotification('cs', $subject, $htmlBody);
                    } catch (\Exception $e) {
                        error_log("Email error (novedades): " . $e->getMessage());
                    }
                } catch (\PDOException $e) {
                    $msg_novedad = '<div class="admin-alert admin-alert-error">Error al guardar la noticia en la base de datos.</div>';
                }
            }
        } else {
            $msg_novedad = '<div class="admin-alert admin-alert-warning">El título y la bajada son obligatorios.</div>';
        }
    }
}

?>
<div style="display:flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-start;">

    <!-- Columna izquierda: Schedules -->
    <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Upload Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="material-symbols-outlined">calendar_month</span>
                <h2 style="margin:0; font-size:1.1rem;">Subida de Schedules</h2>
            </div>
            <p style="color: var(--admin-text-muted); margin: 0 0 1rem;">El sistema usa SimpleXLSX nativo para procesar las hojas de cálculo.</p>

            <?php echo $mensaje; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="form-group">
                    <label class="form-label" for="excel">Archivo Excel de la Línea Marítima</label>
                    <div class="upload-dropzone" onclick="document.getElementById('excel').click()">
                        <span class="material-symbols-outlined" style="font-size:2rem;color:var(--admin-text-light);">upload_file</span>
                        <p style="margin:0.5rem 0 0; color:var(--admin-text-muted); font-size:0.9rem;">Haga clic o arrastre su archivo .xlsx / .xls aquí</p>
                        <input type="file" id="excel" name="excel_schedules" accept=".xlsx, .xls" required style="display:none;" onchange="this.closest('.upload-dropzone').querySelector('p').textContent = this.files[0]?.name || 'Sin archivo'">
                    </div>
                    <small style="color:var(--admin-text-light);">Lee buques en filas 2–3 (cols B/E/H/K), escalas de puerto de fila 5 a 21. Solo los 4 próximos buques.</small>
                </div>
                <button type="submit" class="btn-admin-primary">
                    <span class="material-symbols-outlined">cloud_upload</span>
                    Procesar y Publicar Schedules
                </button>
            </form>
        </div>

        <!-- Historial -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="material-symbols-outlined">history</span>
                <h3 style="margin:0; font-size:1rem;">Últimas Importaciones Activas</h3>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Fecha Subida</th>
                            <th>Sem.</th>
                            <th>Servicio</th>
                            <th>Buques</th>
                            <th>Escalas</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($importaciones)): ?>
                            <tr><td colspan="6" style="text-align:center; color:var(--admin-text-muted);">No hay importaciones registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($importaciones as $imp): ?>
                            <tr>
                                <td><strong><?php echo date('d/m/Y H:i', strtotime($imp['creado_en'])); ?></strong></td>
                                <td><span class="badge badge-blue"><?php echo (int)$imp['week_number']; ?></span></td>
                                <td style="font-size:0.85rem;"><?php echo htmlspecialchars($imp['service_name']); ?></td>
                                <td><span class="badge badge-gold"><?php echo (int)$imp['total_buques']; ?></span></td>
                                <td><?php echo (int)$imp['total_escalas']; ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('¿Eliminar este lote completo y todas sus escalas?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="purgar_hash" value="<?php echo htmlspecialchars($imp['hash']); ?>">
                                        <button type="submit" class="btn-admin-danger" style="padding:0.35rem 0.75rem; font-size:0.8rem;">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p style="font-size:0.8rem; color:var(--admin-text-light); margin:0.75rem 0 0;">Puede coexistir más de un lote, pero lo habitual es eliminar el anterior antes de subir uno nuevo.</p>
        </div>
    </div>

    <!-- Columna derecha: Crear Noticia -->
    <div style="flex: 1; min-width: 300px;">
        <div class="news-form-card">
            <div class="admin-card-header">
                <span class="material-symbols-outlined">newsmode</span>
                <h2 style="margin:0; font-size:1.1rem;">Publicar Nueva Noticia</h2>
            </div>
            <p style="color:var(--admin-text-muted); margin: 0 0 1.25rem; font-size:0.9rem;">La noticia se publicará en la sección de Novedades del sitio.</p>

            <?php echo $msg_novedad; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="nueva_novedad" value="1">

                <div class="form-group">
                    <label class="form-label" for="nov_titulo">Título <span style="color:#dc2626">*</span></label>
                    <input type="text" id="nov_titulo" name="nov_titulo" class="admin-input" placeholder="Ej: AMA amplía rutas hacia el Pacífico" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nov_bajada">Bajada / Resumen <span style="color:#dc2626">*</span></label>
                    <textarea id="nov_bajada" name="nov_bajada" class="admin-textarea" rows="2" placeholder="Breve descripción que aparece en la lista de novedades..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nov_cuerpo">Cuerpo (opcional)</label>
                    <textarea id="nov_cuerpo" name="nov_cuerpo" class="admin-textarea" rows="4" placeholder="Texto completo de la noticia..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nov_imagen">Imagen destacada</label>
                    <div class="upload-dropzone" onclick="document.getElementById('nov_imagen').click()" style="padding:1rem;">
                        <span class="material-symbols-outlined" style="font-size:1.5rem;color:var(--admin-text-light);">image</span>
                        <p style="margin:0.25rem 0 0; color:var(--admin-text-muted); font-size:0.85rem;">Haga clic para seleccionar una imagen (JPG, PNG, WebP — máx. 2 MB)</p>
                        <input type="file" id="nov_imagen" name="nov_imagen" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="this.closest('.upload-dropzone').querySelector('p').textContent = this.files[0]?.name || 'Sin archivo'">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nov_documentos">Documentos adjuntos</label>
                    <div class="upload-dropzone" onclick="document.getElementById('nov_documentos').click()" style="padding:1rem;">
                        <span class="material-symbols-outlined" style="font-size:1.5rem;color:var(--admin-text-light);">attach_file</span>
                        <p style="margin:0.25rem 0 0; color:var(--admin-text-muted); font-size:0.85rem;">PDF, Word o Excel — máx. 10 MB cada uno (puede seleccionar varios)</p>
                        <input type="file" id="nov_documentos" name="nov_documentos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx" style="display:none;" onchange="const c = this.files.length; this.closest('.upload-dropzone').querySelector('p').textContent = c ? c + ' archivo(s) seleccionado(s)' : 'Sin archivos'">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="nov_categoria">Categoría</label>
                        <select id="nov_categoria" name="nov_categoria" class="admin-select">
                            <option value="Servicios">Servicios</option>
                            <option value="Tecnología">Tecnología</option>
                            <option value="Sustentabilidad">Sustentabilidad</option>
                            <option value="Información">Información</option>
                            <option value="Eventos">Eventos</option>
                            <option value="Comunicados">Comunicados</option>
                            <option value="Logística">Logística</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="nov_etiquetas">Etiquetas</label>
                        <input type="text" id="nov_etiquetas" name="nov_etiquetas" class="admin-input" placeholder="Ro-Ro, Rutas (separadas por coma)">
                    </div>
                </div>

                <div class="form-group" style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1.25rem;">
                    <input type="checkbox" id="nov_destacada" name="nov_destacada" style="width:1rem; height:1rem; accent-color: var(--admin-gold);">
                    <label for="nov_destacada" style="margin:0; font-weight:500; font-size:0.9rem;">Marcar como noticia destacada</label>
                </div>

                <button type="submit" class="btn-admin-success">
                    <span class="material-symbols-outlined">publish</span>
                    Publicar Noticia
                </button>
            </form>
        </div>
    </div>

</div>
