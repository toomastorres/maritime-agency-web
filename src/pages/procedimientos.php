<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — procedimientos.php                             -->
<!-- Portal de Procedimientos Operativos e i18n Smart Routing -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Procedimientos y Trámites | AMA Agencies Argentina</title>
    <meta name="description"
        content="Guía operativa de procedimientos y trámites de AMA Agencies Argentina. Conozca los requisitos y envíe su solicitud.">
    <meta name="keywords" content="AMA, procedimientos, trámites, liberación importación, exportación, facturación, reclamos">
    <meta name="author" content="AMA Agencies Argentina S.A.">
    <meta name="robots" content="index, follow">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Procedimientos y Trámites | AMA Agencies Argentina">
    <meta property="og:description" content="Guía operativa interactiva de trámites y solicitudes para clientes.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/logo-blue.svg">
    <meta property="og:url" content="https://ama-demo.example/procedimientos">
    <meta property="og:site_name" content="AMA Agencies Argentina">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://ama-demo.example/procedimientos">

    <!-- Specific CSS for Internal Pages -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/procedimientos.css'); ?>">
</head>

<body class="has-hero">
    <!-- HEADER -->
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main">

        <!-- HERO -->
        <header class="hero hero--page" aria-labelledby="procedures-title">
            <div class="hero__bg-image" style="background-image: url('<?php echo ASSETS_URL; ?>/img/grande_francia_frente-1.png');"></div>
            <div class="hero__overlay"></div>
            <div class="hero__content contenedor-principal">
                <span class="hero__badge" data-i18n="procedures.badge">Guía Operativa</span>
                <h1 class="hero__title" id="procedures-title" data-i18n="procedures.title">Procedimientos y Trámites</h1>
                <p class="hero__subtitle" data-i18n="procedures.description">
                    Consulte los requisitos de cada operación y envíe su solicitud pre-formateada al departamento correspondiente.
                </p>
            </div>
        </header>

        <!-- SECCIÓN DE FILTROS Y TARJETAS -->
        <section class="section-wrapper procedures-section">
            <div class="contenedor-principal">
                
                <!-- Filtros Interactivos (Píldoras) -->
                <div class="procedures-filters-container">
                    <label class="filters-label" data-i18n="procedures.filter_label">Filtrar por tipo de trámite:</label>
                    <div class="procedures-filters">
                        <button class="filter-pill active" data-category="all" data-i18n="procedures.filter_all">Todos</button>
                        <button class="filter-pill" data-category="import" data-i18n="procedures.filter_impo">Importación</button>
                        <button class="filter-pill" data-category="export" data-i18n="procedures.filter_expo">Exportación</button>
                        <button class="filter-pill" data-category="claims" data-i18n="procedures.filter_claims">Reclamos e Inspección</button>
                        <button class="filter-pill" data-category="finance" data-i18n="procedures.filter_finance">Finanzas y Caja</button>
                        <button class="filter-pill" data-category="comercial" data-i18n="procedures.filter_comercial">Comercial</button>
                    </div>
                </div>

                <!-- Grid de Tarjetas de Procedimientos -->
                <div class="procedures-grid">
                    
                    <!-- Tarjeta 1: Liberación de Importación (Releases) -->
                    <div class="procedure-card" data-category="import">
                        <div class="procedure-card__badge" data-i18n="procedures.filter_impo">Importación</div>
                        <h3 class="procedure-card__title" data-i18n="procedures.p1_title">Liberación de Importación (Releases)</h3>
                        <p class="procedure-card__desc" data-i18n="procedures.p1_desc">Gestione la liberación y entrega de mercadería de importación arribada a Zárate.</p>
                        
                        <div class="procedure-card__requirements">
                            <h4 data-i18n="procedures.requirements_label">Requisitos obligatorios:</h4>
                            <ul>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p1_req1">Presentación del BL original endosado (o LOI Télex Release)</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p1_req2">Constancia de libre deuda emitida por la agencia</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p1_req3">Comprobante de pago de gastos locales</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="procedure-card__action">
                            <a href="mailto:customerservices@ama-demo.example" 
                               class="boton-primario mailto-link"
                               data-email="customerservices@ama-demo.example"
                               data-subject-key="procedures.p1_subject"
                               data-body-key="procedures.p1_body"
                               data-i18n="procedures.btn_start">
                                Iniciar Trámite por Email
                            </a>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Emisión de BLs de Exportación -->
                    <div class="procedure-card" data-category="export">
                        <div class="procedure-card__badge" data-i18n="procedures.filter_expo">Exportación</div>
                        <h3 class="procedure-card__title" data-i18n="procedures.p2_title">Emisión de BLs de Exportación</h3>
                        <p class="procedure-card__desc" data-i18n="procedures.p2_desc">Solicite la emisión y entrega de sus Bill of Lading (BL) para cargas de exportación.</p>
                        
                        <div class="procedure-card__requirements">
                            <h4 data-i18n="procedures.requirements_label">Requisitos obligatorios:</h4>
                            <ul>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p2_req1">Matriz de BL aprobada en sistema</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p2_req2">Comprobante de pago del flete y cargos locales</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p2_req3">Confirmación física de embarque del contenedor/unidad</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="procedure-card__action">
                            <a href="mailto:doc@ama-demo.example" 
                               class="boton-primario mailto-link"
                               data-email="doc@ama-demo.example"
                               data-subject-key="procedures.p2_subject"
                               data-body-key="procedures.p2_body"
                               data-i18n="procedures.btn_start">
                                Iniciar Trámite por Email
                            </a>
                        </div>
                    </div>

                    <!-- Tarjeta 3: Inspección de Carga en Zárate -->
                    <div class="procedure-card" data-category="claims">
                        <div class="procedure-card__badge" data-i18n="procedures.filter_claims">Reclamos e Inspección</div>
                        <h3 class="procedure-card__title" data-i18n="procedures.p3_title">Inspección de Carga en Zárate</h3>
                        <p class="procedure-card__desc" data-i18n="procedures.p3_desc">Solicite la inspección física de las unidades ante sospechas de daño previo al retiro de Terminal Zárate.</p>
                        
                        <div class="procedure-card__requirements">
                            <h4 data-i18n="procedures.requirements_label">Requisitos obligatorios:</h4>
                            <ul>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p3_req1">Presentación de Carta de Protesta formal</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p3_req2">Detalle del daño alegado con registro fotográfico inicial</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p3_req3">La unidad NO debe ser retirada del puerto hasta realizar el peritaje</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="procedure-card__action">
                            <a href="mailto:claims@ama-demo.example" 
                               class="boton-primario mailto-link"
                               data-email="claims@ama-demo.example"
                               data-subject-key="procedures.p3_subject"
                               data-body-key="procedures.p3_body"
                               data-i18n="procedures.btn_start">
                                Iniciar Trámite por Email
                            </a>
                        </div>
                    </div>

                    <!-- Tarjeta 4: Solicitud de Factura y Liquidación -->
                    <div class="procedure-card" data-category="finance">
                        <div class="procedure-card__badge" data-i18n="procedures.filter_finance">Finanzas y Caja</div>
                        <h3 class="procedure-card__title" data-i18n="procedures.p4_title">Solicitud de Factura y Liquidación</h3>
                        <p class="procedure-card__desc" data-i18n="procedures.p4_desc">Solicite la liquidación de gastos locales e importes de flete para facturación.</p>
                        
                        <div class="procedure-card__requirements">
                            <h4 data-i18n="procedures.requirements_label">Requisitos obligatorios:</h4>
                            <ul>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p4_req1">Número de BL o Booking de referencia</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p4_req2">Razón Social y CUIT a la cual facturar</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p4_req3">Desglose o detalle de los servicios adicionales requeridos</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="procedure-card__action">
                            <a href="mailto:finance@ama-demo.example" 
                               class="boton-primario mailto-link"
                               data-email="finance@ama-demo.example"
                               data-subject-key="procedures.p4_subject"
                               data-body-key="procedures.p4_body"
                               data-i18n="procedures.btn_start">
                                Iniciar Trámite por Email
                            </a>
                        </div>
                    </div>

                    <!-- Tarjeta 5: Comprobante de Pago y Recibo -->
                    <div class="procedure-card" data-category="finance">
                        <div class="procedure-card__badge" data-i18n="procedures.filter_finance">Finanzas y Caja</div>
                        <h3 class="procedure-card__title" data-i18n="procedures.p5_title">Comprobante de Pago y Recibo</h3>
                        <p class="procedure-card__desc" data-i18n="procedures.p5_desc">Informe transferencias de pagos realizados para la obtención del recibo oficial y habilitación de trámites.</p>
                        
                        <div class="procedure-card__requirements">
                            <h4 data-i18n="procedures.requirements_label">Requisitos obligatorios:</h4>
                            <ul>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p5_req1">Comprobante de transferencia bancaria adjunto</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p5_req2">Número de factura liquidada que se está cancelando</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p5_req3">CUIT y Razón Social del cliente</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="procedure-card__action">
                            <a href="mailto:finance@ama-demo.example" 
                               class="boton-primario mailto-link"
                               data-email="finance@ama-demo.example"
                               data-subject-key="procedures.p5_subject"
                               data-body-key="procedures.p5_body"
                               data-i18n="procedures.btn_start">
                                Iniciar Trámite por Email
                            </a>
                        </div>
                    </div>

                    <!-- Tarjeta 6: Cotización de Contenedores -->
                    <div class="procedure-card" data-category="comercial">
                        <div class="procedure-card__badge" data-i18n="procedures.filter_comercial">Comercial</div>
                        <h3 class="procedure-card__title" data-i18n="procedures.p6_title">Cotización de Contenedores (FCL/LCL)</h3>
                        <p class="procedure-card__desc" data-i18n="procedures.p6_desc">Solicite cotizaciones de flete internacional para mercadería contenerizada.</p>
                        
                        <div class="procedure-card__requirements">
                            <h4 data-i18n="procedures.requirements_label">Requisitos obligatorios:</h4>
                            <ul>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p6_req1">Puerto de origen y puerto de destino deseados</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p6_req2">Tipo y tamaño del contenedor (20ft, 40ft HC, Reefer, etc.)</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p6_req3">Descripción de la mercadería y peso bruto estimado</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="procedure-card__action">
                            <a href="mailto:containers@ama-demo.example" 
                               class="boton-primario mailto-link"
                               data-email="containers@ama-demo.example"
                               data-subject-key="procedures.p6_subject"
                               data-body-key="procedures.p6_body"
                               data-i18n="procedures.btn_start">
                                Iniciar Trámite por Email
                            </a>
                        </div>
                    </div>

                    <!-- Tarjeta 7: Cotización de Carga Rodante (Ro-Ro) -->
                    <div class="procedure-card" data-category="comercial">
                        <div class="procedure-card__badge" data-i18n="procedures.filter_comercial">Comercial</div>
                        <h3 class="procedure-card__title" data-i18n="procedures.p7_title">Cotización de Carga Rodante (Ro-Ro)</h3>
                        <p class="procedure-card__desc" data-i18n="procedures.p7_desc">Solicite fletes para vehículos automotores, camiones, buses o maquinaria autopropulsada.</p>
                        
                        <div class="procedure-card__requirements">
                            <h4 data-i18n="procedures.requirements_label">Requisitos obligatorios:</h4>
                            <ul>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p7_req1">Marca, modelo y año de fabricación de la unidad</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p7_req2">Dimensiones exactas (largo x ancho x alto en metros)</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p7_req3">Peso en toneladas y si la unidad rueda por sus propios medios</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="procedure-card__action">
                            <a href="mailto:cars.vans@ama-demo.example" 
                               class="boton-primario mailto-link"
                               data-email="cars.vans@ama-demo.example"
                               data-subject-key="procedures.p7_subject"
                               data-body-key="procedures.p7_body"
                               data-i18n="procedures.btn_start">
                                Iniciar Trámite por Email
                            </a>
                        </div>
                    </div>

                    <!-- Tarjeta 8: Cotización de Carga Proyecto -->
                    <div class="procedure-card" data-category="comercial">
                        <div class="procedure-card__badge" data-i18n="procedures.filter_comercial">Comercial</div>
                        <h3 class="procedure-card__title" data-i18n="procedures.p8_title">Cotización de Carga Proyecto</h3>
                        <p class="procedure-card__desc" data-i18n="procedures.p8_desc">Solicite tarifas para cargas sobredimensionadas, extrapesadas o proyectos industriales complejos.</p>
                        
                        <div class="procedure-card__requirements">
                            <h4 data-i18n="procedures.requirements_label">Requisitos obligatorios:</h4>
                            <ul>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p8_req1">Plano de estiba detallado o ficha técnica del bulto</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p8_req2">Puntos de izaje y centros de gravedad identificados</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span data-i18n="procedures.p8_req3">Peso total, requerimientos de cuna o trincado especial</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="procedure-card__action">
                            <a href="mailto:project@ama-demo.example" 
                               class="boton-primario mailto-link"
                               data-email="project@ama-demo.example"
                               data-subject-key="procedures.p8_subject"
                               data-body-key="procedures.p8_body"
                               data-i18n="procedures.btn_start">
                                Iniciar Trámite por Email
                            </a>
                        </div>
                    </div>

                </div><!-- /.procedures-grid -->
                
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <!-- SCRIPTS -->
    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/procedimientos.js'); ?>" defer></script>
</body>
</html>
