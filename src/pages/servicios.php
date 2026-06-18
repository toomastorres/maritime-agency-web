<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — servicios.php                                  -->
<!-- Página de servicios logísticos                            -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios Logísticos | AMA Agencies Argentina</title>
    <meta name="description" content="Servicios logísticos integrados de AMA Agencies Argentina. Conoce nuestras rutas, tipos de carga y a nuestro equipo comercial especializado.">
    <meta name="keywords" content="AMA, servicios logísticos, carga rodante, project cargo, contenedores, rutas marítimas, Terminal Zárate">
    <meta name="author" content="AMA Agencies Argentina S.A.">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Servicios Logísticos | AMA Agencies Argentina">
    <meta property="og:description" content="Servicios logísticos integrados. Transporte de vehículos, carga ro-ro, contenedores y carga proyecto.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/GGroupCMYK.png">
    <meta property="og:url" content="https://ama-demo.example/servicios">
    <meta property="og:site_name" content="AMA Agencies Argentina">
    <meta property="og:locale" content="es_AR">
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/img/AMA_bandera_azul.png">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/G-blu-scaled.png">
    <link rel="canonical" href="https://ama-demo.example/servicios">
    <link rel="stylesheet" href="<?php echo assetUrl('css/servicios.css'); ?>">
</head>

