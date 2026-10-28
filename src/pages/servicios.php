<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — servicios.php                                  -->
<!-- Servicios: catálogo, red de puertos y cotizador — sistema Oltremare -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios Logísticos | Atlantic Maritime Agency</title>
    <meta name="description" content="Servicios logísticos integrados de Atlantic Maritime Agency. Conoce nuestras rutas, tipos de carga y a nuestro equipo comercial especializado.">
    <meta name="keywords" content="AMA, servicios logísticos, carga rodante, project cargo, contenedores, rutas marítimas, Terminal Zárate">
    <meta name="author" content="Atlantic Maritime Agency S.A.">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Servicios Logísticos | Atlantic Maritime Agency">
    <meta property="og:description" content="Servicios logísticos integrados. Transporte de vehículos, carga ro-ro, contenedores y carga proyecto.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/logo-blue.svg">
    <meta property="og:url" content="https://atlantic-agency.example.com/servicios">
    <meta property="og:site_name" content="Atlantic Maritime Agency">
    <meta property="og:locale" content="es_AR">
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="canonical" href="https://atlantic-agency.example.com/servicios">
    <link rel="stylesheet" href="<?php echo assetUrl('css/servicios.css'); ?>">
</head>

<body>
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main" class="olt-page">

        <!-- ── HERO ────────────────────────────────────────── -->
        <header class="olt-hero-image" aria-labelledby="servicios-hero-title">
            <img class="olt-hero-image__bg" src="<?php echo ASSETS_URL; ?>/img/grande_nigeria_lado.png" alt="">
            <div class="olt-hero-image__overlay" aria-hidden="true"></div>
            <div class="olt-hero-image__content">
                <div class="olt-crumb"><a href="<?php echo BASE_URL; ?>" data-i18n="nav.home">Inicio</a> / <span data-i18n="nav.services">Servicios</span></div>
                <h1 class="olt-h1" id="servicios-hero-title" data-i18n="services_page.title_full">Servicios y comercial</h1>
                <p class="olt-hero-image__subtitle" data-i18n="services_page.description">
                    Buques multipropósito: en una misma escala embarcamos vehículos, carga proyecto y contenedores desde Terminal Zárate.
                </p>
            </div>
        </header>

        <!-- ── CATÁLOGO DE SERVICIOS ──────────────────────── -->
        <section class="olt-catalog" id="servicios-cards" aria-labelledby="servicios-cards-title">
            <h2 id="servicios-cards-title" class="sr-only" data-i18n="services_page.cards_title">Soluciones de Transporte Especializado</h2>
            <div class="olt-container">

                <div class="olt-catalog__row" id="ro-ro">
                    <div class="olt-catalog__media">
                        <div class="olt-catalog__thumb" style="background-image:url('<?php echo ASSETS_URL; ?>/img/grande_amburgo.png')"></div>
                        <div class="olt-kicker--accent" data-i18n="services_page.tag_roro">Vehículos</div>
                    </div>
                    <div class="olt-catalog__body">
                        <h3 class="olt-h2" data-i18n="services_preview.card_1_title">Carga Rodante</h3>
                        <p data-i18n="services_page.roro_desc">Infraestructura especializada para el transporte seguro de vehículos livianos, pesados y maquinaria agrícola. Nuestra flota de última generación garantiza máxima protección.</p>
                        <div class="olt-catalog__specs">
                            <div><div class="olt-kicker" data-i18n="services_page.spec_roro_1_k">Tipo de unidad</div><div class="olt-catalog__spec-v" data-i18n="services_page.spec_roro_1_v">Autos, camiones, maquinaria</div></div>
                            <div><div class="olt-kicker" data-i18n="services_page.spec_roro_2_k">Método de embarque</div><div class="olt-catalog__spec-v" data-i18n="services_page.spec_roro_2_v">Rampa, sin desarmar</div></div>
                            <div><div class="olt-kicker" data-i18n="services_page.spec_terminal_k">Terminal</div><div class="olt-catalog__spec-v">Zárate</div></div>
                        </div>
                    </div>
                </div>

                <div class="olt-catalog__row" id="proyecto">
                    <div class="olt-catalog__media">
                        <div class="olt-catalog__thumb" style="background-image:url('<?php echo ASSETS_URL; ?>/img/grande_francia_frente-1.png')"></div>
                        <div class="olt-kicker--accent" data-i18n="services_page.tag_proyecto">Proyecto</div>
                    </div>
                    <div class="olt-catalog__body">
                        <h3 class="olt-h2" data-i18n="services_preview.card_2_title">Carga de Proyecto</h3>
                        <p data-i18n="services_page.proyecto_desc">Expertos en logística de piezas sobredimensionadas y pesadas para minería, energía e infraestructura. Supervisión técnica especializada en cada movimiento.</p>
                        <div class="olt-catalog__specs">
                            <div><div class="olt-kicker" data-i18n="services_page.spec_proyecto_1_k">Tipo de carga</div><div class="olt-catalog__spec-v" data-i18n="services_page.spec_proyecto_1_v">Piezas sobredimensionadas y pesadas</div></div>
                            <div><div class="olt-kicker" data-i18n="services_page.spec_proyecto_2_k">Planificación</div><div class="olt-catalog__spec-v" data-i18n="services_page.spec_proyecto_2_v">Estudio de estiba a medida</div></div>
                            <div><div class="olt-kicker" data-i18n="services_page.spec_terminal_k">Terminal</div><div class="olt-catalog__spec-v">Zárate</div></div>
                        </div>
                    </div>
                </div>

                <div class="olt-catalog__row" id="contenedores">
                    <div class="olt-catalog__media">
                        <div class="olt-catalog__thumb" style="background-image:url('<?php echo ASSETS_URL; ?>/img/port-terminal.jpg')"></div>
                        <div class="olt-kicker--accent" data-i18n="services_page.tag_containers">Container</div>
                    </div>
                    <div class="olt-catalog__body">
                        <h3 class="olt-h2" data-i18n="services_preview.card_3_title">Contenedores</h3>
                        <p data-i18n="services_page.contenedores_desc">Servicio completo de transporte de contenedores con seguimiento en tiempo real y soluciones personalizadas para cualquier tipo de carga.</p>
                        <div class="olt-catalog__specs">
                            <div><div class="olt-kicker" data-i18n="services_page.spec_cont_1_k">Formatos</div><div class="olt-catalog__spec-v" data-i18n="services_page.spec_cont_1_v">20' Dry, 40' HC, Reefer</div></div>
                            <div><div class="olt-kicker" data-i18n="services_page.spec_cont_2_k">Seguimiento</div><div class="olt-catalog__spec-v" data-i18n="services_page.spec_cont_2_v">Monitoreo en tiempo real</div></div>
                            <div><div class="olt-kicker" data-i18n="services_page.spec_terminal_k">Terminal</div><div class="olt-catalog__spec-v">Zárate</div></div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ── ROUTES MAP SECTION ─────────────────────────── -->
        <!-- Módulo embebido: src/pages/mapa-red.php (mapa D3 interactivo por servicio + grilla de puertos). -->
        <!-- Auto-resize via postMessage, escuchado en el <script> antes de </body>. -->
        <section class="olt-section olt-section--tight" id="rutas" aria-labelledby="rutas-map-title">
            <h2 id="rutas-map-title" class="sr-only" data-i18n="services_page.map_title">Red Global de Rutas</h2>
            <div class="olt-container">
                <iframe id="mapa-red-frame" class="mapa-red-iframe" data-gmap
                        src="<?php echo BASE_URL; ?>mapa-red"
                        title="Mapa de la red de servicios de AMA"
                        loading="lazy" scrolling="no"></iframe>
            </div>
        </section>

        <!-- ── COTIZADOR ───────────────────────────────────── -->
        <section class="olt-quote" id="cotizador" aria-labelledby="quote-title">
            <div class="olt-container">
                <div class="olt-quote__intro">
                    <div class="olt-kicker--accent" data-i18n="services_page.quote_kicker">Cotización en línea</div>
                    <h2 class="olt-h2" id="quote-title" data-i18n="services_page.quote_title">Solicite una cotización</h2>
                    <p data-i18n="services_page.quote_body">Complete los datos de su carga y la consulta llega directamente al equipo comercial que corresponde. Respondemos dentro de las 48 horas hábiles.</p>
                </div>

                <form class="olt-quote__layout" id="quote-form" novalidate>
                    <div class="olt-panel olt-quote__form">

                        <!-- Honeypot anti-spam (no visible para personas) -->
                        <input type="text" name="website_url" id="quote-honeypot" class="olt-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="olt-kicker" data-i18n="services_page.quote_step1">Paso 1 · Tipo de carga</div>
                        <div class="olt-quote__types" role="group" aria-label="Tipo de carga">
                            <button type="button" class="olt-quote__type" data-service="roro" data-label-key="services_preview.card_1_title" data-label="Carga Rodante" data-mail="customerserv@atlantic-agency.example.com" data-i18n="services_preview.card_1_title">Carga Rodante</button>
                            <button type="button" class="olt-quote__type" data-service="proyecto" data-label-key="services_preview.card_2_title" data-label="Carga de Proyecto" data-mail="projects@atlantic-agency.example.com" data-i18n="services_preview.card_2_title">Carga de Proyecto</button>
                            <button type="button" class="olt-quote__type" data-service="contenedores" data-label-key="services_preview.card_3_title" data-label="Contenedores" data-mail="containers@atlantic-agency.example.com" data-i18n="services_preview.card_3_title">Contenedores</button>
                        </div>
                        <input type="hidden" name="servicio" id="quote-servicio" required>

                        <div class="olt-kicker" data-i18n="services_page.quote_step2">Paso 2 · Datos de la carga</div>
                        <div class="olt-quote__grid">
                            <div class="olt-field">
                                <label data-i18n="services_page.quote_origin">Puerto de origen</label>
                                <div class="olt-field__static">Zárate, Argentina</div>
                            </div>
                            <div class="olt-field">
                                <label for="quote-destino" data-i18n="services_page.quote_destination">Puerto de destino</label>
                                <input type="text" id="quote-destino" name="destino" placeholder="Ej: Hamburgo, Alemania" data-i18n-placeholder="services_page.quote_destination_ph">
                            </div>
                            <div class="olt-field olt-field--full">
                                <label for="quote-mercaderia" data-i18n="services_page.quote_goods">Descripción de la mercadería</label>
                                <textarea id="quote-mercaderia" name="mercaderia" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="olt-kicker" data-i18n="services_page.quote_step3">Paso 3 · Datos de contacto</div>
                        <div class="olt-quote__grid">
                            <div class="olt-field">
                                <label for="quote-nombre" data-i18n="services_page.quote_name">Nombre y apellido <span class="olt-req">*</span></label>
                                <input type="text" id="quote-nombre" name="nombre" required>
                            </div>
                            <div class="olt-field">
                                <label for="quote-empresa" data-i18n="services_page.quote_company">Empresa</label>
                                <input type="text" id="quote-empresa" name="empresa">
                            </div>
                            <div class="olt-field">
                                <label for="quote-email" data-i18n="services_page.quote_email">Correo electrónico <span class="olt-req">*</span></label>
                                <input type="email" id="quote-email" name="email" required>
                            </div>
                            <div class="olt-field">
                                <label for="quote-telefono" data-i18n="services_page.quote_phone">Teléfono</label>
                                <input type="tel" id="quote-telefono" name="telefono">
                            </div>
                        </div>
                    </div>

                    <!-- El wrapper no es decorativo: position:sticky se resuelve contra
                         el bloque contenedor, y si el pegajoso es el propio item de
                         la grilla (que con align-items:start mide lo que su
                         contenido) no le queda recorrido y nunca se pega. -->
                    <div class="olt-quote__side">
                    <div class="olt-quote__summary">
                        <div class="olt-quote__summary-head" data-i18n="services_page.quote_summary_title">Resumen de la solicitud</div>
                        <div class="olt-quote__summary-body">
                            <div class="olt-quote__summary-rows">
                                <div>
                                    <div class="olt-kicker" data-i18n="services_page.quote_summary_type">Tipo de carga</div>
                                    <div class="olt-quote__summary-value" id="quote-summary-label" data-i18n="services_page.quote_summary_empty">Seleccione un tipo de carga</div>
                                </div>
                                <div>
                                    <div class="olt-kicker" data-i18n="services_page.quote_summary_sendto">Se envía a</div>
                                    <div class="olt-quote__summary-mail" id="quote-summary-mail">—</div>
                                </div>
                            </div>
                            <div class="olt-quote__summary-foot">
                                <button type="submit" class="olt-btn olt-btn--primary olt-quote__submit" id="quote-submit-btn" data-i18n="services_page.quote_submit">Enviar solicitud</button>
                                <p data-i18n="services_page.quote_disclaimer">Al enviar acepta que sus datos sean utilizados exclusivamente para responder esta consulta comercial, conforme a la política de privacidad.</p>
                                <div id="quote-status" class="olt-quote__status" role="status" aria-live="polite"></div>
                            </div>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- ── COMMERCIAL TEAM ────────────────────────────── -->
        <section class="olt-section" aria-labelledby="team-cta-title">
            <div class="olt-container">
                <div class="olt-team__intro">
                    <h2 class="olt-h2" id="team-cta-title" data-i18n="services_page.team_title">Contacto comercial directo</h2>
                    <p data-i18n="services_page.team_desc_v2">Si preferís escribir en vez de completar el formulario, cada tipo de carga tiene su propio buzón comercial.</p>
                </div>

                <?php
                // Buzones comerciales tomados del modelo del directorio, para que
                // Servicios y Contacto no se desincronicen. Son casillas de área,
                // no personas: el mismo criterio que el directorio de Contacto.
                $iconCarga = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>';
                $iconProy  = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
                $iconCont  = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 17H5V5h14v14zM6 7h5v4H6zm7 0h5v4h-5zm-7 6h5v4H6zm7 0h5v4h-5z"/></svg>';
                $iconCS    = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>';

                $canalesComercial = [
                    ['icon' => $iconCS,    'label' => 'contact.ch_cs',              'label_txt' => 'Customer Service',        'desc' => 'services_page.ch_cs_desc',    'desc_txt' => 'Seguimiento de embarques, turnos y liberaciones.',      'email' => 'customerserv@atlantic-agency.example.com'],
                    ['icon' => $iconCarga, 'label' => 'contact.ch_autos',           'label_txt' => 'Autos / Ro-Ro',           'desc' => 'services_page.ch_roro_desc',  'desc_txt' => 'Vehículos, maquinaria y toda la carga rodante.',        'email' => 'cars.vans@atlantic-agency.example.com'],
                    ['icon' => $iconProy,  'label' => 'contact.ch_projects',        'label_txt' => 'Carga proyecto',          'desc' => 'services_page.ch_proj_desc',  'desc_txt' => 'Piezas sobredimensionadas e indivisibles.',             'email' => 'projects@atlantic-agency.example.com'],
                    ['icon' => $iconCont,  'label' => 'contact.ch_contenedores',    'label_txt' => 'Contenedores (Impo/Expo)', 'desc' => 'services_page.ch_cont_desc', 'desc_txt' => 'Equipos dry y reefer, importación y exportación.',      'email' => 'containers@atlantic-agency.example.com'],
                ];
                ?>

                <div class="olt-team-grid olt-team-grid--channels">
                    <?php foreach ($canalesComercial as $canal): ?>
                    <a class="olt-team-card olt-team-card--channel" href="mailto:<?php echo htmlspecialchars($canal['email']); ?>">
                        <span class="olt-team-card__header">
                            <?php echo $canal['icon']; ?>
                            <span data-i18n="<?php echo htmlspecialchars($canal['label']); ?>"><?php echo htmlspecialchars($canal['label_txt']); ?></span>
                        </span>
                        <span class="olt-team-card__desc" data-i18n="<?php echo htmlspecialchars($canal['desc']); ?>"><?php echo htmlspecialchars($canal['desc_txt']); ?></span>
                        <span class="olt-team-card__mail"><?php echo htmlspecialchars($canal['email']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <a href="<?php echo BASE_URL; ?>contacto" class="olt-link olt-team__all" data-i18n="services_page.btn_all_contacts">Ver todos los contactos de la empresa</a>
            </div>
        </section>

    </main>

    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/servicios.js'); ?>" defer></script>

    <!-- Auto-resize del iframe del mapa de la red (mensajes postMessage desde
         mapa-red.php) y sincronización de idioma: el iframe tiene su propio
         i18n, y sin este aviso se quedaba en el idioma con el que cargó
         cuando el visitante cambiaba de idioma en el selector del header. -->
    <script>
        (function () {
            function frames() { return document.querySelectorAll('iframe[data-gmap]'); }

            window.addEventListener('message', function (e) {
                var d = e.data;
                if (!d || !d.__gmap) return;
                if (d.h) {
                    frames().forEach(function (f) {
                        if (f.contentWindow === e.source) f.style.height = d.h + 'px';
                    });
                }
                // El iframe avisa que ya está listo para recibir el idioma.
                if (d.ready) pushLang(e.source);
            });

            function currentLang() {
                var p = new URLSearchParams(location.search).get('lang');
                if (p) return p;
                try { return localStorage.getItem('ama_lang') || ''; } catch (err) { return ''; }
            }

            function pushLang(target) {
                var lang = currentLang();
                if (!lang) return;
                (target ? [{ contentWindow: target }] : Array.prototype.slice.call(frames()))
                    .forEach(function (f) {
                        try { f.contentWindow.postMessage({ __gmapLang: lang }, '*'); } catch (err) { /* noop */ }
                    });
            }

            document.querySelectorAll('[data-lang]').forEach(function (btn) {
                btn.addEventListener('click', function () { setTimeout(pushLang, 60); });
            });
        })();
    </script>

</body>
</html>
