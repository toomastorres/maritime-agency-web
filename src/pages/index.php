<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — index.php                                      -->
<!-- Página principal (Homepage) — sistema Oltremare           -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atlantic Maritime Agency — Transporte Marítimo y Logística Internacional</title>
    <meta name="description" content="Atlantic Maritime Agency S.A. representa a Atlantic Maritime Group, multinacional italiana con más de 20 años conectando mercados a través de rutas marítimas eficientes desde Terminal Zárate.">
    <meta name="keywords" content="AMA, transporte marítimo, logística, Argentina, contenedores, carga proyecto, vehículos, Terminal Zárate, Ro-Ro, buques">
    <meta name="author" content="Atlantic Maritime Agency S.A.">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Atlantic Maritime Agency — Transporte Marítimo">
    <meta property="og:description" content="Soluciones integrales de transporte marítimo y logística internacional desde y hacia Argentina. Terminal Zárate.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/grande_amburgo.png">
    <meta property="og:url" content="https://atlantic-agency.example.com">
    <meta property="og:site_name" content="Atlantic Maritime Agency">
    <meta property="og:locale" content="es_AR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Atlantic Maritime Agency">
    <meta name="twitter:description" content="Soluciones de transporte marítimo y logística internacional desde Terminal Zárate.">
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="canonical" href="https://atlantic-agency.example.com">
    <link rel="preload" as="image" href="<?php echo ASSETS_URL; ?>/img/grande_amburgo.png">
    <link rel="stylesheet" href="<?php echo assetUrl('css/index.css'); ?>">
</head>