<body class="has-hero">
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main">

        <!-- ── HERO ────────────────────────────────────────── -->
        <header class="hero hero--page" aria-labelledby="servicios-hero-title">
            <div class="hero__bg-image" style="background-image: url('<?php echo ASSETS_URL; ?>/img/grande_san_paolo_apaisado.png');"></div>
            <div class="hero__overlay"></div>
            <div class="hero__content contenedor-principal">
                <span class="hero__badge" data-i18n="services_page.badge">Soluciones Logísticas</span>
                <h1 class="hero__title" id="servicios-hero-title" data-i18n="services_page.title">Servicios y Comercial</h1>
                <p class="hero__subtitle" data-i18n="services_page.description">
                    Líderes en transporte marítimo multimodal. Soluciones logísticas integrales
                    que conectan el Cono Sur con los principales mercados globales a través
                    de nuestra red de servicios especializados.
                </p>
            </div>
        </header>

        <!-- ── SERVICES CARDS ─────────────────────────────── -->
        <section class="section-wrapper section-wrapper--alt" id="servicios-cards" aria-labelledby="servicios-cards-title">
            <div class="contenedor-principal">
                <div class="section-header" style="text-align: center; margin-bottom: 3rem;">
                    <span class="section-label-gold" data-i18n="services_page.cards_label">Expertos en Carga</span>
                    <h2 class="titulo-principal" id="servicios-cards-title" data-i18n="services_page.cards_title">Soluciones de Transporte Especializado</h2>
                    <div class="section-divider-gold"></div>
                </div>
                <div class="servicios-cards-grid">

                    <!-- Card 1: Ro-Ro -->
                    <div class="servicio-card-img" id="ro-ro">
                        <div class="servicio-card-img__image" style="background-image: url('<?php echo ASSETS_URL; ?>/img/grande_amburgo.png')"></div>
                        <div class="servicio-card-img__body">
                            <div class="servicio-card-img__header">
                                <svg class="servicio-card-img__icon" viewBox="0 0 24 24" fill="var(--AMA-gold)" aria-hidden="true"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                                <h3 class="titulo-contenido" data-i18n="services_preview.card_1_title">Carga Rodante</h3>
                            </div>
                            <p class="texto-info" data-i18n="services_page.roro_desc">Infraestructura especializada para el transporte seguro de vehículos livianos, pesados y maquinaria agrícola. Nuestra flota de última generación garantiza máxima protección.</p>
                            <a href="mailto:customerserv@ama-demo.example?subject=Solicitud%20de%20Cotizaci%C3%B3n%20-%20Carga%20Rodante" class="boton-primario" data-i18n="services_page.btn_quote">Solicitar Cotización</a>
                        </div>
                    </div>

                    <!-- Card 2: Proyecto -->
                    <div class="servicio-card-img" id="proyecto">
                        <div class="servicio-card-img__image" style="background-image: url('<?php echo ASSETS_URL; ?>/img/grande_francia_frente-1.png')"></div>
                        <div class="servicio-card-img__body">
                            <div class="servicio-card-img__header">
                                <svg class="servicio-card-img__icon" viewBox="0 0 24 24" fill="var(--AMA-gold)" aria-hidden="true"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                                <h3 class="titulo-contenido" data-i18n="services_preview.card_2_title">Carga de Proyecto</h3>
                            </div>
                            <p class="texto-info" data-i18n="services_page.proyecto_desc">Expertos en logística de piezas sobredimensionadas y pesadas para minería, energía e infraestructura. Supervisión técnica especializada en cada movimiento.</p>
                            <a href="mailto:projects@ama-demo.example?subject=Solicitud%20de%20Cotizaci%C3%B3n%20-%20Carga%20Proyecto" class="boton-primario" data-i18n="services_page.btn_quote">Solicitar Cotización</a>
                        </div>
                    </div>

                    <!-- Card 3: Contenedores -->
                    <div class="servicio-card-img" id="contenedores">
                        <div class="servicio-card-img__image" style="background-image: url('<?php echo ASSETS_URL; ?>/img/port-terminal.jpg')"></div>
                        <div class="servicio-card-img__body">
                            <div class="servicio-card-img__header">
                                <svg class="servicio-card-img__icon" viewBox="0 0 24 24" fill="var(--AMA-gold)" aria-hidden="true"><path d="M20.5 11H19V7c0-1.1-.9-2-2-2h-4V3.5C13 2.12 11.88 1 10.5 1S8 2.12 8 3.5V5H4c-1.1 0-1.99.9-1.99 2v3.8H3.5c1.49 0 2.7 1.21 2.7 2.7s-1.21 2.7-2.7 2.7H2V20c0 1.1.9 2 2 2h3.8v-1.5c0-1.49 1.21-2.7 2.7-2.7 1.49 0 2.7 1.21 2.7 2.7V22H17c1.1 0 2-.9 2-2v-4h1.5c1.38 0 2.5-1.12 2.5-2.5S21.88 11 20.5 11z"/></svg>
                                <h3 class="titulo-contenido" data-i18n="services_preview.card_3_title">Contenedores</h3>
                            </div>
                            <p class="texto-info" data-i18n="services_page.contenedores_desc">Servicio completo de transporte de contenedores con seguimiento en tiempo real y soluciones personalizadas para cualquier tipo de carga.</p>
                            <a href="mailto:containers@ama-demo.example?subject=Solicitud%20de%20Cotizaci%C3%B3n%20-%20Contenedores" class="boton-primario" data-i18n="services_page.btn_quote">Solicitar Cotización</a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ── ROUTES MAP SECTION ─────────────────────────── -->
        <section class="routes-map-section" id="rutas" aria-labelledby="rutas-map-title">
            <div class="routes-map-grid">

                <!-- Left: Info -->
                <div class="routes-map-info">
                    <h2 id="rutas-map-title" data-i18n="services_page.map_title">Red Global de Rutas</h2>
                    <p data-i18n="services_page.map_desc">
                        Nuestra red conecta Argentina con los hubs más importantes del mundo,
                        operando en los principales puertos del Atlántico Sur, Europa del Norte,
                        África Occidental y Asia.
                    </p>

                    <div class="route-check-list">
                        <div class="route-check-item">
                            <div class="route-check-item__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                            </div>
                            <span data-i18n="services_page.route_1">Directo a Norte de Europa</span>
                        </div>
                        <div class="route-check-item">
                            <div class="route-check-item__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                            </div>
                            <span data-i18n="services_page.route_2">Conexión West Africa</span>
                        </div>
                        <div class="route-check-item">
                            <div class="route-check-item__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                            </div>
                            <span data-i18n="services_page.route_3">Conectividad Asia (Taicang, Ningbo)</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Interactive SVG Map -->
                <div class="routes-map-visual">
                    <div class="routes-svg-container" id="map-container" role="img" aria-label="Mapa interactivo de rutas marítimas de AMA">
                        <div class="map-loading" id="map-loading" aria-live="polite" data-i18n="services_page.map_loading">Cargando mapa…</div>
                        <svg id="world-map" aria-hidden="true"></svg>
                    </div>
                </div>

            </div>

            <!-- Ports List below map -->
            <div class="puertos-operamos">
                <h3 data-i18n="services_page.ports_title">Puertos que Operamos</h3>
                <div class="puertos-grid">

                    <!-- América del Sur -->
                    <div class="puerto-region-card">
                        <div class="puerto-region-name">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            <span data-i18n="services_page.region_sa">América del Sur</span>
                        </div>
                        <ul class="puerto-items-list">
                            <li class="puerto-item puerto-item--main">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg>
                                <span>Zárate</span> <span class="puerto-item__badge" data-i18n="services_page.badge_main">Principal</span>
                            </li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Montevideo</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Paranaguá</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Santos</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Rio de Janeiro</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Vitória</li>
                        </ul>
                    </div>

                    <!-- África Occidental -->
                    <div class="puerto-region-card">
                        <div class="puerto-region-name">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            <span data-i18n="services_page.region_af_west">África Occidental</span>
                        </div>
                        <ul class="puerto-items-list">
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Lagos</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Cotonou</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Tema</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Abidjan</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Dakar</li>
                        </ul>
                    </div>

                    <!-- Europa del Norte -->
                    <div class="puerto-region-card">
                        <div class="puerto-region-name">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            <span data-i18n="services_page.region_eu_north">Europa del Norte</span>
                        </div>
                        <ul class="puerto-items-list">
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Amberes</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Rotterdam</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Hamburgo</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Tilbury</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Tenerife</li>
                        </ul>
                    </div>

                    <!-- Asia -->
                    <div class="puerto-region-card">
                        <div class="puerto-region-name">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            <span data-i18n="services_page.region_as">Asia</span>
                        </div>
                        <ul class="puerto-items-list">
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Taicang</li>
                            <li class="puerto-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg> Ningbo</li>
                        </ul>
                    </div>

                </div>
            </div>
        </section>

        <!-- ── COMMERCIAL TEAM ────────────────────────────── -->
        <section class="section-wrapper" aria-labelledby="team-cta-title">
            <div class="contenedor-principal">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h2 class="titulo-principal" id="team-cta-title" data-i18n="services_page.team_title">Nuestro Equipo Comercial</h2>
                    <p class="cuerpo-principal" data-i18n="services_page.team_desc_v2" style="max-width: 800px; margin: 0 auto;">
                        Para solicitar una cotización, comuníquese directamente con el equipo correspondiente a su tipo de carga.
                    </p>
                </div>

                <div class="team-commercial-grid">

                    <!-- Tarjeta 1: Romi Vergara (Ro-Ro) -->
                    <div class="team-card">
                        <div class="team-card__header">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                            <span data-i18n="services_preview.card_1_title">Carga Rodante</span>
                        </div>
                        <div class="team-card__body">
                            <div class="team-card__contact">
                                <div class="team-card__avatar">RV</div>
                                <div>
                                    <p class="team-card__name">Romi Vergara</p>
                                    <p class="team-card__role" data-i18n="services_page.role_roro">Comercial — Vehiculos</p>
                                </div>
                            </div>
                            <div class="team-card__actions">
                                <a href="mailto:cars.vans@ama-demo.example" class="team-action-btn team-action-btn--mail">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                    <span>customerserv@ama-demo.example</span>
                                </a>
                                <a href="tel:+541140000000" class="team-action-btn team-action-btn--phone">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                    <span data-i18n="services_page.btn_call">Llamar</span> <span class="team-int-badge"><span data-i18n="services_page.int_label">Int.</span> 227</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Trevor Jacobs (Proyecto) -->
                    <div class="team-card">
                        <div class="team-card__header">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                            <span data-i18n="services_preview.card_2_title">Carga de Proyecto</span>
                        </div>
                        <div class="team-card__body">
                            <div class="team-card__contact">
                                <div class="team-card__avatar">TJ</div>
                                <div>
                                    <p class="team-card__name">Trevor Jacobs</p>
                                    <p class="team-card__role" data-i18n="services_page.role_project">Comercial — Proyecto</p>
                                </div>
                            </div>
                            <div class="team-card__actions">
                                <a href="mailto:projects@ama-demo.example" class="team-action-btn team-action-btn--mail">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                    <span>projects@ama-demo.example</span>
                                </a>
                                <a href="tel:+541140000000" class="team-action-btn team-action-btn--phone">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                    <span data-i18n="services_page.btn_call">Llamar</span> <span class="team-int-badge"><span data-i18n="services_page.int_label">Int.</span> 230</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 3: Marcelo Lemos (Contenedores - Expo) -->
                    <div class="team-card">
                        <div class="team-card__header">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 17H5V5h14v14zM6 7h5v4H6zm7 0h5v4h-5zm-7 6h5v4H6zm7 0h5v4h-5z"/></svg>
                            <span data-i18n="services_page.header_expo">Contenedores</span>
                        </div>
                        <div class="team-card__body">
                            <div class="team-card__contact">
                                <div class="team-card__avatar">ML</div>
                                <div>
                                    <p class="team-card__name">Marcelo Lemos</p>
                                    <p class="team-card__role" data-i18n="services_page.role_containers">Comercial — Contenedores</p>
                                </div>
                            </div>
                            <div class="team-card__actions">
                                <a href="mailto:containers@ama-demo.example" class="team-action-btn team-action-btn--mail">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                    <span>containers@ama-demo.example</span>
                                </a>
                                <a href="tel:+541140000000" class="team-action-btn team-action-btn--phone">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                    <span data-i18n="services_page.btn_call">Llamar</span> <span class="team-int-badge"><span data-i18n="services_page.int_label">Int.</span> 228</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 4: Jean Paul Gibaut (Contenedores - Expo) -->
                    <div class="team-card">
                        <div class="team-card__header">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 17H5V5h14v14zM6 7h5v4H6zm7 0h5v4h-5zm-7 6h5v4H6zm7 0h5v4h-5z"/></svg>
                            <span data-i18n="services_page.header_expo">Contenedores</span>
                        </div>
                        <div class="team-card__body">
                            <div class="team-card__contact">
                                <div class="team-card__avatar">JG</div>
                                <div>
                                    <p class="team-card__name">Jean Paul Gibaut</p>
                                    <p class="team-card__role" data-i18n="services_page.role_containers">Comercial — Contenedores</p>
                                </div>
                            </div>
                            <div class="team-card__actions">
                                <a href="mailto:containers@ama-demo.example" class="team-action-btn team-action-btn--mail">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                    <span>containers@ama-demo.example</span>
                                </a>
                                <a href="tel:+541140000000" class="team-action-btn team-action-btn--phone">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                    <span data-i18n="services_page.btn_call">Llamar</span> <span class="team-int-badge"><span data-i18n="services_page.int_label">Int.</span> 229</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 5: Victoria Montero (Contenedores - Impo) -->
                    <div class="team-card">
                        <div class="team-card__header">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 17H5V5h14v14zM6 7h5v4H6zm7 0h5v4h-5zm-7 6h5v4H6zm7 0h5v4h-5z"/></svg>
                            <span data-i18n="services_page.header_impo">Contenedores</span>
                        </div>
                        <div class="team-card__body">
                            <div class="team-card__contact">
                                <div class="team-card__avatar">VM</div>
                                <div>
                                    <p class="team-card__name">Victoria Montero</p>
                                    <p class="team-card__role" data-i18n="services_page.role_containers">Comercial — Contenedores</p>
                                </div>
                            </div>
                            <div class="team-card__actions">
                                <a href="mailto:customerservices@ama-demo.example" class="team-action-btn team-action-btn--mail">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                    <span>containers@ama-demo.example</span>
                                </a>
                                <a href="tel:+541140000000" class="team-action-btn team-action-btn--phone">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                    <span data-i18n="services_page.btn_call">Llamar</span> <span class="team-int-badge"><span data-i18n="services_page.int_label">Int.</span> 237</span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <a href="contacto" class="boton-secundario" data-i18n="services_page.btn_all_contacts" style="padding: 0.5rem 1.5rem;">
                        Ver todos los contactos de la empresa
                    </a>
                </div>
            </div>
        </section>



    </main>

    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/d3@7.9.0/dist/d3.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/topojson-client@3.1.0/dist/topojson-client.min.js" defer></script>
    <script src="<?php echo assetUrl('js/servicios.js'); ?>" defer></script>

</body>
</html>
