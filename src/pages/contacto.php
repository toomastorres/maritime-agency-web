<?php
// Modelo del directorio (orden, canales de área, referentes, slugs i18n).
// Único origen de verdad compartido con la API src/logic/api/contactos.php.
require_once __DIR__ . '/../logic/contactos_model.php';

$canalesArea             = contactos_canales_area();
$puestosConEmailPersonal = contactos_puestos_email_personal();

// Cargar empleados de la BD, agrupados y ordenados según el modelo institucional
$empleados = [];
try {
    require_once __DIR__ . '/../core/database.php';
    $rows = $pdo->query("SELECT * FROM employees")->fetchAll();
    $empleados = contactos_agrupar_y_ordenar($rows);
} catch (Exception $e) {
    $empleados = [];
}
?>
<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — contacto.php                                   -->
<!-- Página de directorio de contactos                         -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Directorio de Contactos | AMA Agencies Argentina</title>
    <meta name="description"
        content="Encuentre rápidamente al empleado de contacto en el departamento correspondiente de AMA Agencies Argentina.">
    <meta name="keywords"
        content="AMA contacto, directorio empleados, comercial, importación, exportación, finanzas">
    <meta name="author" content="AMA Agencies Argentina S.A.">
    <meta name="robots" content="index, follow">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Contactos | AMA Agencies Argentina">
    <meta property="og:description" content="Directorio de contactos de AMA Agencies Argentina.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/logo-blue.svg">
    <meta property="og:url" content="https://ama-demo.example/contacto">
    <meta property="og:site_name" content="AMA Agencies Argentina">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://ama-demo.example/contacto">

    <!-- CSS Design System (modular loading via JS / PHP) -->

    <!-- Specific CSS for Internal Pages -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/contacto.css'); ?>">
</head>

