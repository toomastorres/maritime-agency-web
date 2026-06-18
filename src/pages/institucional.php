<?php
// Load vacancies from DB
$vacantes = [];
try {
    require_once __DIR__ . '/../core/database.php';
    $stmt = $pdo->query("SELECT * FROM vacancies WHERE activo = 1 ORDER BY creado_en DESC");
    $vacantes = $stmt->fetchAll();
} catch (Exception $e) { $vacantes = []; }

function getDeptKey(string $dept): string {
    $map = [
        'Gerencia General' => 'gerencia-general',
        'Recepcion' => 'recepcion',
        'Finanzas' => 'finanzas',
        'Comercial' => 'comercial',
        'Operaciones' => 'operaciones',
        'Documentacion' => 'documentacion',
        'Equipment' => 'equipment'
    ];
    return $map[$dept] ?? strtolower(trim(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $dept), '-'));
}
?>
<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — institucional.php                              -->
<!-- Página institucional y oportunidades laborales            -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Institucional y Oportunidades | AMA Agencies Argentina</title>
    <meta name="description"
        content="Conoce nuestra misión, visión y oportunidades de carrera en AMA Agencies Argentina. Únete a nuestro equipo multinacional de transporte marítimo.">
    <meta name="keywords"
        content="AMA institucional, misión, visión, recursos humanos, empleos, marítimo, naviera">
    <meta name="author" content="AMA Agencies Argentina S.A.">
    <meta name="robots" content="index, follow">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Institucional | AMA Agencies Argentina">
    <meta property="og:description" content="Nuestra misión, visión y oportunidades de carrera en el Grupo AMA.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/GGroupCMYK.png">
    <meta property="og:url" content="https://ama-demo.example/institucional">
    <meta property="og:site_name" content="AMA Agencies Argentina">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/img/AMA_bandera_azul.png">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/G-blu-scaled.png">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://ama-demo.example/institucional">

    <!-- Specific CSS for Internal Pages -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/institucional.css'); ?>">
</head>

