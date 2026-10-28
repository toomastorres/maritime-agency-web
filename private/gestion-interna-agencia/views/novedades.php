<?php
/**
 * AMA — novedades.php (Admin)
 * Publicación de novedades del sitio.
 *
 * Estaba embebido en la vista de Schedules, en la columna de al lado del
 * importador de Excel: dos tareas sin relación compartiendo pantalla, y la
 * tabla de importaciones quedaba sin ancho suficiente por culpa del reparto.
 * Ahora es su propia sección.
 */
if (!function_exists('is_logged_in')) exit;
if (!isset($pdo)) require_once dirname(__DIR__) . '/includes/db.php';

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

// ¿Hay alguna novedad publicada? Sirve para decir con datos si el módulo se
// está usando.
$novedades_total = 0;
try {
    $novedades_total = (int) $pdo->query("SELECT COUNT(*) FROM novedades")->fetchColumn();
} catch (\PDOException $e) { }
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Novedades</h1>
        <p class="admin-page-sub">Noticias institucionales del sitio.</p>
    </div>
</div>

<!-- Aviso honesto: la home dejó de mostrar el bloque de Novedades y ninguna
     página pública consume /api/novedades hoy. Lo que se publique acá queda
     guardado, pero no se ve en el sitio hasta que se vuelva a habilitar la
     sección. Se avisa en vez de dejar que alguien redacte para la nada. -->
<div class="admin-alert admin-alert-warning">
    <strong>La sección pública de Novedades está desactivada.</strong>
    Las noticias se guardan correctamente, pero hoy ninguna página del sitio
    las muestra: el bloque de Novedades se quitó de la portada. Si querés
    volver a publicarlas hacia afuera, hay que reactivar esa sección en la web.
    <?php if ($novedades_total > 0): ?>
    Hay <?php echo $novedades_total; ?> noticia<?php echo $novedades_total === 1 ? '' : 's'; ?> guardada<?php echo $novedades_total === 1 ? '' : 's'; ?> esperando.
    <?php endif; ?>
</div>

    <div class="news-form-card">
        <div class="admin-card-header">
            <span class="material-symbols-outlined">newsmode</span>
            <h2 class="admin-card-title">Publicar Nueva Noticia</h2>
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
