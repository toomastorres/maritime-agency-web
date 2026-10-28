/**
 * AMA — servicios.js
 * Lógica del cotizador de Servicios: selección de tipo de carga,
 * resumen dinámico y envío a la API real de cotizaciones.
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const form        = document.getElementById('quote-form');
    if (!form) return;

    const typeButtons  = form.querySelectorAll('.olt-quote__type');
    const servicioInput = document.getElementById('quote-servicio');
    const summaryLabel = document.getElementById('quote-summary-label');
    const summaryMail  = document.getElementById('quote-summary-mail');
    const submitBtn    = document.getElementById('quote-submit-btn');
    const statusEl     = document.getElementById('quote-status');

    const tr = (key, fallback) => {
        if (typeof AmaI18n === 'undefined') return fallback;
        const val = AmaI18n.t(key);
        return (val && val !== key) ? val : fallback;
    };

    typeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            typeButtons.forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            servicioInput.value = btn.dataset.service;
            summaryLabel.textContent = tr(btn.dataset.labelKey, btn.dataset.label);
            summaryMail.textContent = btn.dataset.mail;
        });
    });

    function setStatus(kind, msg) {
        statusEl.className = 'olt-quote__status olt-quote__status--' + kind;
        statusEl.textContent = msg;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        setStatus('', '');

        if (!servicioInput.value) {
            setStatus('error', tr('services_page.quote_err_type', 'Seleccioná un tipo de carga antes de enviar.'));
            return;
        }
        const nombre = document.getElementById('quote-nombre').value.trim();
        const email  = document.getElementById('quote-email').value.trim();
        if (!nombre || !email) {
            setStatus('error', tr('services_page.quote_err_required', 'Completá nombre y correo electrónico.'));
            return;
        }

        const destino     = document.getElementById('quote-destino').value.trim();
        const mercaderia  = document.getElementById('quote-mercaderia').value.trim();
        const mensajeParts = [];
        if (destino) mensajeParts.push('Destino: ' + destino);
        if (mercaderia) mensajeParts.push('Mercadería: ' + mercaderia);

        const payload = {
            servicio: servicioInput.value,
            nombre: nombre,
            empresa: document.getElementById('quote-empresa').value.trim(),
            email: email,
            telefono: document.getElementById('quote-telefono').value.trim(),
            mensaje: mensajeParts.join(' · '),
            website_url: document.getElementById('quote-honeypot').value,
        };

        submitBtn.disabled = true;
        submitBtn.textContent = tr('services_page.quote_sending', 'Enviando...');

        fetch('/api/cotizacion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    setStatus('ok', tr('services_page.quote_success', 'Solicitud enviada. Te responderemos dentro de las 48 horas hábiles.'));
                    form.reset();
                    typeButtons.forEach(b => b.classList.remove('is-active'));
                    summaryLabel.textContent = tr('services_page.quote_summary_empty', 'Seleccione un tipo de carga');
                    summaryMail.textContent = '—';
                } else {
                    setStatus('error', tr('services_page.quote_error', 'No se pudo enviar la solicitud. Intentá nuevamente.'));
                }
            })
            .catch(() => {
                setStatus('error', tr('services_page.quote_error', 'No se pudo enviar la solicitud. Intentá nuevamente.'));
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = tr('services_page.quote_submit', 'Enviar solicitud');
            });
    });
});