<body class="has-hero">
    <!-- HEADER -->
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main">

        <!-- PAGE HERO -->
        <header class="hero hero--page" aria-labelledby="institucional-title">
            <div class="hero__bg-image" style="background-image: url('<?php echo ASSETS_URL; ?>/img/grande_nigeria_lado.png');"></div>
            <div class="hero__overlay"></div>
            <div class="hero__content contenedor-principal">
                <span class="hero__badge">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor" aria-hidden="true"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
                    <span data-i18n="institutional.hero_badge">Nuestra Historia</span>
                </span>
                <h1 class="hero__title" id="institucional-title" data-i18n="institutional.title">Sé parte de AMA Agencies Argentina</h1>
                <p class="hero__subtitle" data-i18n="institutional.description">
                    Creemos que nuestra fortaleza radica en la combinación de tradición, excelencia y visión de futuro.
                    Conoce más sobre nuestra cultura y las oportunidades para crecer con nosotros.
                </p>
            </div>
        </header>

        <!-- MISSION & VISION -->
        <section class="section-wrapper mv-section">
            <div class="contenedor-principal">

                <!-- Mission & Vision: side by side cards -->
                <div class="mv-cards-row">
                    <div class="mv-card mv-card--mission">
                        <div class="mv-card__icon-wrap">
                            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/></svg>
                        </div>
                        <h3 class="mv-card__title" data-i18n="institutional.mission_badge">Misión</h3>
                        <p class="mv-card__text" data-i18n="institutional.mission_text">
                            Brindar soluciones integrales de transporte marítimo y logística, conectando a Argentina con el mundo de manera eficiente, confiable y sostenible, contribuyendo al desarrollo del comercio exterior y generando valor para nuestros clientes, colaboradores y la comunidad.
                        </p>
                    </div>
                    <div class="mv-card mv-card--vision">
                        <div class="mv-card__icon-wrap mv-card__icon-wrap--gold">
                            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                        </div>
                        <h3 class="mv-card__title" data-i18n="institutional.vision_badge">Visión</h3>
                        <p class="mv-card__text" data-i18n="institutional.vision_text">
                            Ser el referente en soluciones de transporte marítimo en América del Sur, reconocidos por nuestra excelencia operativa, innovación tecnológica y compromiso con la sustentabilidad, superando las expectativas de nuestros clientes en cada operación.
                        </p>
                    </div>
                </div>

                <!-- Values: full-width 4-column grid -->
                <div class="values-section">
                    <div class="section-header section-header--centered" style="margin-bottom: 2rem;">
                        <span class="mv-badge">
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            <span data-i18n="institutional.values_badge">Nuestros Valores</span>
                        </span>
                    </div>
                    <div class="values-grid-full">
                        <div class="value-card-v2">
                            <div class="value-card-v2__icon">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                            </div>
                            <h4 data-i18n="institutional.val_1_title">Confiabilidad</h4>
                            <p data-i18n="institutional.val_1_desc">Cumplimos con nuestros compromisos, garantizando la seguridad y puntualidad en cada operación.</p>
                        </div>
                        <div class="value-card-v2">
                            <div class="value-card-v2__icon">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            </div>
                            <h4 data-i18n="institutional.val_2_title">Orientación al Cliente</h4>
                            <p data-i18n="institutional.val_2_desc">Escuchamos y entendemos las necesidades de nuestros clientes para ofrecer soluciones personalizadas.</p>
                        </div>
                        <div class="value-card-v2">
                            <div class="value-card-v2__icon">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm4.24 16L12 15.45 7.77 18l1.12-4.81-3.73-3.23 4.92-.42L12 5l1.92 4.53 4.92.42-3.73 3.23L16.23 18z"/></svg>
                            </div>
                            <h4 data-i18n="institutional.val_3_title">Integridad</h4>
                            <p data-i18n="institutional.val_3_desc">Actuamos con transparencia y ética en todas nuestras relaciones comerciales y profesionales.</p>
                        </div>
                        <div class="value-card-v2">
                            <div class="value-card-v2__icon">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 008 20c9 0 10-8 10-8-1 2-6 3-9 1 0 0 2-5 9-3l1 1c0-2-1-5-4-6.8L15 3c0 0-1.5 2-2 3.2A6 6 0 0017 8z"/></svg>
                            </div>
                            <h4 data-i18n="institutional.val_4_title">Sostenibilidad</h4>
                            <p data-i18n="institutional.val_4_desc">Comprometidos con prácticas responsables que minimicen el impacto ambiental de nuestras operaciones.</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- CAREER OPPORTUNITIES & VACANCIES -->
        <section class="section-wrapper careers-section" id="vacantes-seccion">
            <div class="contenedor-principal">
                <div class="section-header section-header--centered">
                    <h2 class="titulo-principal" data-i18n="institutional.career_title">Trabajá con Nosotros</h2>
                    <p class="cuerpo-contenido careers-intro" data-i18n="institutional.career_desc">
                        Sumate a un equipo líder en transporte marítimo. Conocé nuestras vacantes disponibles o enviá tu CV para futuras oportunidades.
                    </p>
                </div>

                <div class="careers-layout">

                    <!-- Left: Vacancies -->
                    <div class="careers-vacancies">
                        <h3 class="careers-col-title">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="var(--AMA-gold)"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
                            <span data-i18n="institutional.vacancies_title">Vacantes Disponibles</span>
                            <span class="careers-count" id="careers-count"><?php echo count($vacantes); ?></span>
                        </h3>

                        <div id="vacantes-container" aria-live="polite">
                            <?php if (empty($vacantes)): ?>
                                <!-- no active vacancies - message shown by #no-vacantes below -->
                            <?php else: ?>
                                <?php foreach ($vacantes as $v): ?>
                                <div class="vacante-card">
                                    <div class="vacante-card__header">
                                        <h4 class="vacante-card__title"><?php echo htmlspecialchars($v['titulo'] ?? $v['title'] ?? 'Vacante'); ?></h4>
                                        <?php if (!empty($v['departamento']) || !empty($v['area'])): ?>
                                        <span class="vacante-card__dept" data-i18n="departments.<?php echo getDeptKey($v['departamento'] ?? $v['area'] ?? ''); ?>"><?php echo htmlspecialchars($v['departamento'] ?? $v['area']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($v['ubicacion']) || !empty($v['location'])): ?>
                                    <p class="vacante-card__location">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                        <span><?php echo htmlspecialchars($v['ubicacion'] ?? $v['location']); ?></span>
                                    </p>
                                    <?php endif; ?>
                                    <a href="mailto:rrhh@ama-demo.example?subject=Postulaci%C3%B3n%3A%20<?php echo rawurlencode($v['titulo'] ?? $v['title'] ?? 'Vacante'); ?>" class="boton-primario" data-i18n="institutional.btn_apply" style="font-size:0.85rem; padding:0.5rem 1rem; margin-top:0.75rem; display:inline-block;">Postularme</a>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div id="no-vacantes" class="card-info"<?php echo !empty($vacantes) ? ' style="display:none;"' : ''; ?>>
                                <h3 class="titulo-contenido" data-i18n="institutional.no_vacancies_title">Actualmente no tenemos búsquedas activas.</h3>
                                <p class="texto-info" data-i18n="institutional.no_vacancies_desc">De todas formas, si quieres formar parte de nuestro equipo, te invitamos a que nos envíes tu CV para tenerte en cuenta en futuras oportunidades.</p>
                            </div>
                    </div>

                    <!-- Right: Spontaneous Application -->
                    <div class="careers-spontaneous">
                        <div class="card-AMA spontaneous-card">
                            <h3 class="careers-col-title">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="var(--AMA-gold)"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                                <span data-i18n="institutional.spontaneous_title">Postulación Espontánea</span>
                            </h3>
                        <!-- Spontaneous application: simple mailto CTA -->
                        <div class="spontaneous-mailto-cta">
                            <div class="spontaneous-icon">
                                <svg viewBox="0 0 24 24" width="40" height="40" fill="var(--AMA-gold)"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                            </div>
                            <h4 class="titulo-contenido" data-i18n="institutional.spontaneous_title">Postulación Espontánea</h4>
                            <p class="texto-info" data-i18n="institutional.spontaneous_text">¿No encontrás una vacante acorde a tu perfil? Enviános tu CV y te contactaremos cuando surja una oportunidad.</p>
                            <a href="mailto:rrhh@ama-demo.example?subject=Postulaci%C3%B3n%20Espont%C3%A1nea" class="boton-primario" data-i18n="institutional.spontaneous_btn" style="display:inline-block; margin-top:1rem;">
                                Enviar CV por Email
                            </a>
                            <p class="texto-info" data-i18n="institutional.spontaneous_address_label" style="margin-top:0.75rem; font-size:0.85rem; opacity:0.8;">rrhh@ama-demo.example</p>
                        </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <!-- JavaScript Base -->
    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>

</body>

</html>

