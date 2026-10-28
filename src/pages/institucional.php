<?php
// Load vacancies from DB
$vacantes = [];
try {
    require_once __DIR__ . '/../core/database.php';
    $stmt = $pdo->query("SELECT * FROM vacancies WHERE activo = 1 ORDER BY creado_en DESC");
    $vacantes = $stmt->fetchAll();
} catch (Exception $e) { $vacantes = []; }

// getDeptKey() venía duplicada acá con su propio mapa de áreas: al cambiar
// la estructura de departamentos esta copia se quedó con la vieja. Se usa la
// del modelo, que es el único origen de verdad del directorio.
require_once __DIR__ . '/../logic/contactos_model.php';
?>
<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — institucional.php                              -->
<!-- Institucional y vacantes — sistema Oltremare                -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Institucional y Oportunidades | Atlantic Maritime Agency</title>
    <meta name="description"
        content="Conoce nuestra misión, visión y oportunidades de carrera en Atlantic Maritime Agency. Únete a nuestro equipo multinacional de transporte marítimo.">
    <meta name="keywords"
        content="AMA institucional, misión, visión, recursos humanos, empleos, marítimo, naviera">
    <meta name="author" content="Atlantic Maritime Agency S.A.">
    <meta name="robots" content="index, follow">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Institucional | Atlantic Maritime Agency">
    <meta property="og:description" content="Nuestra misión, visión y oportunidades de carrera en Atlantic Maritime Group.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/logo-blue.svg">
    <meta property="og:url" content="https://atlantic-agency.example.com/institucional">
    <meta property="og:site_name" content="Atlantic Maritime Agency">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://atlantic-agency.example.com/institucional">

    <!-- Specific CSS for Internal Pages -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/institucional.css'); ?>">
</head>

