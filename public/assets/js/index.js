/**
 * AMA — index.js
 * Lógica de la página principal: franja operativa (tipo de cambio,
 * próxima llegada a Zárate), tabla de próximas salidas y novedades.
 * Todo se alimenta de las APIs reales del sitio (/api/tipo_cambio,
 * /api/schedules, /api/novedades), con estados de carga/error/vacío.
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // AmaI18n.t() devuelve la propia key si no encuentra traducción — lo que
    // también pasa si esta función corre antes de que termine de cargar el JSON
    // de idioma (fetch en paralelo). Se detecta ese caso y se usa el fallback.
    const tr = (key, fallback) => {
        if (typeof AmaI18n === 'undefined') return fallback;
        const val = AmaI18n.t(key);
        return (val && val !== key) ? val : fallback;
    };

    // Los tiles de la franja operativa son <a> completos, así que lo que se
    // inyecte adentro no puede ser otro enlace: un <a> anidado es HTML inválido
    // y el navegador lo desarma, rompiendo el layout del tile.
    const muted = (txt) => `<span class="home-strip__muted">${txt}</span>`;

    // ── Tipo de cambio del día ───────────────────────────────────────────
    function loadTipoCambio() {
        const valueEl = document.getElementById('home-tc-value');
        const metaEl  = document.getElementById('home-tc-meta');
        if (!valueEl) return;

        fetch('/api/tipo_cambio')
            .then(res => res.json())
            .then(result => {
                if (result.status === 'ok' && result.data && result.data.dolar_valor) {
                    const monto = Number(result.data.dolar_valor).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    valueEl.innerHTML = `<span class="home-strip__tc-currency">USD</span><span>${monto}</span>`;
                    if (metaEl) metaEl.textContent = tr('home.tc_note', 'Venta · cotización del día');
                    return;
                }
                if (result.status === 'cerrado') {
                    valueEl.innerHTML = muted(tr('home.tc_closed', 'Fuera de horario — ver tipo de cambio'));
                    return;
                }
                valueEl.innerHTML = muted(tr('home.tc_unavailable', 'Ver tipo de cambio'));
            })
            .catch(() => {
                valueEl.innerHTML = muted(tr('home.tc_unavailable', 'Ver tipo de cambio'));
            });
    }

    // ── Schedules: próxima llegada a Zárate + próximas salidas ──────────
    function parseDdMmYyyy(str) {
        if (!str || typeof str !== 'string') return null;
        const parts = str.split('/');
        if (parts.length !== 3) return null;
        const [d, m, y] = parts.map(Number);
        if (!d || !m || !y) return null;
        return new Date(y, m - 1, d);
    }

    function buildZarateArrivals(data) {
        const isZarate = (p) => typeof p === 'string' && /z[aá]rate/i.test(p);
        const arrivals = [];

        (data.rutas || []).forEach(ruta => {
            const viajes = ruta.viajes || [];
            const origin = (viajes[0] && typeof viajes[0].puerto === 'string') ? viajes[0].puerto : null;

            viajes.forEach(v => {
                if (!isZarate(v.puerto) || !v.eta) return;
                const etaDate = parseDdMmYyyy(v.eta);
                if (!etaDate) return;
                arrivals.push({
                    vessel: ruta.nombre || '—',
                    voyage: ruta.voyage_code || '',
                    service: data.service_name || '',
                    origin: (origin && origin !== v.puerto) ? origin : '—',
                    eta: v.eta,
                    etd: v.ets || '—',
                    etaDate: etaDate,
                });
            });
        });

        arrivals.sort((a, b) => a.etaDate - b.etaDate);
        return arrivals;
    }

    function renderDeparturesEmpty(msg) {
        const body = document.getElementById('home-departures-body');
        if (body) body.innerHTML = `<tr><td colspan="5" class="home-departures__status">${msg}</td></tr>`;
    }

    function loadSchedules() {
        const arrivalValueEl = document.getElementById('home-arrival-value');
        const arrivalMetaEl  = document.getElementById('home-arrival-meta');
        const body = document.getElementById('home-departures-body');
        if (!body && !arrivalValueEl) return;

        const scheduleUrl = '/schedule';
        const fallbackText = tr('home.departures_empty', 'Consultá el schedule completo');
        // La tabla no está dentro de un enlace, así que ahí sí va un <a> real.
        const fallbackMsg = `<a href="${scheduleUrl}">${fallbackText}</a>`;
        const arrivalFallback = muted(fallbackText);

        fetch('/api/schedules')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'error' || data.status === 'sin_datos' || !data.rutas || !data.rutas.length) {
                    if (arrivalValueEl) arrivalValueEl.innerHTML = arrivalFallback;
                    renderDeparturesEmpty(fallbackMsg);
                    return;
                }

                const arrivals = buildZarateArrivals(data);

                if (!arrivals.length) {
                    if (arrivalValueEl) arrivalValueEl.innerHTML = arrivalFallback;
                    renderDeparturesEmpty(fallbackMsg);
                    return;
                }

                const next = arrivals[0];
                if (arrivalValueEl) {
                    arrivalValueEl.textContent = '';
                    arrivalValueEl.innerHTML = `${next.vessel} ${next.voyage ? `<span class="voyage">V. ${next.voyage}</span>` : ''}`;
                }
                if (arrivalMetaEl) {
                    const parts = [`ETA ${next.eta}`];
                    if (next.etd && next.etd !== '—') parts.push(`ETD ${next.etd}`);
                    if (next.service) parts.push(next.service);
                    arrivalMetaEl.textContent = parts.join(' · ');
                }

                if (body) {
                    body.innerHTML = arrivals.slice(0, 4).map(a => `
                        <tr>
                            <td><span class="vessel-name">${a.vessel}</span> ${a.voyage ? `<span class="voyage">V. ${a.voyage}</span>` : ''}</td>
                            <td class="is-secondary">${a.service || '—'}</td>
                            <td class="is-secondary">${a.origin}</td>
                            <td class="is-right">${a.eta}</td>
                            <td class="is-right">${a.etd}</td>
                        </tr>
                    `).join('');
                }
            })
            .catch(() => {
                if (arrivalValueEl) arrivalValueEl.innerHTML = arrivalFallback;
                renderDeparturesEmpty(fallbackMsg);
            });
    }

    loadTipoCambio();
    loadSchedules();
});
