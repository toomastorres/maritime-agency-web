<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AMA — footer.php                                     -->
<!-- Componente de pie de página corporativo — sistema Oltremare -->
<!-- ═══════════════════════════════════════════════════════════ -->
<footer class="olt-footer" role="contentinfo">
    <div class="olt-footer__inner">
        <div class="olt-footer__grid">

            <!-- Columna 1: Marca y contacto -->
            <div class="olt-footer__brand">
                <img src="<?php echo ASSETS_URL; ?>/img/logo-white.svg" alt="Atlantic Maritime Agency" class="olt-footer__logo" loading="lazy">
                <p class="olt-footer__address">
                    <span data-i18n="footer.company_name">Atlantic Maritime Agency S.A.</span><br>
                    <span data-i18n="footer.address">Av. Puerto Madero 450, Piso 8, CABA, Argentina</span><br>
                    <a href="tel:+541140000000" data-i18n="footer.phone">+54 11 4000-0000</a>
                </p>
            </div>

            <!-- Columna 2: Operación -->
            <div class="olt-footer__col">
                <h4 data-i18n="footer.col_operations">Operación</h4>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>tipo-de-cambio" data-i18n="nav.exchange">Tipo de cambio</a></li>
                    <li><a href="<?php echo BASE_URL; ?>schedule" data-i18n="nav.schedule">Schedule</a></li>
                    <li><a href="<?php echo BASE_URL; ?>servicios" data-i18n="nav.services">Servicios</a></li>
                </ul>
            </div>

            <!-- Columna 3: Agencia -->
            <div class="olt-footer__col">
                <h4 data-i18n="footer.col_agency">Agencia</h4>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>institucional" data-i18n="nav.institutional">Institucional</a></li>
                    <li><a href="<?php echo BASE_URL; ?>institucional#vacantes" data-i18n="footer.careers">Trabajá con nosotros</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contacto" data-i18n="footer.contact_directory">Directorio de contactos</a></li>
                    <li><a href="<?php echo BASE_URL; ?>private/gestion-interna-agencia/" data-i18n="footer.internal_portal">Portal interno</a></li>
                </ul>
            </div>

            <!-- Columna 4: Contacto directo -->
            <div class="olt-footer__col">
                <h4 data-i18n="footer.col_direct_contact">Contacto directo</h4>
                <!-- Los cuatro buzones comerciales, no sólo dos: containers estaba
                     puesto como si fuera "Comercial" a secas y las casillas de
                     carga proyecto y Ro-Ro no aparecían en ninguna parte del pie. -->
                <ul class="olt-footer__contact-list">
                    <li>
                        <span data-i18n="footer.cs_label">Customer Service</span>
                        <a href="mailto:customerserv@atlantic-agency.example.com">customerserv@atlantic-agency.example.com</a>
                    </li>
                    <li>
                        <span data-i18n="contact.ch_contenedores">Contenedores (Impo/Expo)</span>
                        <a href="mailto:containers@atlantic-agency.example.com">containers@atlantic-agency.example.com</a>
                    </li>
                    <li>
                        <span data-i18n="contact.ch_projects">Carga proyecto</span>
                        <a href="mailto:projects@atlantic-agency.example.com">projects@atlantic-agency.example.com</a>
                    </li>
                    <li>
                        <span data-i18n="contact.ch_autos">Autos / Ro-Ro</span>
                        <a href="mailto:cars.vans@atlantic-agency.example.com">cars.vans@atlantic-agency.example.com</a>
                    </li>
                </ul>
            </div>

        </div>

        <div class="olt-footer__bottom">
            <span data-i18n="footer.copyright">© 2026 Atlantic Maritime Agency S.A. — agente de Atlantic Maritime Group</span>
            <div class="olt-footer__legal">
                <a href="<?php echo BASE_URL; ?>terminos-y-condiciones" data-i18n="footer.terms">Términos y condiciones</a>
                <a href="<?php echo BASE_URL; ?>politica-de-privacidad" data-i18n="footer.privacy">Política de privacidad</a>
                <a href="<?php echo BASE_URL; ?>politicas-de-cookies" data-i18n="footer.cookies">Política de cookies</a>
            </div>
        </div>
    </div>
</footer>

<!-- SweetAlert2 CDN for aesthetic popups -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" defer></script>
