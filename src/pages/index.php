<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — index.php                                      -->
<!-- Página principal (Homepage) — Redesign Completo          -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMA Agencies Argentina — Transporte Marítimo y Logística Internacional</title>
    <meta name="description" content="AMA Agencies Argentina S.A. representa al Grupo AMA, multinacional italiana con más de 75 años conectando mercados a través de rutas marítimas eficientes desde Terminal Zárate.">
    <meta name="keywords" content="AMA, transporte marítimo, logística, Argentina, contenedores, carga proyecto, vehículos, Terminal Zárate, Ro-Ro, buques">
    <meta name="author" content="AMA Agencies Argentina S.A.">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:title" content="AMA Agencies Argentina — Transporte Marítimo">
    <meta property="og:description" content="Soluciones integrales de transporte marítimo y logística internacional desde y hacia Argentina. Terminal Zárate.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/grande_amburgo.png">
    <meta property="og:url" content="https://ama-demo.example">
    <meta property="og:site_name" content="AMA Agencies Argentina">
    <meta property="og:locale" content="es_AR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="AMA Agencies Argentina">
    <meta name="twitter:description" content="Soluciones de transporte marítimo y logística internacional desde Terminal Zárate.">
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/img/AMA_bandera_azul.png">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/G-blu-scaled.png">
    <link rel="canonical" href="https://ama-demo.example">
    <link rel="preload" as="image" href="<?php echo ASSETS_URL; ?>/img/grande_amburgo.png">
    <link rel="stylesheet" href="<?php echo assetUrl('css/index.css'); ?>">
</head>

