/**
 * AMA — contacto.js
 * Filtro de departamentos para el directorio de contactos (renderizado server-side).
 * No hay fetch/AJAX para el directorio — los empleados los genera PHP directamente en el HTML.
 * El formulario de consulta general no tiene endpoint dedicado: arma un mailto: con lo cargado.
 */

document.addEventListener('DOMContentLoaded', () => {
    const tr = (key, fallback) => {
        const val = (typeof AmaI18n !== 'undefined') ? AmaI18n.t(key) : null;
        return (val && val !== key) ? val : fallback;
    };

    // Department filter buttons
    document.querySelectorAll('[data-filter-dept]').forEach(btn => {
        btn.addEventListener('click', () => {
            const dept = btn.dataset.filterDept;

            // Update active state
            document.querySelectorAll('[data-filter-dept]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Show/hide department groups
            document.querySelectorAll('.dept-group').forEach(g => {
                if (dept === 'todos' || g.dataset.dept === dept) {
                    g.style.display = '';
                } else {
                    g.style.display = 'none';
                }
            });

            // El listado se maqueta con columnas CSS: al filtrar queda una sola
            // tarjeta y el navegador la partiría al medio para repartirla entre
            // las dos columnas. Con un área elegida se pasa a una columna.
            const cont = document.getElementById('gme-contactos-contenido');
            if (cont) cont.classList.toggle('is-filtrado', dept !== 'todos');
        });
    });

    // General inquiry form → mailto: (no hay endpoint de consulta general en el backend)
    const DEFAULT_MAILBOX = 'customerserv@atlantic-agency.example.com';
    const inquiryForm = document.getElementById('contact-inquiry-form');
    const inquiryStatus = document.getElementById('ci-status');
    if (inquiryForm) {
        inquiryForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('ci-name').value.trim();
            const email = document.getElementById('ci-email').value.trim();
            const sectorEl = document.getElementById('ci-sector');
            const opt = sectorEl ? sectorEl.options[sectorEl.selectedIndex] : null;
            const message = document.getElementById('ci-message').value.trim();

            // El asunto lleva la etiqueta traducida que ve el visitante, no el
            // value interno, que siempre está en castellano.
            const sector = opt && opt.value ? opt.textContent.trim() : '';
            // Con un sector elegido la consulta va al buzón de esa área; sin
            // sector, a Customer Service, que es la puerta de entrada general.
            const destino = (opt && opt.dataset.mail) ? opt.dataset.mail : DEFAULT_MAILBOX;

            const subject = encodeURIComponent('Consulta' + (sector ? ' — ' + sector : '') + (name ? ' — ' + name : ''));
            const replyLine = email ? '\n\nResponder a: ' + email : '';
            const body = encodeURIComponent(message + (name ? '\n\n' + name : '') + replyLine);
            window.location.href = `mailto:${destino}?subject=${subject}&body=${body}`;

            if (inquiryStatus) {
                inquiryStatus.className = 'olt-form-status olt-form-status--ok';
                inquiryStatus.textContent = tr('contact.form_status_sent', 'Se abrió tu programa de correo con la consulta redactada. Si no se abrió, escribinos directamente a') + ' ' + destino + '.';
            }
        });
    }
});