<body>
    <!-- HEADER -->
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main" class="olt-page">

        <!-- PAGE HERO -->
        <header class="olt-hero-image" aria-labelledby="institucional-title">
            <img class="olt-hero-image__bg" src="<?php echo ASSETS_URL; ?>/img/grande_san_paolo_apaisado.png" alt="">
            <div class="olt-hero-image__overlay" aria-hidden="true"></div>
            <div class="olt-hero-image__content">
                <div class="olt-crumb"><a href="<?php echo BASE_URL; ?>" data-i18n="nav.home">Inicio</a> / <span data-i18n="institutional.title_short">Institucional</span></div>
                <h1 class="olt-h1" id="institucional-title" data-i18n="institutional.title_full">La agencia</h1>
                <p class="olt-hero-image__subtitle" data-i18n="institutional.subtitle_full">Quiénes somos, cómo trabajamos y cómo sumarse al equipo.</p>
            </div>
        </header>

        <!-- HISTORIA + LÍNEA DE TIEMPO -->
        <section class="olt-section">
            <div class="olt-container olt-history">
                <div class="olt-history__text">
                    <h2 class="olt-h2" data-i18n="institutional.history_title">Un grupo italiano con más de setenta y cinco años de historia</h2>
                    <p data-i18n="institutional.history_p1">El Atlantic Maritime Group, con sede en Nápoles, es una de las principales operadoras del transporte marítimo mundial. Atlantic Maritime Agency S.A. lo representa localmente desde hace más de veinte años, con oficina en la Ciudad de Buenos Aires y operación en Terminal Zárate.</p>
                    <p data-i18n="institutional.history_p2">La agencia acompaña a exportadores, importadores, despachantes y forwarders en cada etapa: cotización, reserva de bodega, documentación, embarque, seguimiento y reclamos.</p>
                </div>
                <div class="olt-timeline">
                    <div class="olt-timeline__item">
                        <div class="olt-timeline__year">1947</div>
                        <div>
                            <div class="olt-timeline__title" data-i18n="institutional.tl_1_title">Fundación de Atlantic Maritime Group</div>
                            <div class="olt-timeline__text" data-i18n="institutional.tl_1_text">En Nápoles, Italia — hoy una de las navieras líderes del Mediterráneo y el Atlántico.</div>
                        </div>
                    </div>
                    <div class="olt-timeline__item">
                        <div class="olt-timeline__year">2003</div>
                        <div>
                            <div class="olt-timeline__title" data-i18n="institutional.tl_2_title">Apertura de la agencia en Argentina</div>
                            <div class="olt-timeline__text" data-i18n="institutional.tl_2_text">Representación local del grupo, con oficina en Buenos Aires y operación en Terminal Zárate.</div>
                        </div>
                    </div>
                    <div class="olt-timeline__item">
                        <div class="olt-timeline__year" data-i18n="institutional.tl_3_year">Hoy</div>
                        <div>
                            <div class="olt-timeline__title" data-i18n="institutional.tl_3_title">Equipos propios por área</div>
                            <div class="olt-timeline__text" data-i18n="institutional.tl_3_text">Customer Service, Documentación, Finanzas y Claims, operando desde Terminal Zárate.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MISIÓN, VISIÓN Y VALORES -->
        <section class="olt-section olt-mv">
            <div class="olt-container">
                <div class="olt-mv__cards">
                    <div class="olt-panel olt-mv__card">
                        <div class="olt-kicker--accent" data-i18n="institutional.mission_badge">Misión</div>
                        <p data-i18n="institutional.mission_text">Brindar soluciones integrales de transporte marítimo y logística, conectando a Argentina con el mundo de manera eficiente, confiable y sostenible, contribuyendo al desarrollo del comercio exterior y generando valor para nuestros clientes, colaboradores y la comunidad.</p>
                    </div>
                    <div class="olt-panel olt-mv__card">
                        <div class="olt-kicker--accent" data-i18n="institutional.vision_badge">Visión</div>
                        <p data-i18n="institutional.vision_text">Ser el referente en soluciones de transporte marítimo en América del Sur, reconocidos por nuestra excelencia operativa, innovación tecnológica y compromiso con la sustentabilidad, superando las expectativas de nuestros clientes en cada operación.</p>
                    </div>
                </div>

                <div class="olt-values">
                    <div class="olt-kicker" data-i18n="institutional.values_badge">Nuestros valores</div>
                    <div class="olt-values__grid">
                        <div>
                            <h4 data-i18n="institutional.val_1_title">Confiabilidad</h4>
                            <p data-i18n="institutional.val_1_desc">Cumplimos con nuestros compromisos, garantizando la seguridad y puntualidad en cada operación.</p>
                        </div>
                        <div>
                            <h4 data-i18n="institutional.val_2_title">Orientación al Cliente</h4>
                            <p data-i18n="institutional.val_2_desc">Escuchamos y entendemos las necesidades de nuestros clientes para ofrecer soluciones personalizadas.</p>
                        </div>
                        <div>
                            <h4 data-i18n="institutional.val_3_title">Integridad</h4>
                            <p data-i18n="institutional.val_3_desc">Actuamos con transparencia y ética en todas nuestras relaciones comerciales y profesionales.</p>
                        </div>
                        <div>
                            <h4 data-i18n="institutional.val_4_title">Sostenibilidad</h4>
                            <p data-i18n="institutional.val_4_desc">Comprometidos con prácticas responsables que minimicen el impacto ambiental de nuestras operaciones.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- BÚSQUEDAS ABIERTAS -->
        <section class="olt-careers" id="vacantes-seccion">
            <div class="olt-container">
                <div class="olt-section-head">
                    <div class="olt-section-head__text">
                        <div class="olt-kicker--accent" data-i18n="institutional.career_kicker">Trabajá con nosotros</div>
                        <h2 class="olt-h2" data-i18n="institutional.career_title">Búsquedas abiertas</h2>
                        <p data-i18n="institutional.career_desc">Las vacantes se publican desde el portal interno de RRHH. Podés postularte con el formulario o enviar tu CV por correo.</p>
                    </div>
                    <div class="olt-section-head__aside">
                        <a href="mailto:rrhh@atlantic-agency.example.com?subject=Postulaci%C3%B3n%20Espont%C3%A1nea" class="olt-btn olt-btn--outline" data-i18n="institutional.spontaneous_btn_short">Enviar CV espontáneo</a>
                    </div>
                </div>

                <div id="vacantes-container" class="olt-vacancies" aria-live="polite">
                    <?php if (!empty($vacantes)): foreach ($vacantes as $v):
                        $titulo = $v['titulo'] ?? $v['title'] ?? 'Vacante';
                        $dept   = $v['departamento'] ?? $v['area'] ?? '';
                        $ubic   = $v['ubicacion'] ?? $v['location'] ?? '';
                        $desc   = $v['descripcion'] ?? '';
                        $reqs   = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)($v['requisitos'] ?? ''))));
                        $vid    = (int)($v['id'] ?? 0);
                    ?>
                    <div class="olt-vacancy" data-vacancy-id="<?php echo $vid; ?>">
                        <button type="button" class="olt-vacancy__head" aria-expanded="false">
                            <span class="olt-vacancy__title"><?php echo htmlspecialchars($titulo); ?></span>
                            <span class="olt-vacancy__meta"><span data-i18n="departments.<?php echo getDeptKey($dept); ?>"><?php echo htmlspecialchars($dept); ?></span><?php if ($ubic): ?> · <?php echo htmlspecialchars($ubic); ?><?php endif; ?></span>
                            <span class="olt-vacancy__toggle" aria-hidden="true">+</span>
                        </button>
                        <div class="olt-vacancy__body">
                            <div class="olt-vacancy__body-inner">
                                <div class="olt-vacancy__desc">
                                    <?php if ($desc): ?><p><?php echo nl2br(htmlspecialchars($desc)); ?></p><?php endif; ?>
                                    <?php if (!empty($reqs)): ?>
                                    <div class="olt-kicker" data-i18n="institutional.requirements_label">Requisitos</div>
                                    <ul class="olt-vacancy__reqs">
                                        <?php foreach ($reqs as $r): ?><li><?php echo htmlspecialchars($r); ?></li><?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="olt-vacancy__apply">
                                    <div class="olt-kicker" data-i18n="institutional.apply_label">Postularme</div>
                                    <div class="olt-vacancy__apply-toggle">
                                        <button type="button" class="olt-vacancy__apply-btn is-active" data-mode="form" data-i18n="institutional.apply_form">Con formulario</button>
                                        <button type="button" class="olt-vacancy__apply-btn" data-mode="mail" data-i18n="institutional.apply_mail">Por correo</button>
                                    </div>

                                    <form class="olt-vacancy__form" data-vacancy-id="<?php echo $vid; ?>" data-area="<?php echo htmlspecialchars($dept); ?>" enctype="multipart/form-data" novalidate>
                                        <input type="text" name="website_url" class="olt-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
                                        <input type="hidden" name="vacante_id" value="<?php echo $vid; ?>">
                                        <input type="hidden" name="area" value="<?php echo htmlspecialchars($dept); ?>">
                                        <div class="olt-field"><label data-i18n="institutional.field_nombre">Nombre</label><input type="text" name="nombre" required></div>
                                        <div class="olt-field"><label data-i18n="institutional.field_apellido">Apellido</label><input type="text" name="apellido" required></div>
                                        <div class="olt-field"><label data-i18n="institutional.field_email">Correo electrónico</label><input type="email" name="email" required></div>
                                        <div class="olt-field"><label data-i18n="institutional.field_telefono">Teléfono</label><input type="tel" name="telefono"></div>
                                        <div class="olt-field"><label data-i18n="institutional.field_cv">Currículum (PDF, DOC o DOCX, máx. 5 MB)</label><input type="file" name="cv" accept=".pdf,.doc,.docx" required></div>
                                        <button type="submit" class="olt-btn olt-btn--primary olt-btn--block" data-i18n="institutional.btn_send_application">Enviar postulación</button>
                                        <p class="olt-vacancy__disclaimer" data-i18n="institutional.form_disclaimer">Sus datos se remiten al área de Recursos Humanos y se conservan mientras dure el proceso de selección.</p>
                                        <div class="olt-vacancy__status" role="status" aria-live="polite"></div>
                                    </form>

                                    <div class="olt-vacancy__mailbox" hidden>
                                        <p data-i18n="institutional.mail_intro">Envíe su CV en PDF a la casilla de Recursos Humanos indicando el puesto en el asunto.</p>
                                        <div class="olt-note-rule">
                                            <div class="olt-kicker" data-i18n="institutional.mail_subject_label">Asunto sugerido</div>
                                            <div><span data-i18n="institutional.mail_subject_prefix">Postulación —</span> <?php echo htmlspecialchars($titulo); ?></div>
                                        </div>
                                        <a href="mailto:rrhh@atlantic-agency.example.com?subject=Postulaci%C3%B3n%20%E2%80%94%20<?php echo rawurlencode($titulo); ?>" class="olt-btn olt-btn--primary olt-btn--block">rrhh@atlantic-agency.example.com</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <div id="no-vacantes" class="olt-vacancies__empty">
                        <h3 class="olt-h3" data-i18n="institutional.no_vacancies_title">Actualmente no tenemos búsquedas activas.</h3>
                        <p data-i18n="institutional.no_vacancies_desc">De todas formas, si quieres formar parte de nuestro equipo, te invitamos a que nos envíes tu CV para tenerte en cuenta en futuras oportunidades.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <!-- JavaScript Base -->
    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/institucional.js'); ?>" defer></script>

</body>

</html>
