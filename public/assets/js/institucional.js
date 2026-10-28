/**
 * AMA — institucional.js
 * Acordeón de vacantes, toggle "postular por formulario / por mail"
 * y envío del formulario de postulación a la API real (/api/postulantes).
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const tr = (key, fallback) => {
        if (typeof AmaI18n === 'undefined') return fallback;
        const val = AmaI18n.t(key);
        return (val && val !== key) ? val : fallback;
    };

    // ── Acordeón de vacantes ─────────────────────────────────────────────
    document.querySelectorAll('.olt-vacancy__head').forEach(head => {
        head.addEventListener('click', () => {
            const vacancy = head.closest('.olt-vacancy');
            const expanded = head.getAttribute('aria-expanded') === 'true';
            head.setAttribute('aria-expanded', String(!expanded));
            vacancy.classList.toggle('is-open', !expanded);
            const toggleIcon = head.querySelector('.olt-vacancy__toggle');
            if (toggleIcon) toggleIcon.textContent = expanded ? '+' : '−';
        });
    });

    // ── Toggle: postular por formulario / por mail ──────────────────────
    document.querySelectorAll('.olt-vacancy__apply-toggle').forEach(toggle => {
        const buttons = toggle.querySelectorAll('.olt-vacancy__apply-btn');
        const wrap = toggle.closest('.olt-vacancy__apply');
        const form = wrap.querySelector('.olt-vacancy__form');
        const mailbox = wrap.querySelector('.olt-vacancy__mailbox');

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                buttons.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                const isForm = btn.dataset.mode === 'form';
                form.hidden = !isForm;
                mailbox.hidden = isForm;
            });
        });
    });

    // ── Envío del formulario de postulación ──────────────────────────────
    document.querySelectorAll('.olt-vacancy__form').forEach(form => {
        const statusEl = form.querySelector('.olt-vacancy__status');
        const submitBtn = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const nombre = form.querySelector('[name="nombre"]').value.trim();
            const apellido = form.querySelector('[name="apellido"]').value.trim();
            const email = form.querySelector('[name="email"]').value.trim();
            const cvInput = form.querySelector('[name="cv"]');

            statusEl.className = 'olt-vacancy__status';
            statusEl.textContent = '';

            if (!nombre || !apellido || !email || !cvInput.files.length) {
                statusEl.classList.add('olt-vacancy__status--error');
                statusEl.textContent = tr('institutional.apply_err_required', 'Completá nombre, apellido, correo y adjuntá tu CV.');
                return;
            }

            const formData = new FormData(form);
            submitBtn.disabled = true;
            submitBtn.textContent = tr('institutional.apply_sending', 'Enviando...');

            fetch('/api/postulantes', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        statusEl.classList.add('olt-vacancy__status--ok');
                        statusEl.textContent = tr('institutional.apply_success', 'Postulación enviada correctamente.');
                        form.reset();
                    } else {
                        statusEl.classList.add('olt-vacancy__status--error');
                        const msg = (data.errors && data.errors[0]) || tr('institutional.apply_error', 'No se pudo enviar la postulación. Intentá nuevamente.');
                        statusEl.textContent = msg;
                    }
                })
                .catch(() => {
                    statusEl.classList.add('olt-vacancy__status--error');
                    statusEl.textContent = tr('institutional.apply_error', 'No se pudo enviar la postulación. Intentá nuevamente.');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = tr('institutional.btn_send_application', 'Enviar postulación');
                });
        });
    });
});
