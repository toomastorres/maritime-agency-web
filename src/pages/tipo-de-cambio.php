<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — tipo-de-cambio.php                             -->
<!-- Página de cotizaciones de tipo de cambio                  -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Tipo de Cambio | AMA Agencies Argentina</title>
    <meta name="description"
        content="Consulte la cotización del día para el tipo de cambio oficial de AMA Agencies Argentina. Descargue el comprobante en formato PDF.">
    <meta name="keywords" content="AMA, tipo de cambio, cotización dólar, cotización euro, pagos, cuenta bancaria">
    <meta name="author" content="AMA Agencies Argentina S.A.">
    <meta name="robots" content="index, follow">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Tipo de Cambio | AMA Agencies Argentina">
    <meta property="og:description" content="Cotización diaria oficial y datos bancarios para pagos.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/logo-blue.svg">
    <meta property="og:url" content="https://ama-demo.example/tipo-de-cambio">
    <meta property="og:site_name" content="AMA Agencies Argentina">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://ama-demo.example/tipo-de-cambio">

    <!-- Specific CSS for Internal Pages -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/tipo-de-cambio.css'); ?>">
</head>

<body class="has-hero">
    <!-- HEADER -->
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main">

        <!-- HERO -->
        <header class="hero hero--page" aria-labelledby="exchange-title">
            <div class="hero__bg-image" style="background-image: url('<?php echo ASSETS_URL; ?>/img/grande_san_paolo_apaisado.png');"></div>
            <div class="hero__overlay"></div>
            <div class="hero__content contenedor-principal">
                <span class="hero__badge" data-i18n="exchange.badge">Información Financiera</span>
                <h1 class="hero__title" id="exchange-title" data-i18n="exchange.title">Tipo de Cambio</h1>
                <p class="hero__subtitle" data-i18n="exchange.description">
                    Cotización oficial de AMA Agencies Argentina S.A. para pagos y transferencias bancarias. Actualizado diariamente durante días hábiles.
                </p>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <section class="section-wrapper" style="min-height: 50vh;">
            <div class="contenedor-principal">

                <!-- Loading State -->
                <div id="tc-loading" class="alerta-estado text-center">
                    <svg class="spinner-icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 1 10 10h-2a8 8 0 0 0-8-8V2z"/></svg> <span data-i18n="exchange.loading">Cargando cotización...</span>
                </div>

                <!-- Error/Gated State -->
                <div id="tc-alert-container"></div>

                <div id="tc-content" style="display: none;" class="animate-fade-in">

                    <!-- Exchange Rate Cards -->
                    <div class="tc-rates-grid">
                        <div class="tc-rate-card">
                            <div class="tc-rate-card__icon">
                                <svg viewBox="0 0 24 24" width="36" height="36" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                            </div>
                            <div>
                                <h3 class="tc-rate-card__currency" data-i18n="exchange.usd">Dólar (USD)</h3>
                                <span class="tc-rate-card__type" data-i18n="exchange.official_bna">Oficial BNA</span>
                            </div>
                            <p class="tc-rate-label" data-i18n="exchange.rate_label">Tipo de Cambio</p>
                            <p class="tc-rate-value" id="val-usd">$0.00</p>
                            <p class="tc-rate-date texto-info tc-date-display">--/--/----</p>
                        </div>

                        <div class="tc-rate-card">
                            <div class="tc-rate-card__icon">
                                <svg viewBox="0 0 24 24" width="36" height="36" fill="currentColor"><path d="M12.5 6.9c1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-.53.12-1.03.3-1.48.54l1.47 1.47c.41-.17.91-.27 1.51-.27zM5.33 4.06L4.06 5.33 7.5 8.77c0 2.08 1.56 3.21 3.91 3.91l3.51 3.51c-.34.48-1.05.91-2.42.91-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.05-.2 1.99-.66 2.67-1.39l2.08 2.08 1.27-1.27L5.33 4.06z"/></svg>
                            </div>
                            <div>
                                <h3 class="tc-rate-card__currency" data-i18n="exchange.eur">Euro (EUR)</h3>
                                <span class="tc-rate-card__type" data-i18n="exchange.official_bna">Oficial BNA</span>
                            </div>
                            <p class="tc-rate-label" data-i18n="exchange.rate_label">Tipo de Cambio</p>
                            <p class="tc-rate-value" id="val-eur">€0.00</p>
                            <p class="tc-rate-date texto-info tc-date-display">--/--/----</p>
                        </div>
                    </div>

                    <!-- PDF Download -->
                    <div style="text-align: center; margin: 2rem 0;">
                        <button id="btn-descargar-pdf" class="boton-primario">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align: sub; margin-right: 0.5rem;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                            <span data-i18n="exchange.btn_download">Descargar PDF</span>
                        </button>
                    </div>

                    <!-- Bank Details (dark blue card) -->
                    <div class="tc-bank-card">
                        <div class="tc-bank-card__header">
                            <div class="tc-bank-card__title-group">
                                <div class="tc-bank-card__icon">
                                    <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm14-12v7h3v-7h-3zM11.5 1L2 6v2h19V6l-9.5-5z"/></svg>
                                </div>
                                <div>
                                    <h3 class="tc-bank-title" data-i18n="exchange.bank_data_title">Datos de Facturación</h3>
                                    <p class="tc-bank-subtitle" data-i18n="exchange.bank_subtitle">Información bancaria oficial para transferencias electrónicas.</p>
                                </div>
                            </div>
                            <div class="tc-bank-email-box">
                                <p class="tc-bank-email-label" data-i18n="exchange.bank_email_label">Enviar comprobante a:</p>
                                <a href="mailto:finanzas@AMA.com.ar" class="tc-bank-email-link">finance@ama-demo.example</a>
                            </div>
                        </div>
                        <div class="tc-bank-card__body" id="bank-data">
                            <!-- Injected via JS -->
                        </div>
                        <div class="tc-bank-card__footer">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="var(--AMA-gold)"><path d="M12 2L1 21h22L12 2zm0 3.5L19.5 19h-15L12 5.5zm-1 5.5v4h2v-4h-2zm0 6v2h2v-2h-2z"/></svg>
                            <!-- FIX: moved inline color/size/style to .tc-bank-note class in tipo-de-cambio.css -->
                            <p class="tc-bank-note" data-i18n="exchange.bank_note">Nota: Los pagos realizados después de las 15:00 hs serán procesados con el tipo de cambio del día hábil siguiente.</p>
                        </div>
                    </div>

                    <!-- Info Panel -->
                    <div class="tc-info-panel">
                        <div class="tc-info-panel__item">
                            <div class="tc-info-panel__icon">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                            </div>
                            <p class="tc-info-panel__title" data-i18n="schedule.hours_title">Horario de Atención</p>
                            <p class="tc-info-panel__value" data-i18n="footer.schedule">Lunes a Viernes: 9:00hs a 16:00hs</p>
                        </div>
                        <div class="tc-info-panel__item">
                            <div class="tc-info-panel__icon">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M21 10.12h-6.78l2.74-2.82c-2.73-2.7-7.15-2.8-9.88-.1-2.73 2.71-2.73 7.08 0 9.79s7.15 2.71 9.88 0C18.32 15.65 19 14.08 19 12.1h2c0 1.98-.88 4.55-2.64 6.29-3.51 3.48-9.21 3.48-12.72 0-3.5-3.47-3.53-9.11-.02-12.58s9.14-3.49 12.65 0L21 3v7.12zM12.5 8v4.25l3.5 2.08-.72 1.21L11 13V8h1.5z"/></svg>
                            </div>
                            <p class="tc-info-panel__title" data-i18n="schedule.update_title">Última Actualización</p>
                            <p class="tc-info-panel__value"><span class="tc-info-panel__value--highlight" id="last-update">--</span></p>
                        </div>
                        <div class="tc-info-panel__item">
                            <div class="tc-info-panel__icon">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                            </div>
                            <p class="tc-info-panel__title" data-i18n="exchange.contact_title">Contacto Caja</p>
                            <p class="tc-info-panel__value" data-i18n="exchange.contact_desc" style="margin-bottom: 0.5rem;">Para consultas específicas sobre tipos de cambio</p>
                            <a href="mailto:tc@ama-demo.example" class="tc-info-panel__action">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                <span data-i18n="exchange.btn_contact">Contactar Caja</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <!-- jsPDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>
    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/tipo-de-cambio.js'); ?>" defer></script>
</body>

</html>
