<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — tipo-de-cambio.php                             -->
<!-- Página de cotizaciones de tipo de cambio — sistema Oltremare -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Tipo de Cambio | Atlantic Maritime Agency</title>
    <meta name="description"
        content="Consulte la cotización del día para el tipo de cambio oficial de Atlantic Maritime Agency. Descargue el comprobante en formato PDF.">
    <meta name="keywords" content="AMA, tipo de cambio, cotización dólar, cotización euro, pagos, cuenta bancaria">
    <meta name="author" content="Atlantic Maritime Agency S.A.">
    <meta name="robots" content="index, follow">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Tipo de Cambio | Atlantic Maritime Agency">
    <meta property="og:description" content="Cotización diaria oficial y datos bancarios para pagos.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/logo-blue.svg">
    <meta property="og:url" content="https://atlantic-agency.example.com/tipo-de-cambio">
    <meta property="og:site_name" content="Atlantic Maritime Agency">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://atlantic-agency.example.com/tipo-de-cambio">

    <!-- Specific CSS for Internal Pages -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/tipo-de-cambio.css'); ?>">
</head>

<body>
    <!-- HEADER -->
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main" class="olt-page">

        <!-- HERO plano -->
        <header class="olt-hero-flat">
            <div class="olt-hero-flat__inner">
                <div class="olt-crumb"><a href="<?php echo BASE_URL; ?>" data-i18n="nav.home">Inicio</a> / <span data-i18n="exchange.title">Tipo de cambio</span></div>
                <h1 class="olt-h1" data-i18n="exchange.title_full">Tipo de cambio del día</h1>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <section class="olt-section">
            <div class="olt-container">

                <div class="tc-layout">

                    <!-- ═══════════════════════════════════════════════
                         Columna de la cotización. Es lo único que tiene
                         vigencia horaria: se reemplaza por un estado
                         cuando está cerrado o todavía no se cargó.
                         ═══════════════════════════════════════════════ -->
                    <div class="tc-main">

                        <!-- Loading -->
                        <div id="tc-loading" class="olt-state">
                            <svg class="olt-state__icon spinner-icon" viewBox="0 0 24 24" width="32" height="32" fill="currentColor" aria-hidden="true" style="margin-inline:auto"><path d="M12 2a10 10 0 0 1 10 10h-2a8 8 0 0 0-8-8V2z"/></svg>
                            <p class="olt-state__desc" data-i18n="exchange.loading">Cargando cotización...</p>
                        </div>

                        <!-- Estado cerrado / sin cotización (generado por JS) -->
                        <div id="tc-alert-container"></div>

                        <!-- Cotización vigente -->
                        <div id="tc-content" style="display: none;" class="olt-panel olt-panel--flush animate-fade-in">
                            <div class="olt-panel__head">
                                <span class="olt-kicker olt-kicker--inline" data-i18n="exchange.current_quote">Cotización vigente</span>
                                <span class="olt-panel__head-meta"><span data-i18n="exchange.published">Publicada</span> <span id="last-update">--</span></span>
                            </div>
                            <div class="tc-quote-card__grid">
                                <div class="tc-quote-card__cell olt-metric">
                                    <div class="olt-metric__label" data-i18n="exchange.usd">Dólar estadounidense</div>
                                    <div class="olt-metric__value olt-metric__value--xl tc-quote-card__value"><span class="tc-quote-card__cur">ARS</span><span id="val-usd">0,00</span></div>
                                    <div class="tc-quote-card__note" data-i18n="exchange.rate_valid_until">Venta · vigente hasta las 17:00</div>
                                </div>
                                <div class="tc-quote-card__cell olt-metric">
                                    <div class="olt-metric__label" data-i18n="exchange.eur">Euro</div>
                                    <div class="olt-metric__value olt-metric__value--xl tc-quote-card__value"><span class="tc-quote-card__cur">ARS</span><span id="val-eur">0,00</span></div>
                                    <div class="tc-quote-card__note" data-i18n="exchange.rate_valid_until">Venta · vigente hasta las 17:00</div>
                                </div>
                            </div>
                            <div class="olt-panel__foot">
                                <button id="btn-descargar-pdf" class="olt-btn olt-btn--primary">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align: sub; margin-right: 0.5rem;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                                    <span data-i18n="exchange.btn_download">Descargar comprobante PDF</span>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- ═══════════════════════════════════════════════
                         Datos bancarios: no vencen a las 17:00 y son
                         justo lo que busca quien entra de noche a
                         preparar una transferencia. Quedan fuera del
                         bloque de cotización para que sigan visibles
                         en todos los estados.
                         ═══════════════════════════════════════════════ -->
                    <aside class="tc-side">
                        <div class="olt-panel tc-bank-box">
                            <div class="olt-kicker" data-i18n="exchange.bank_data_title">Datos bancarios</div>
                            <div id="bank-data" class="tc-bank-box__body"><!-- Injected via JS --></div>
                            <div id="bank-data-empty" class="tc-bank-box__empty" style="display:none" data-i18n="exchange.bank_unavailable" data-i18n-html>Escribinos a <a href="mailto:tesoreria@atlantic-agency.example.com">tesoreria@atlantic-agency.example.com</a> y te enviamos los datos bancarios.</div>
                        </div>
                        <div class="olt-note-rule">
                            <p data-i18n="exchange.publish_note" data-i18n-html>La cotización se publica de lunes a viernes antes de las 10:00 y pierde vigencia a las 17:00. Consultas: <a href="mailto:tc@atlantic-agency.example.com">tc@atlantic-agency.example.com</a></p>
                        </div>
                    </aside>

                </div>

                <!-- Info panel: tampoco depende del horario -->
                <div class="tc-info-grid">
                    <div>
                        <div class="olt-kicker" data-i18n="exchange.how_applied_title">Cómo se aplica</div>
                        <p data-i18n="exchange.how_applied_body">La cotización publicada es la que se toma para liquidar gastos locales y facturar los servicios del día. Las operaciones cerradas después de las 17:00 se liquidan con la cotización de la jornada siguiente.</p>
                    </div>
                    <div>
                        <div class="olt-kicker" data-i18n="exchange.on_transfer_title">Al transferir</div>
                        <p data-i18n="exchange.on_transfer_body" data-i18n-html>Indicá el número de BL o booking en el concepto de la transferencia y enviá el comprobante a <a href="mailto:tesoreria@atlantic-agency.example.com">tesoreria@atlantic-agency.example.com</a> para que se emita el recibo.</p>
                    </div>
                    <div>
                        <div class="olt-kicker" data-i18n="exchange.contact_title">Consultas</div>
                        <p data-i18n="exchange.contact_desc_full" data-i18n-html>Diferencias de cambio, notas de crédito y estados de cuenta: <a href="mailto:tesoreria@atlantic-agency.example.com">tesoreria@atlantic-agency.example.com</a>, de 9 a 17 h.</p>
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
