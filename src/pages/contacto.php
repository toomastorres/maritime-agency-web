<?php
// Modelo del directorio institucional por área de trabajo (orden,
// descripción y canal de contacto de cada departamento). No hay datos
// de personal acá: el directorio es de áreas, no de empleados.
require_once __DIR__ . '/../logic/contactos_model.php';

$departamentos = contactos_orden_departamentos();
$canalesArea   = contactos_canales_area();
$descArea      = contactos_descripcion_area();
?>
<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — contacto.php                                   -->
<!-- Directorio de contacto — sistema Oltremare                 -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!DOCTYPE html>
<html lang="es" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Directorio de Contactos | Atlantic Maritime Agency</title>
    <meta name="description"
        content="Encuentre rápidamente el área de contacto correspondiente a su operación en Atlantic Maritime Agency.">
    <meta name="keywords"
        content="AMA contacto, directorio de áreas, comercial, importación, exportación, finanzas">
    <meta name="author" content="Atlantic Maritime Agency S.A.">
    <meta name="robots" content="index, follow">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Contactos | Atlantic Maritime Agency">
    <meta property="og:description" content="Directorio de contactos de Atlantic Maritime Agency.">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/img/logo-blue.svg">
    <meta property="og:url" content="https://atlantic-agency.example.com/contacto">
    <meta property="og:site_name" content="Atlantic Maritime Agency">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/logo-mark.svg">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://atlantic-agency.example.com/contacto">

    <!-- Specific CSS for Internal Pages -->
    <link rel="stylesheet" href="<?php echo assetUrl('css/contacto.css'); ?>">
</head>

