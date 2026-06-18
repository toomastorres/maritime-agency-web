<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — footer.php                                     -->
<!-- Componente de pie de página corporativo                   -->
<!-- ═══════════════════════════════════════════════════════════ -->
<footer class="AMA-footer" role="contentinfo">
    <div class="footer-inner">
        <div class="footer-grid">

            <!-- Column 1: Brand & Contact -->
            <div class="footer-brand">
                <img src="<?php echo ASSETS_URL; ?>/img/logo-white.svg" alt="AMA Agencies Argentina" width="160"
                    height="36" loading="lazy">
                <p data-i18n="footer.company_name">AMA Agencies Argentina S.A.</p>
                <div class="footer-contact-item">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                    </svg>
                    <span data-i18n="footer.address">25 de Mayo 702, 1er Piso, CABA, Argentina</span>
                </div>
                <!-- FIX: phone number wrapped in tel: link for mobile accessibility -->
                <div class="footer-contact-item">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
                    </svg>
                    <a href="tel:+541140000000" data-i18n="footer.phone">+54 11 4000-0000</a>
                </div>
                <div class="footer-contact-item">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                    </svg>
                    <span data-i18n="footer.schedule">Lunes a Viernes: 9:00hs a 17:00hs</span>
                </div>
            </div>

            <!-- Column 2: Quick Links -->
            <div class="footer-links">
                <h4 data-i18n="footer.quick_links">Enlaces rápidos</h4>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>" data-i18n="nav.home">Inicio</a></li>
                    <li><a href="<?php echo BASE_URL; ?>tipo-de-cambio" data-i18n="nav.exchange">Tipo de Cambio</a></li>
                    <li><a href="<?php echo BASE_URL; ?>schedule" data-i18n="nav.schedule">Schedule</a></li>
                    <li><a href="<?php echo BASE_URL; ?>servicios" data-i18n="nav.services">Servicios y Comercial</a></li>
                    <!-- <li><a href="<?php echo BASE_URL; ?>procedimientos" data-i18n="nav.procedures">Procedimientos</a></li> -->
                    <!-- <li><a href="<?php echo BASE_URL; ?>institucional" data-i18n="nav.institutional">Institucional</a></li> -->
                    <li><a href="<?php echo BASE_URL; ?>contacto" data-i18n="nav.contact">Contacto</a></li>
                </ul>
            </div>

            <!-- Column 3: Legal -->
            <div class="footer-links">
                <h4 data-i18n="footer.legal">Información Legal</h4>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>politica-de-privacidad" data-i18n="footer.privacy">Política de
                            Privacidad</a></li>
                    <li><a href="<?php echo BASE_URL; ?>politicas-de-cookies" data-i18n="footer.cookies">Políticas de
                            Cookies</a></li>
                    <li><a href="<?php echo BASE_URL; ?>terminos-y-condiciones" data-i18n="footer.terms">Términos y
                            Condiciones</a></li>
                    <li><a href="https://www.AMA.napoli.it" target="_blank" rel="noopener noreferrer"
                            data-i18n="footer.group_site">Sitio del Grupo AMA</a></li>
                </ul>
            </div>

            <!-- Column 4: Newsletter FUERA DE SCOPE POR AHORA, SE PUEDE REHABILITAR EN EL FUTURO -->
            <!--
            <div class="footer-newsletter">
                <h4 data-i18n="footer.newsletter_title">Newsletter</h4>
                <p data-i18n="footer.newsletter_desc">Reciba actualizaciones sobre schedules y noticias de la industria marítima.</p>
                <form id="newsletter-form" class="newsletter-form">
                    <input type="email"
                        data-i18n-placeholder="footer.newsletter_placeholder"
                        placeholder="Su email"
                        aria-label="Email para newsletter"
                        required>
                    <button type="submit" data-i18n="footer.newsletter_btn">Suscribirse</button>
                </form>
            </div>
            -->
        </div>

        <div class="footer-bottom">
            <span data-i18n="footer.copyright">© 2026 AMA Agencies Argentina S.A. Todos los derechos
                reservados.</span>
            <a href="https://www.AMA.napoli.it" target="_blank" rel="noopener noreferrer">
                <img src="<?php echo ASSETS_URL; ?>/img/logo-white.svg" alt="AMA Agencies Argentina" width="40" height="40"
                    loading="lazy">
            </a>
        </div>
    </div>
</footer>

<!-- SweetAlert2 CDN for aesthetic popups -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" defer></script>
<script src="<?php echo assetUrl('js/newsletter.js'); ?>" defer></script>