<body class="has-hero">

    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main">

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- SECCIÓN 1: HERO                                       -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="idx-hero" aria-labelledby="hero-title" id="hero">
            <div class="idx-hero__parallax-wrap" id="heroParallaxWrap">
                <img
                    class="idx-hero__bg-img"
                    src="<?php echo ASSETS_URL; ?>/img/grande_amburgo.png"
                    alt="Buque AMA Grande Amburgo navegando en alta mar"
                    width="1920" height="1080"
                    id="heroParallaxImg"
                    fetchpriority="high"
                >
            </div>
            <div class="idx-hero__overlay" aria-hidden="true"></div>

            <div class="idx-hero__content contenedor-principal">
                <!-- Badge con typewriter -->
                <div class="idx-hero__badge" aria-live="polite" aria-atomic="true" id="heroBadgeConfig" data-i18n-words="hero.words">
                    <span class="idx-hero__badge-dot" aria-hidden="true"></span>
                    <span class="idx-hero__badge-text" id="heroTypewriterText">Conectividad</span>
                </div>

                <h1 class="idx-hero__title" id="hero-title" data-i18n="hero.title">
                    Bienvenidos a<br>
                    <span class="idx-hero__title-accent">AMA Agencies</span><br>
                    Argentina
                </h1>

                <p class="idx-hero__subtitle" data-i18n="hero.subtitle">
                    Conectamos mercados a través de rutas marítimas eficientes y confiables,
                    brindando soluciones integrales de transporte global.
                </p>

                <div class="idx-hero__cta-group">
                    <a href="<?php echo BASE_URL; ?>tipo-de-cambio" class="boton-oro idx-cta-primary" data-i18n="hero.cta_exchange">
                        <svg viewBox="0 0 24 24" aria-hidden="true" width="18" height="18"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        Tipo de Cambio del Día
                    </a>
                    <a href="<?php echo BASE_URL; ?>schedule" class="boton-ghost idx-cta-secondary" data-i18n="hero.cta_schedule">
                        <svg viewBox="0 0 24 24" aria-hidden="true" width="18" height="18"><path fill="currentColor" d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                        Ver Schedules
                    </a>
                </div>
            </div>

            <!-- Indicador de scroll -->
            <a href="#nosotros" class="idx-hero__scroll-cta" aria-label="Desplazarse hacia abajo" tabindex="0">
                <svg viewBox="0 0 24 24" aria-hidden="true" width="24" height="24">
                    <path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                </svg>
            </a>
        </section>


        <!-- ══════════════════════════════════════════════════════ -->
        <!-- SECCIÓN 2: NOSOTROS + ESTADÍSTICAS                    -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="idx-about" aria-labelledby="about-title" id="nosotros">
            <div class="idx-about__inner contenedor-principal">

                <!-- Columna izquierda: texto -->
                <div class="idx-about__text fade-in-up">
                    <span class="section-label-gold" data-i18n="about.label">Nuestra Historia</span>
                    <h2 class="idx-about__title" id="about-title" data-i18n="about.title">
                        +20 años conectando Argentina con el mundo
                    </h2>
                    <div class="idx-about__divider" aria-hidden="true"></div>
                    <p class="idx-about__body" data-i18n="about.p1">
                        AMA Agencies Argentina S.A. representa al <strong>Grupo AMA</strong>, una de las multinacionales italianas líderes en el transporte marítimo a nivel mundial, con sede en Nápoles y presencia en más de 130 puertos alrededor del globo.
                    </p>
                    <p class="idx-about__body" data-i18n="about.p2">
                        Desde nuestra base en <strong>Terminal Zárate</strong> — el hub estratégico del Río de la Plata — conectamos Argentina y Sudamérica con Europa, África y Asia, transportando vehículos, carga proyecto y contenedores con los más altos estándares de la industria marítima internacional.
                    </p>
                    <div class="idx-about__badges">
                        <span class="idx-about__badge-item" data-i18n="about.badge1">
                            <svg viewBox="0 0 24 24" aria-hidden="true" width="14" height="14"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            Terminal Zárate
                        </span>
                        <span class="idx-about__badge-item" data-i18n="about.badge2">
                            <svg viewBox="0 0 24 24" aria-hidden="true" width="14" height="14"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96z"/></svg>
                            Red Global
                        </span>
                        <span class="idx-about__badge-item" data-i18n="about.badge3">
                            <svg viewBox="0 0 24 24" aria-hidden="true" width="14" height="14"><path fill="currentColor" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                            Grupo AMA
                        </span>
                    </div>
                </div>

                <!-- Columna derecha: estadísticas animadas -->
                <div class="idx-stats" id="statsSection" aria-label="Estadísticas del Grupo AMA">
                    <div class="idx-stat-card fade-in-up" style="--delay: 0.05s">
                        <div class="idx-stat-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm4.24 16L12 15.45 7.77 18l1.12-4.81-3.73-3.23 4.92-.42L12 5l1.92 4.53 4.92.42-3.73 3.23L16.23 18z"/></svg>
                        </div>
                        <div class="idx-stat-card__number" data-target="20" data-suffix="+">0+</div>
                        <div class="idx-stat-card__label" data-i18n="stats.years">Años de Experiencia</div>
                    </div>

                    <div class="idx-stat-card fade-in-up" style="--delay: 0.15s">
                        <div class="idx-stat-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        </div>
                        <div class="idx-stat-card__number" data-target="130" data-suffix="+">0+</div>
                        <div class="idx-stat-card__label" data-i18n="stats.ports">Puertos Conectados</div>
                    </div>

                    <div class="idx-stat-card fade-in-up" style="--delay: 0.25s">
                        <div class="idx-stat-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>
                        </div>
                        <div class="idx-stat-card__number" data-target="30" data-suffix="+">0+</div>
                        <div class="idx-stat-card__label" data-i18n="stats.countries">Países Alcanzados</div>
                    </div>

                    <div class="idx-stat-card fade-in-up" style="--delay: 0.35s">
                        <div class="idx-stat-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M20 21c-1.39 0-2.78-.47-4-1.32-2.44 1.71-5.56 1.71-8 0C6.78 20.53 5.39 21 4 21H2v2h2c1.38 0 2.74-.35 4-.99 2.52 1.29 5.48 1.29 8 0 1.26.65 2.62.99 4 .99h2v-2h-2zM3.95 19H4c1.6 0 3.02-.88 4-2 .98 1.12 2.4 2 4 2s3.02-.88 4-2c.98 1.12 2.4 2 4 2h.05l1.89-6.68c.08-.26.06-.54-.06-.78s-.34-.42-.6-.5L20 10.62V6c0-1.1-.9-2-2-2h-3V1H9v3H6c-1.1 0-2 .9-2 2v4.62l-1.29.42c-.26.08-.48.26-.6.5s-.15.52-.06.78L3.95 19z"/></svg>
                        </div>
                        <div class="idx-stat-card__number" data-target="150" data-suffix="+">0+</div>
                        <div class="idx-stat-card__label" data-i18n="stats.vessels">Buques en Flota</div>
                    </div>
                </div>

            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════ -->
        <!-- SECCIÓN 3: SERVICIOS PREVIEW                          -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="idx-services" aria-labelledby="services-title" id="servicios">
            <div class="contenedor-principal">
                <div class="idx-section-header fade-in-up">
                    <span class="section-label-gold" data-i18n="services_preview.label">Nuestros Servicios</span>
                    <h2 class="idx-section-title" id="services-title" data-i18n="services_preview.title">
                        Tipos de Carga que Operamos
                    </h2>
                    <div class="section-divider-gold" style="margin: 1rem 0 0 0;"></div>
                </div>

                <div class="idx-services__grid">

                    <!-- Carga Rodante -->
                    <article class="idx-service-card fade-in-up" style="--delay: 0.0s">
                        <div class="idx-service-card__icon-wrap" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="36" height="36">
                                <path fill="currentColor" d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                            </svg>
                        </div>
                        <div class="idx-service-card__tag" data-i18n="services_preview.card1_tag">Vehiculos</div>
                        <h3 class="idx-service-card__title" data-i18n="services_preview.card_1_title">Carga Rodante</h3>
                        <p class="idx-service-card__desc" data-i18n="services_preview.card_1_desc">
                            Transporte especializado para vehículos, maquinaria autopropulsada y equipos sobre ruedas. Operamos con los más altos estándares de seguridad en cada viaje.
                        </p>
                        <a href="<?php echo BASE_URL; ?>servicios#ro-ro" class="idx-service-card__link" aria-label="Ver más sobre Carga Rodante" data-i18n="services_preview.btn_more">
                            Ver más
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                        </a>
                    </article>

                    <!-- Carga Proyecto -->
                    <article class="idx-service-card fade-in-up" style="--delay: 0.12s">
                        <div class="idx-service-card__icon-wrap" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="36" height="36">
                                <path fill="currentColor" d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                            </svg>
                        </div>
                        <div class="idx-service-card__tag" data-i18n="services_preview.card2_tag">Proyecto</div>
                        <h3 class="idx-service-card__title" data-i18n="services_preview.card_2_title">Carga Proyecto</h3>
                        <p class="idx-service-card__desc" data-i18n="services_preview.card_2_desc">
                            Soluciones para cargas sobredimensionadas, equipos industriales y proyectos de ingeniería. Cada operación es diseñada a medida para su carga.
                        </p>
                        <a href="<?php echo BASE_URL; ?>servicios#proyecto" class="idx-service-card__link" aria-label="Ver más sobre Carga Proyecto" data-i18n="services_preview.btn_more">
                            Ver más
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                        </a>
                    </article>

                    <!-- Contenedores -->
                    <article class="idx-service-card fade-in-up" style="--delay: 0.24s">
                        <div class="idx-service-card__icon-wrap" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="36" height="36">
                                <path fill="currentColor" d="M20.5 11H19V7c0-1.1-.9-2-2-2h-4V3.5C13 2.12 11.88 1 10.5 1S8 2.12 8 3.5V5H4c-1.1 0-1.99.9-1.99 2v3.8H3.5c1.49 0 2.7 1.21 2.7 2.7s-1.21 2.7-2.7 2.7H2V20c0 1.1.9 2 2 2h3.8v-1.5c0-1.49 1.21-2.7 2.7-2.7 1.49 0 2.7 1.21 2.7 2.7V22H17c1.1 0 2-.9 2-2v-4h1.5c1.38 0 2.5-1.12 2.5-2.5S21.88 11 20.5 11z"/>
                            </svg>
                        </div>
                        <div class="idx-service-card__tag" data-i18n="services_preview.card3_tag">Container</div>
                        <h3 class="idx-service-card__title" data-i18n="services_preview.card_3_title">Contenedores</h3>
                        <p class="idx-service-card__desc" data-i18n="services_preview.card_3_desc">
                            Opciones versátiles en 20ft Dry, Tank y 40ft High Cube para su mercadería. Soluciones estándar con la confiabilidad de una línea marítima global.
                        </p>
                        <a href="<?php echo BASE_URL; ?>servicios#contenedores" class="idx-service-card__link" aria-label="Ver más sobre Contenedores" data-i18n="services_preview.btn_more">
                            Ver más
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                        </a>
                    </article>

                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════ -->
        <!-- SECCIÓN 4: ¿POR QUÉ AMA?                        -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="idx-why" aria-labelledby="why-title" id="por-que">
            <div class="idx-why__deco-left" aria-hidden="true"></div>
            <div class="idx-why__deco-right" aria-hidden="true"></div>
            <div class="contenedor-principal">
                <div class="idx-section-header idx-section-header--light fade-in-up">
                    <span class="idx-section-label-light" data-i18n="why.label">Nuestra Propuesta de Valor</span>
                    <h2 class="idx-section-title idx-section-title--light" id="why-title" data-i18n="why.title">
                        ¿Por qué elegirnos?
                    </h2>
                    <div class="idx-divider-gold" aria-hidden="true"></div>
                </div>

                <div class="idx-why__grid">

                    <div class="idx-why-card fade-in-up" style="--delay: 0.0s">
                        <div class="idx-why-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M12 2L1 7l11 5 9-4.09V17h2V7L12 2zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
                        </div>
                        <h3 class="idx-why-card__title" data-i18n="why.card1_title">Multinacional Italiana</h3>
                        <p class="idx-why-card__desc" data-i18n="why.card1_desc">Respaldo del Grupo AMA, líder mundial en transporte marítimo con sede en Nápoles y décadas de excelencia operativa global.</p>
                    </div>

                    <div class="idx-why-card fade-in-up" style="--delay: 0.1s">
                        <div class="idx-why-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        </div>
                        <h3 class="idx-why-card__title" data-i18n="why.card2_title">Terminal Zárate</h3>
                        <p class="idx-why-card__desc" data-i18n="why.card2_desc">Operamos en el puerto estratégico del Río de la Plata, conectando Sudamérica con Europa, África Occidental y Asia de forma eficiente.</p>
                    </div>

                    <div class="idx-why-card fade-in-up" style="--delay: 0.2s">
                        <div class="idx-why-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95a15.65 15.65 0 00-1.38-3.56A8.03 8.03 0 0118.92 8zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.987 7.987 0 015.08 16zm2.95-8H5.08a7.987 7.987 0 014.33-3.56A15.65 15.65 0 008.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56a15.65 15.65 0 001.38-3.56h2.95a8.03 8.03 0 01-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>
                        </div>
                        <h3 class="idx-why-card__title" data-i18n="why.card3_title">Red de 130+ Puertos</h3>
                        <p class="idx-why-card__desc" data-i18n="why.card3_desc">Conectamos con los principales hubs de Europa, África Occidental y Asia, brindando opciones de trasbordo flexibles para su carga.</p>
                    </div>

                    <div class="idx-why-card fade-in-up" style="--delay: 0.3s">
                        <div class="idx-why-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                        </div>
                        <h3 class="idx-why-card__title" data-i18n="why.card4_title">Equipo Especializado</h3>
                        <p class="idx-why-card__desc" data-i18n="why.card4_desc">Personal con formación y experiencia en logística internacional, disponible para asesorar y acompañar cada operación de principio a fin.</p>
                    </div>

                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════ -->
        <!-- SECCIÓN 5: FAQs                                       -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="idx-faq" aria-labelledby="faq-title" id="faq">
            <div class="contenedor-principal">

                <div class="idx-section-header fade-in-up">
                    <span class="section-label-gold" data-i18n="faq.section_label">Preguntas Frecuentes</span>
                    <h2 class="idx-section-title" id="faq-title" data-i18n="faq.title">
                        Respuestas rápidas para sus consultas
                    </h2>
                    <p class="idx-faq__subtitle" data-i18n="faq.subtitle">
                        Todo lo que necesita saber sobre nuestros servicios, operaciones y contactos.
                    </p>
                    <div class="section-divider-gold" style="margin: 1rem 0 0 0;"></div>
                </div>

                <div class="idx-faq__accordion" id="faqAccordion">

                    <div class="idx-faq-item">
                        <button class="idx-faq-btn" aria-expanded="false" data-i18n="faq.q1">
                            <span>Ubicación de oficina y nuestros horarios de atención</span>
                            <span class="idx-faq-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </button>
                        <div class="idx-faq-body" hidden>
                            <div class="idx-faq-body__inner">
                                <p data-i18n="faq.a1">25 de Mayo 702, 1er Piso, CABA. <strong>Lunes a Viernes de 9:00hs a 17:00hs.</strong></p>
                            </div>
                        </div>
                    </div>

                    <div class="idx-faq-item">
                        <button class="idx-faq-btn" aria-expanded="false" data-i18n="faq.q2">
                            <span>¿Dónde puedo saber la fecha de arribo o salida de mi carga?</span>
                            <span class="idx-faq-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </button>
                        <div class="idx-faq-body" hidden>
                            <div class="idx-faq-body__inner" data-i18n-html="true" data-i18n="faq.a2">
                                <p>Nuestros schedules se actualizan todos los viernes. <a href="<?php echo BASE_URL; ?>schedule">Ver Schedule →</a></p>
                            </div>
                        </div>
                    </div>

                    <div class="idx-faq-item">
                        <button class="idx-faq-btn" aria-expanded="false" data-i18n="faq.q3">
                            <span>¿Qué documentación debo presentar para mi exportación?</span>
                            <span class="idx-faq-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </button>
                        <div class="idx-faq-body" hidden>
                            <div class="idx-faq-body__inner" data-i18n-html="true" data-i18n="faq.a3">
                                <p>Envíenos un correo a nuestros canales de documentación y finanzas:</p>
                                <p><a href="mailto:doc@ama-demo.example">doc@ama-demo.example</a><br><a href="mailto:finance@ama-demo.example">finance@ama-demo.example</a></p>
                            </div>
                        </div>
                    </div>

                    <div class="idx-faq-item">
                        <button class="idx-faq-btn" aria-expanded="false" data-i18n="faq.q4">
                            <span>¿Dónde consulto el tipo de cambio?</span>
                            <span class="idx-faq-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </button>
                        <div class="idx-faq-body" hidden>
                            <div class="idx-faq-body__inner" data-i18n-html="true" data-i18n="faq.a4">
                                <p><a href="<?php echo BASE_URL; ?>tipo-de-cambio">Ver tipo de cambio →</a> o escribí a <a href="mailto:tc@ama-demo.example">tc@ama-demo.example</a></p>
                            </div>
                        </div>
                    </div>

                    <div class="idx-faq-item">
                        <button class="idx-faq-btn" aria-expanded="false" data-i18n="faq.q5">
                            <span>¿Cuáles son los servicios prestados y en qué puerto operan?</span>
                            <span class="idx-faq-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </button>
                        <div class="idx-faq-body" hidden>
                            <div class="idx-faq-body__inner" data-i18n-html="true" data-i18n="faq.a5">
                                <p>Nuestros buques multipropósito permiten la carga de contenedores, carga proyecto y automóviles. En Argentina, operamos en <strong>Terminal Zárate</strong> por su posición estratégica, conectando con puertos en Brasil, Uruguay, Noroeste de África y Norte de Europa, con posibilidades de trasbordo a otras rutas.</p>
                            </div>
                        </div>
                    </div>

                    <div class="idx-faq-item">
                        <button class="idx-faq-btn" aria-expanded="false" data-i18n="faq.q6">
                            <span>¿Cómo puedo cotizar una carga?</span>
                            <span class="idx-faq-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </button>
                        <div class="idx-faq-body" hidden>
                            <div class="idx-faq-body__inner" data-i18n-html="true" data-i18n="faq.a6">
                                <p>Envíe su consulta al equipo comercial según el tipo de carga:</p>
                                <ul>
                                    <li><strong>Vehículos:</strong> <a href="mailto:customerserv@ama-demo.example">customerserv@ama-demo.example</a></li>
                                    <li><strong>Carga Proyecto:</strong> <a href="mailto:projects@ama-demo.example">projects@ama-demo.example</a></li>
                                    <li><strong>Contenedores:</strong> <a href="mailto:containers@ama-demo.example">containers@ama-demo.example</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="idx-faq-item">
                        <button class="idx-faq-btn" aria-expanded="false" data-i18n="faq.q7">
                            <span>¿Cómo actuar ante la necesidad de un reclamo en mi carga?</span>
                            <span class="idx-faq-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </button>
                        <div class="idx-faq-body" hidden>
                            <div class="idx-faq-body__inner" data-i18n-html="true" data-i18n="faq.a7">
                                <p>Presentar carta de protesta formal, monto reclamado, facturas y fotos. Enviar a <a href="mailto:claims@ama-demo.example">claims@ama-demo.example</a></p>
                            </div>
                        </div>
                    </div>

                    <div class="idx-faq-item">
                        <button class="idx-faq-btn" aria-expanded="false" data-i18n="faq.q8">
                            <span>¿Cómo solicitar una inspección de carga?</span>
                            <span class="idx-faq-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </button>
                        <div class="idx-faq-body" hidden>
                            <div class="idx-faq-body__inner" data-i18n-html="true" data-i18n="faq.a8">
                                <p>Solicitar a <a href="mailto:claims@ama-demo.example">claims@ama-demo.example</a>. Las unidades <strong>NO podrán ser retiradas de Terminal Zárate</strong> hasta tanto no se realice dicha inspección.</p>
                            </div>
                        </div>
                    </div>

                </div><!-- /.idx-faq__accordion -->

            </div>
        </section>

    </main>

    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/index.js'); ?>" defer></script>

</body>
</html>