<body class="has-hero">
    <!-- HEADER -->
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main">

        <!-- PAGE HERO -->
        <header class="hero hero--page" aria-labelledby="contacto-title">
            <div class="hero__overlay"></div>
            <div class="hero__content contenedor-principal">
                <span class="hero__badge" data-i18n="contact.badge">Directorio</span>
                <h1 class="hero__title" id="contacto-title" data-i18n="contact.title">Contactos de Empleados</h1>
                <p class="hero__subtitle" data-i18n="contact.description">
                    Encuentre rápidamente a la persona de contacto en el departamento correspondiente.
                </p>
            </div>
        </header>

        <!-- CONTACT INFO GRID -->
        <section class="contacto-info-grid-section" aria-label="Información de contacto general">
            <div class="contenedor-principal">
                <div class="contacto-info-grid">

                    <div class="contacto-info-item">
                        <div class="contacto-info-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 data-i18n="contact.info_address_title">Dirección</h3>
                            <p data-i18n="contact.info_address">25 de Mayo 702, 1er Piso<br>CABA, Buenos Aires, Argentina</p>
                        </div>
                    </div>

                    <div class="contacto-info-item">
                        <div class="contacto-info-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 data-i18n="contact.info_phone_title">Teléfono</h3>
                            <p><a href="tel:+541140000000" data-i18n="contact.info_phone">+54 11 4000-0000</a></p>
                        </div>
                    </div>

                    <div class="contacto-info-item">
                        <div class="contacto-info-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 data-i18n="contact.info_email_title">Email General</h3>
                            <p><a href="mailto:customerservices@ama-demo.example" data-i18n="contact.info_email">customerservices@ama-demo.example</a></p>
                        </div>
                    </div>

                    <div class="contacto-info-item">
                        <div class="contacto-info-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div>
                            <h3 data-i18n="contact.info_hours_title">Horario</h3>
                            <p data-i18n="contact.info_hours">Lunes a Viernes<br>09:00 — 17:00 hs</p>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- MAIN CONTENT -->
        <section class="section-wrapper" aria-labelledby="contactos-titulo">
            <div class="contenedor-principal">

                <div class="directorio-header">
                    <h2 class="titulo-principal" data-i18n="contact.directory_title">Directorio de Contactos</h2>
                    <p data-i18n="contact.directory_desc">Encuentre rápidamente a la persona de contacto en el departamento correspondiente.</p>
                </div>

                <!-- Department filter buttons -->
                <?php if (!empty($empleados)): ?>
                <div class="filtro-sectores" id="filtro-container">
                    <button class="filtro-btn active" data-filter-dept="todos" data-i18n="departments.todos">Todos</button>
                    <?php foreach (array_keys($empleados) as $deptName): ?>
                    <button class="filtro-btn" data-filter-dept="<?php echo htmlspecialchars($deptName); ?>" data-i18n="departments.<?php echo getDeptKey($deptName); ?>">
                        <?php echo htmlspecialchars($deptName); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div id="gme-contactos-contenido" aria-live="polite">
                    <?php if (empty($empleados)): ?>
                    <p class="cuerpo-principal" style="text-align:center;" data-i18n="contact.directory_empty">Directorio no disponible en este momento.</p>
                    <?php else: ?>
                        <?php foreach ($empleados as $dept => $empleadosDept): ?>
                        <div class="dept-group sector-bloque" data-dept="<?php echo htmlspecialchars($dept); ?>">
                            <div class="area-titulo" data-i18n="departments.<?php echo getDeptKey($dept); ?>"><?php echo htmlspecialchars($dept); ?></div>
                            <?php if (!empty($canalesArea[$dept])): ?>
                            <div class="area-canales">
                                <span class="area-canales__label" data-i18n="contact.area_channels">Contacto del área:</span>
                                <?php foreach ($canalesArea[$dept] as $canal): ?>
                                <a class="canal-chip" href="mailto:<?php echo htmlspecialchars($canal['email']); ?>">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                    <span class="canal-chip__label" data-i18n="<?php echo htmlspecialchars($canal['label']); ?>"></span>
                                    <span class="canal-chip__email"><?php echo htmlspecialchars($canal['email']); ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php $cantidadEmpleados = is_array($empleadosDept) || $empleadosDept instanceof Countable ? count($empleadosDept) : 0; ?>
                            <div class="contacto-grid <?php echo $cantidadEmpleados === 4 ? 'grid-simetrico-4' : ''; ?>">
                                <?php foreach ($empleadosDept as $e): ?>
                                <?php $esReferente = in_array($e['puesto'] ?? '', $puestosConEmailPersonal, true); ?>
                                <div class="contact-card<?php echo $esReferente ? ' contact-card--directo' : ''; ?>">
                                    <div class="linea-azul2"></div>
                                    <?php if ($esReferente): ?>
                                    <span class="card-badge" data-i18n="contact.direct_contact">Contacto directo</span>
                                    <?php endif; ?>
                                    <?php
                                        // Generar iniciales del empleado
                                        $initials = '';
                                        if (!empty($e['nombre'])) $initials .= mb_substr(trim($e['nombre']), 0, 1);
                                        if (!empty($e['apellido'])) $initials .= mb_substr(trim($e['apellido']), 0, 1);
                                        $initials = strtoupper($initials);
                                        if (empty($initials)) $initials = 'G'; // Fallback
                                    ?>
                                    <div class="avatar-iniciales">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="nombre"><?php echo htmlspecialchars(strtoupper($e['apellido'] ?? '') . ' ' . ($e['nombre'] ?? '')); ?></div>
                                        <div class="posicion" data-i18n="positions.<?php echo getPositionKey($e['puesto'] ?? $e['posicion'] ?? ''); ?>"><?php echo htmlspecialchars($e['puesto'] ?? $e['posicion'] ?? ''); ?></div>
                                        <?php if ($esReferente && !empty($e['email'])): ?>
                                        <div class="email">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                            <a href="mailto:<?php echo htmlspecialchars($e['email']); ?>"><?php echo htmlspecialchars($e['email']); ?></a>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($e['telefono'])): ?>
                                        <div class="telefono">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                            <span><?php echo htmlspecialchars($e['telefono']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="linea-azul"></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <!-- JavaScript Base -->
    <script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/main.js'); ?>" defer></script>
    <script src="<?php echo assetUrl('js/contacto.js'); ?>" defer></script>
</body>

</html>
