<?php
/**
 * ============================================================
 *  Atlantic Maritime Agency — 404 No Encontrado
 *  Sistema Oltremare: usa header/footer reales para no dejar
 *  al visitante en una página aislada sin navegación.
 * ============================================================
 */
require_once __DIR__ . '/src/config/config.php';
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada | Atlantic Maritime Agency</title>
    <meta name="robots" content="noindex, follow">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">

    <!-- Specific CSS for this page -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/404.css'); ?>">
</head>

<body data-i18n-page="error404">
    <!-- HEADER -->
    <?php require_once __DIR__ . '/src/components/header.php'; ?>

    <main id="main-content" role="main" class="olt-page">

        <!-- HERO plano -->
        <header class="olt-hero-flat olt-404__hero">
            <svg class="olt-404__hero-icon" viewBox="0 0 24 24" width="220" height="220" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="5" r="2"/>
                <line x1="12" y1="7" x2="12" y2="21"/>
                <line x1="8" y1="10" x2="16" y2="10"/>
                <path d="M5 12H2a10 10 0 0 0 10 10 10 10 0 0 0 10-10h-3"/>
            </svg>
            <div class="olt-hero-flat__inner">
                <div class="olt-kicker--light" data-i18n="error404.kicker">Error 404</div>
                <h1 class="olt-h1" data-i18n="error404.title">Página no encontrada</h1>
                <p class="olt-hero-flat__subtitle" data-i18n="error404.subtitle">La página que busca no existe, cambió de dirección o no está disponible temporalmente.</p>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <section class="olt-section olt-404__section">
            <div class="olt-container olt-404__layout">

                <div class="olt-404__digits-col">
                    <div class="olt-404__digits" aria-hidden="true">404</div>
                    <div class="olt-404__digits-rule"></div>
                </div>

                <div class="olt-404__body">
                    <p data-i18n="error404.body">Verifique la dirección ingresada o utilice alguno de los siguientes accesos para continuar navegando el sitio.</p>

                    <div class="olt-404__actions">
                        <a href="<?php echo BASE_URL; ?>" class="olt-btn olt-btn--primary" data-i18n="error404.cta_home">Volver al inicio</a>
                        <a href="<?php echo BASE_URL; ?>contacto" class="olt-btn olt-btn--outline" data-i18n="error404.cta_contact">Contactar a la agencia</a>
                    </div>

                    <hr class="olt-rule olt-rule--soft olt-404__rule">

                    <div class="olt-404__links">
                        <span class="olt-kicker olt-kicker--inline" data-i18n="error404.links_label">Accesos directos</span>
                        <a class="olt-404__link-chip" href="<?php echo BASE_URL; ?>tipo-de-cambio" data-i18n="nav.exchange">Tipo de Cambio</a>
                        <a class="olt-404__link-chip" href="<?php echo BASE_URL; ?>schedule" data-i18n="nav.schedule">Schedule</a>
                        <a class="olt-404__link-chip" href="<?php echo BASE_URL; ?>servicios" data-i18n="nav.services">Servicios</a>
                        <a class="olt-404__link-chip" href="<?php echo BASE_URL; ?>institucional" data-i18n="nav.institutional">Institucional</a>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/src/components/footer.php'; ?>

    <!-- JavaScript Base -->
    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
</body>

</html>
