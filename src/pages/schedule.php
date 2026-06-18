<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — schedule.php                                   -->
<!-- Página de itinerarios de buques                           -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Schedules | AMA Agencies Argentina</title>
    <meta name="description"
        content="Fechas estimadas de arribo y salida (ETA/ETS) de los buques del Grupo AMA desde y hacia Argentina.">
    <meta name="keywords"
        content="AMA, schedule, cronograma de buques, ETA, ETS, Zárate, puertos, llegada de barcos">
    <meta name="author" content="AMA Agencies Argentina S.A.">
    <meta name="robots" content="index, follow">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Schedules | AMA Agencies Argentina">
    <meta property="og:description" content="Fechas estimadas de arribo y salida de buques AMA.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/logo-blue.svg">
    <meta property="og:url" content="https://ama-demo.example/schedule">
    <meta property="og:site_name" content="AMA Agencies Argentina">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://ama-demo.example/schedule">

    <!-- Specific CSS for Internal Pages -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/schedule.css'); ?>">
</head>

<body class="has-hero">
    <!-- HEADER -->
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main">

        <!-- HERO -->
        <header class="hero hero--page" aria-labelledby="schedule-title">
            <div class="hero__bg-image" style="background-image: url('<?php echo ASSETS_URL; ?>/img/grande_amburgo.png');"></div>
            <div class="hero__overlay"></div>
            <div class="hero__content contenedor-principal">
                <span class="hero__badge" data-i18n="schedule.badge">Próximos Arribos</span>
                <h1 class="hero__title" id="schedule-title" data-i18n="schedule.title">Vessel Schedule</h1>
                <p class="hero__subtitle" data-i18n="schedule.description">
                    Calendario actualizado de fechas estimadas de arribo y salida (ETA/ETS) de los buques del Grupo AMA desde y hacia Argentina. Filtra por buque, puerto de carga o descarga para encontrar la información que necesitas.
                </p>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <section class="section-wrapper" style="min-height: 50vh;">
            <div class="contenedor-principal">

                <!-- 4-col Filter Bar -->
                <div class="schedule-filters-bar" id="schedule-ui" style="display: none;">
                    <div class="schedule-filter-group">
                        <label class="schedule-filter-label" data-i18n="schedule.filter_vessel">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20 21c-1.39 0-2.78-.47-4-1.32-2.44 1.71-5.56 1.71-8 0C6.78 20.53 5.39 21 4 21H2v2h2c1.38 0 2.74-.35 4-.99 2.52 1.29 5.48 1.29 8 0 1.26.65 2.62.99 4 .99h2v-2h-2zM3.95 19H4c1.6 0 3.02-.88 4-2 .98 1.12 2.4 2 4 2s3.02-.88 4-2c.98 1.12 2.4 2 4 2h.05l1.89-6.68c.08-.26.06-.54-.06-.78s-.34-.42-.6-.5L20 10.62V6c0-1.1-.9-2-2-2h-3V1H9v3H6c-1.1 0-2 .9-2 2v4.62l-1.29.42c-.26.08-.48.26-.6.5s-.15.52-.06.78L3.95 19zM6 6h12v3.97L12 8 6 9.97V6z"/></svg>
                            Vessel Name
                        </label>
                        <select id="vessel-select" class="schedule-select">
                            <option value="" data-i18n="schedule.filter_all_vessels">Todos los Buques</option>
                        </select>
                    </div>
                    <div class="schedule-filter-group">
                        <label class="schedule-filter-label" data-i18n="schedule.filter_pol">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            Port of Loading (POL)
                        </label>
                        <select id="pol-select" class="schedule-select">
                            <option value="" data-i18n="schedule.filter_all_pol">Todos los Orígenes</option>
                        </select>
                    </div>
                    <div class="schedule-filter-group">
                        <label class="schedule-filter-label" data-i18n="schedule.filter_pod">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/></svg>
                            Port of Discharge (POD)
                        </label>
                        <select id="pod-select" class="schedule-select">
                            <option value="" data-i18n="schedule.filter_all_pod">Todos los Destinos</option>
                        </select>
                    </div>
                    <div class="schedule-filter-actions">
                        <button id="btn-apply-filters" class="boton-primario schedule-btn-apply" data-i18n="schedule.btn_filter">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></svg>
                            Filtrar
                        </button>
                        <button id="btn-reset-filters" class="schedule-btn-reset" aria-label="Limpiar filtros">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Route Search Results (populated by JS when POL+POD selected) -->
                <div id="route-search-results" class="route-search-panel animate-fade-in" style="display: none;"></div>

                <!-- Export Controls (shows with table) -->
                <div class="schedule-export-bar animate-fade-in" id="export-bar" style="display: none;">
                    <p class="texto-info"><span data-i18n="schedule.showing_week">Mostrando schedule — semana</span> <span id="week-label">actual</span></p>
                    <div class="schedule-export-actions">
                        <button id="btn-export-pdf" class="boton-pdf" style="margin-right: 0.5rem;">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3h-2.5v1.5h2v1.5h-2v2H17V7h3.5v1.5zM9 9.5h1v-1H9v1zM14 12h-1V8.5h1V12zM3 6H1v14c0 1.1.9 2 2 2h14v-2H3V6z"/></svg>
                            PDF
                        </button>
                        <button id="btn-export-xlsx" class="boton-excel">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                            EXCEL (.xlsx)
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="schedule-loading" class="alerta-estado text-center">
                    <svg class="spinner-icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 1 10 10h-2a8 8 0 0 0-8-8V2z"/></svg> <span data-i18n="schedule.loading">Cargando tabla de schedule...</span>
                </div>

                <!-- Table Area -->
                <div class="table-responsive animate-fade-in" id="table-container" style="display: none;">
                    <table class="AMA-table AMA-matrix-table" id="schedule-table">
                        <thead id="schedule-head"></thead>
                        <tbody id="schedule-body"></tbody>
                    </table>
                </div>

                <!-- Empty State (No results) -->
                <div id="schedule-empty-state" class="schedule-empty-state animate-fade-in" style="display: none;">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                        <path d="M20 21c-1.39 0-2.78-.47-4-1.32-2.44 1.71-5.56 1.71-8 0C6.78 20.53 5.39 21 4 21H2v2h2c1.38 0 2.74-.35 4-.99 2.52 1.29 5.48 1.29 8 0 1.26.65 2.62.99 4 .99h2v-2h-2zM3.95 19H4c1.6 0 3.02-.88 4-2 .98 1.12 2.4 2 4 2s3.02-.88 4-2c.98 1.12 2.4 2 4 2h.05l1.89-6.68c.08-.26.06-.54-.06-.78s-.34-.42-.6-.5L20 10.62V6c0-1.1-.9-2-2-2h-3V1H9v3H6c-1.1 0-2 .9-2 2v4.62l-1.29.42c-.26.08-.48.26-.6.5s-.15.52-.06.78L3.95 19zM6 6h12v3.97L12 8 6 9.97V6z" opacity="0.3"/>
                    </svg>
                    <h4 class="titulo-contenido text-center" data-i18n="schedule.no_results_title">No se encontraron viajes</h4>
                    <p class="texto-info text-center" data-i18n="schedule.no_results_desc">Intenta ajustar o limpiar los filtros de búsqueda.</p>
                </div>


                <!-- Info Panel -->
                <div class="tc-info-panel">
                    <div class="tc-info-panel__item">
                        <div class="tc-info-panel__icon">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                        </div>
                        <p class="tc-info-panel__title" data-i18n="schedule.hours_title">Horarios de Atención</p>
                        <p class="tc-info-panel__value" data-i18n="schedule.hours_desc">Lunes a Viernes: 9:00 - 17:00</p>
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
                        <p class="tc-info-panel__title" data-i18n="schedule.contact_title">Contacto Comercial</p>
                        <p class="tc-info-panel__value" style="margin-bottom: 0.5rem;" data-i18n="schedule.contact_desc">Consultas sobre itinerarios</p>
                        <a href="mailto:customerserv@ama-demo.example" class="tc-info-panel__action">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                            <span data-i18n="schedule.btn_contact">Contactar</span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <!-- Importación de librerías para exportar a PDF / Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" defer></script>
    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/schedule.js'); ?>" defer></script>
</body>

</html>