<body>
    <!-- HEADER -->
    <?php require_once __DIR__ . '/../components/header.php'; ?>

    <main id="main-content" role="main" class="olt-page">

        <!-- HERO plano -->
        <header class="olt-hero-flat">
            <div class="olt-hero-flat__inner">
                <div class="olt-crumb"><a href="<?php echo BASE_URL; ?>" data-i18n="nav.home">Inicio</a> / <span data-i18n="nav.contact">Contacto</span></div>
                <h1 class="olt-h1" data-i18n="contact.title_full">Directorio de contacto</h1>
                <p class="olt-hero-flat__subtitle" data-i18n="contact.subtitle_full">Escriba directamente al sector que corresponde a su operación. Respondemos de lunes a viernes, de 9:00 a 17:00.</p>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <section class="olt-section">
            <div class="olt-container">

                <!-- Filtro por sector -->
                <div class="olt-contact-filters" id="filtro-container">
                    <button class="olt-contact-filters__btn active" data-filter-dept="todos" data-i18n="departments.todos">Todos</button>
                    <?php foreach ($departamentos as $dept): ?>
                    <button class="olt-contact-filters__btn" data-filter-dept="<?php echo htmlspecialchars($dept); ?>" data-i18n="departments.<?php echo getDeptKey($dept); ?>">
                        <?php echo htmlspecialchars($dept); ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div id="gme-contactos-contenido" aria-live="polite">
                    <?php foreach ($departamentos as $dept): ?>
                    <?php $deptKey = getDeptKey($dept); ?>
                    <!-- El título del área va dentro de la tarjeta: suelto arriba de la
                         caja se leía como una etiqueta flotando, desconectada de su
                         contenido, y en la grilla de dos columnas no quedaba claro a
                         qué caja pertenecía. -->
                    <div class="dept-group olt-contact-group" data-dept="<?php echo htmlspecialchars($dept); ?>">
                        <div class="olt-panel olt-panel--flush olt-area-card">
                            <div class="olt-area-card__head">
                                <h2 class="olt-area-card__title" data-i18n="departments.<?php echo $deptKey; ?>"><?php echo htmlspecialchars($dept); ?></h2>
                                <p class="olt-area-card__desc" data-i18n="contact.dept_desc.<?php echo $deptKey; ?>"><?php echo htmlspecialchars($descArea[$dept] ?? ''); ?></p>
                            </div>

                            <?php if (!empty($canalesArea[$dept])): ?>
                            <!-- Cada buzón es una fila entera enlazada, no una pastilla gris
                                 de 20px: escribirle al área correcta es el objetivo de esta
                                 página y era el elemento más chico y menos visible de ella. -->
                            <!-- Dos acciones por buzón, no una. Antes la fila entera era el
                                 enlace al mail y el interno era texto muerto; ahora el interno
                                 marca la línea de la agencia y envía la extensión por tono, y
                                 el mail sigue abriendo el correo. Por eso la fila ya no puede
                                 ser un <a>: un enlace dentro de otro es HTML inválido. -->
                            <ul class="olt-channel-list">
                                <?php foreach ($canalesArea[$dept] as $canal): ?>
                                <li class="olt-channel">
                                    <span class="olt-channel__label" data-i18n="<?php echo htmlspecialchars($canal['label']); ?>"></span>
                                    <span class="olt-channel__actions">
                                        <?php foreach (($canal['int'] ?? []) as $interno): ?>
                                        <a class="olt-channel__act olt-channel__act--tel"
                                           href="<?php echo htmlspecialchars(contactos_tel_href($interno)); ?>">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.2.4 2.4.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.4 0 .8-.2 1l-2.3 2.2z"/></svg>
                                            <span class="olt-channel__act-txt"><span data-i18n="contact.ext_short">Int.</span> <?php echo htmlspecialchars($interno); ?></span>
                                        </a>
                                        <?php endforeach; ?>
                                        <a class="olt-channel__act olt-channel__act--mail"
                                           href="mailto:<?php echo htmlspecialchars($canal['email']); ?>">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                            <span class="olt-channel__act-txt"><?php echo htmlspecialchars($canal['email']); ?></span>
                                        </a>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Oficina + consulta -->
                <div class="olt-contact-bottom">
                    <div class="olt-contact-office">
                        <h2 class="olt-h2" data-i18n="contact.office_title">Nuestra oficina</h2>
                        <p data-i18n="contact.office_address_full" data-i18n-html="true">Av. Puerto Madero 450, Piso 8<br>C1107AAB — Ciudad Autónoma de Buenos Aires<br>República Argentina</p>
                        <div class="olt-contact-office__grid">
                            <div>
                                <div class="olt-kicker" data-i18n="contact.info_phone_title">Teléfono</div>
                                <div class="olt-contact-office__value"><a href="<?php echo htmlspecialchars(contactos_tel_href()); ?>" data-i18n="contact.info_phone"><?php echo htmlspecialchars(contactos_tel_legible()); ?></a></div>
                                <div class="olt-contact-office__hint" data-i18n="contact.phone_ext_hint">Cada área tiene su interno: marcalo desde el directorio de arriba.</div>
                            </div>
                            <div>
                                <div class="olt-kicker" data-i18n="contact.info_hours_title">Horario</div>
                                <div class="olt-contact-office__value" data-i18n="contact.office_hours_full">Lunes a viernes, 9:00 – 17:00</div>
                            </div>
                        </div>
                    </div>

                    <div class="olt-contact-form-box">
                        <h2 class="olt-h3" data-i18n="contact.form_title">Enviar una consulta</h2>
                        <form id="contact-inquiry-form" novalidate>
                            <div class="olt-field">
                                <label for="ci-name" data-i18n="contact.form_name">Nombre y empresa</label>
                                <input type="text" id="ci-name" required>
                            </div>
                            <div class="olt-field">
                                <label for="ci-email" data-i18n="contact.form_email">Correo electrónico</label>
                                <input type="email" id="ci-email" required>
                            </div>
                            <!-- Select y no texto libre: la página entera está organizada
                                 por área, así que la consulta puede decir a cuál va en vez
                                 de depender de lo que el visitante escriba a mano. -->
                            <div class="olt-field">
                                <label for="ci-sector" data-i18n="contact.form_sector">Sector</label>
                                <select id="ci-sector">
                                    <option value="" data-i18n="contact.form_sector_any">Consulta general</option>
                                    <?php foreach ($departamentos as $dept): ?>
                                    <?php // El buzón del área va en el option: elegir el sector ahora
                                          // manda la consulta al área y no siempre a Customer Service.
                                          $buzon = $canalesArea[$dept][0]['email'] ?? ''; ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"
                                            data-mail="<?php echo htmlspecialchars($buzon); ?>"
                                            data-i18n="departments.<?php echo getDeptKey($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="olt-field">
                                <label for="ci-message" data-i18n="contact.form_message">Consulta</label>
                                <textarea id="ci-message" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="olt-btn olt-btn--primary olt-btn--block" data-i18n="contact.form_submit">Enviar consulta</button>
                            <div id="ci-status" class="olt-form-status" role="status" aria-live="polite"></div>
                        </form>
                    </div>
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