<body>

    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main" class="home">

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- HERO                                                  -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="home-hero" aria-labelledby="hero-title">
            <img class="home-hero__bg" src="<?php echo ASSETS_URL; ?>/img/grande_amburgo.png"
                 alt="Buque AMA navegando en alta mar" width="1920" height="1080" fetchpriority="high">
            <div class="home-hero__overlay" aria-hidden="true"></div>

            <div class="home-hero__content">
                <div class="home-hero__kicker" data-i18n="home.hero_kicker">Transporte marítimo y logística internacional</div>
                <h1 class="home-hero__title" id="hero-title" data-i18n="home.hero_title">Conectamos Argentina con el mundo.</h1>
                <p class="home-hero__subtitle" data-i18n="home.hero_subtitle">
                    Más de veinte años representando a Atlantic Maritime Group desde Terminal Zárate: carga rodante, carga proyecto y contenedores hacia una red de 130 puertos.
                </p>
                <div class="home-hero__cta-group">
                    <a href="<?php echo BASE_URL; ?>schedule" class="olt-btn olt-btn--white" data-i18n="home.hero_cta_schedule">Ver schedule</a>
                    <a href="<?php echo BASE_URL; ?>servicios#cotizador" class="olt-btn olt-btn--outline-light" data-i18n="home.hero_cta_services">Pedir cotización</a>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- FRANJA OPERATIVA: TC del día / próxima llegada / CS   -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="home-strip" aria-label="Información operativa del día">
            <div class="home-strip__inner">

                <!-- Cada columna es un enlace completo, no un dato con un link al lado:
                     el usuario que mira la cotización quiere abrir tipo de cambio, y el
                     que mira la próxima llegada quiere el schedule. Ojo: nada de lo que
                     escriba el JS dentro de estos tiles puede ser <a>, quedaría anidado. -->
                <a class="home-strip__tile olt-metric" href="<?php echo BASE_URL; ?>tipo-de-cambio">
                    <span class="olt-metric__label" data-i18n="home.strip_tc_label">Tipo de cambio</span>
                    <span class="olt-metric__value" id="home-tc-value">
                        <span class="home-strip__muted" data-i18n="home.loading">Consultando…</span>
                    </span>
                    <span class="olt-metric__meta" id="home-tc-meta"></span>
                    <span class="home-strip__go" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </span>
                </a>

                <a class="home-strip__tile olt-metric" href="<?php echo BASE_URL; ?>schedule">
                    <span class="olt-metric__label" data-i18n="home.strip_arrival_label">Próxima llegada a Zárate</span>
                    <!-- El data-i18n va en el span interno, no en #home-arrival-value: el JS
                         reemplaza el innerHTML y así el hook de i18n desaparece con él. Si
                         estuviera en el span externo, applyTranslations() volvería a pisar el
                         buque con "Consultando…" en cada carga y cada cambio de idioma. -->
                    <span class="olt-metric__value olt-metric__value--sm" id="home-arrival-value">
                        <span class="home-strip__muted" data-i18n="home.loading">Consultando…</span>
                    </span>
                    <span class="olt-metric__meta" id="home-arrival-meta"></span>
                    <span class="home-strip__go" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </span>
                </a>

                <a class="home-strip__tile olt-metric" href="mailto:customerserv@atlantic-agency.example.com">
                    <span class="olt-metric__label" data-i18n="home.strip_cs_label">Atención al cliente</span>
                    <span class="olt-metric__value olt-metric__value--text">customerserv@atlantic-agency.example.com</span>
                    <span class="olt-metric__meta" data-i18n="home.strip_cs_desc">Liberaciones, turnos y seguimiento</span>
                    <span class="home-strip__go" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </span>
                </a>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- BUQUES MULTIPROPÓSITO                                 -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="home-vessels" aria-labelledby="vessels-title">
            <div class="home-vessels__inner">
                <div class="home-vessels__intro">
                    <div class="olt-kicker" data-i18n="home.vessels_kicker">Qué movemos</div>
                    <h2 class="olt-h2" id="vessels-title" data-i18n="home.vessels_title">Buques multipropósito</h2>
                    <p data-i18n="home.vessels_body">
                        En una misma escala embarcamos vehículos, contenedores y bultos fuera de medida. No hay que esperar un buque distinto según el tipo de carga: la rampa y las bodegas conviven en el mismo casco.
                    </p>
                    <a href="<?php echo BASE_URL; ?>servicios" class="olt-link" data-i18n="home.vessels_link">Ver servicios y pedir cotización</a>
                </div>

                <div class="home-vessels__grid">
                    <div class="home-vessel-card">
                        <div class="home-vessel-card__img" style="background-image:url('<?php echo ASSETS_URL; ?>/img/grande_nigeria_lado.png')"></div>
                        <h3 data-i18n="home.vessel1_title">Carga rodante</h3>
                        <p data-i18n="home.vessel1_body">Autos, camiones, colectivos y maquinaria autopropulsada, embarcados por rampa sin desarme ni contenedor.</p>
                    </div>
                    <div class="home-vessel-card">
                        <div class="home-vessel-card__img" style="background-image:url('<?php echo ASSETS_URL; ?>/img/port-terminal.jpg')"></div>
                        <h3 data-i18n="home.vessel2_title">Contenedores</h3>
                        <p data-i18n="home.vessel2_body">Equipos dry y reefer de 20 y 40 pies, con monitoreo de temperatura y conexión a la red del grupo.</p>
                    </div>
                    <div class="home-vessel-card">
                        <div class="home-vessel-card__img" style="background-image:url('<?php echo ASSETS_URL; ?>/img/grande_san_paolo_apaisado.png')"></div>
                        <h3 data-i18n="home.vessel3_title">Carga proyecto</h3>
                        <p data-i18n="home.vessel3_body">Transformadores, palas de aerogenerador y estructuras indivisibles, con estudio de estiba propio.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- PRÓXIMAS SALIDAS                                      -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="home-departures" aria-labelledby="departures-title">
            <div class="home-departures__inner">
                <div class="olt-section-head">
                    <div class="olt-section-head__text">
                        <div class="olt-kicker" data-i18n="home.departures_kicker">Dos servicios regulares</div>
                        <h2 class="olt-h2" id="departures-title" data-i18n="home.departures_title">Próximas salidas</h2>
                    </div>
                    <div class="olt-section-head__aside">
                        <a href="<?php echo BASE_URL; ?>schedule" class="olt-link" data-i18n="home.departures_link">Ver el schedule completo</a>
                    </div>
                </div>

                <div class="home-departures__table-wrap">
                    <table class="home-departures__table">
                        <thead>
                            <tr>
                                <th data-i18n="home.th_vessel">Buque</th>
                                <th class="is-secondary" data-i18n="home.th_service">Servicio</th>
                                <th class="is-secondary" data-i18n="home.th_origin">Procedencia</th>
                                <th class="is-right" data-i18n="home.th_eta">ETA Zárate</th>
                                <th class="is-right" data-i18n="home.th_etd">ETD</th>
                            </tr>
                        </thead>
                        <tbody id="home-departures-body">
                            <tr><td colspan="5" class="home-departures__status" data-i18n="home.loading">Consultando…</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="home-departures__note" data-i18n="home.departures_note">Fechas estimadas, sujetas a condiciones operativas y meteorológicas.</p>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- LA AGENCIA                                            -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="home-agency" aria-labelledby="agency-title">
            <div class="home-agency__inner">

                <div class="home-agency__about">
                    <div class="olt-kicker" data-i18n="home.agency_kicker">La agencia</div>
                    <h2 class="olt-h2" id="agency-title" data-i18n="home.agency_title">Representamos a Atlantic Maritime Group en Argentina</h2>
                    <p data-i18n="home.agency_body">Desde 2003 operamos como agencia local del grupo napolitano fundado en 1947. Toda nuestra operación se consolida en Terminal Zárate, con equipos propios de Customer Service, Documentación, Finanzas y Claims.</p>

                    <a href="<?php echo BASE_URL; ?>institucional" class="olt-link home-agency__link" data-i18n="home.news_link">Ver institucional y vacantes</a>
                </div>

                <!-- Las cifras ocupan la columna que quedó libre al sacar Novedades:
                     si van debajo del texto, la mitad derecha de la sección queda
                     en blanco y los tres números terminan separados por 300px. -->
                <div class="home-agency__stats">
                    <div class="olt-metric">
                        <div class="olt-metric__value olt-metric__value--serif">130</div>
                        <div class="olt-metric__meta" data-i18n="home.stat1_label">Puertos en la red del grupo</div>
                    </div>
                    <div class="olt-metric">
                        <div class="olt-metric__value olt-metric__value--serif">2</div>
                        <div class="olt-metric__meta" data-i18n="home.stat2_label">Servicios regulares a Zárate</div>
                    </div>
                    <div class="olt-metric">
                        <div class="olt-metric__value olt-metric__value--serif">20+</div>
                        <div class="olt-metric__meta" data-i18n="home.stat3_label">Años de operación local</div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- CTA COTIZACIÓN                                        -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section class="home-cta" aria-labelledby="cta-title">
            <div class="home-cta__inner">
                <div>
                    <h2 class="olt-h2" id="cta-title" data-i18n="home.cta_title">¿Necesitás cotizar un embarque?</h2>
                    <p data-i18n="home.cta_body">Contanos qué carga tenés y el equipo comercial responde con la tarifa y el espacio disponible.</p>
                </div>
                <div class="home-cta__actions">
                    <a href="mailto:containers@atlantic-agency.example.com?subject=Solicitud%20de%20Cotizaci%C3%B3n" class="olt-btn olt-btn--primary" data-i18n="home.cta_btn_quote">Pedir cotización</a>
                    <a href="<?php echo BASE_URL; ?>contacto" class="olt-btn olt-btn--outline" data-i18n="home.cta_btn_contacts">Directorio de contactos</a>
                </div>
            </div>
        </section>

    </main>

    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/index.js'); ?>" defer></script>

</body>
</html>
