/**
 * AMA — schedule.js
 * Lógica de la página de itinerarios: tabla, filtros, exportación PDF.
 */

document.addEventListener('DOMContentLoaded', () => {
    const loading        = document.getElementById('schedule-loading');
    const tableContainer = document.getElementById('table-container');
    const controlsUI     = document.getElementById('schedule-ui');
    const exportBar      = document.getElementById('export-bar');
    const vesselSelect   = document.getElementById('vessel-select');
    const polSelect      = document.getElementById('pol-select');
    const podSelect      = document.getElementById('pod-select');
    const thead          = document.getElementById('schedule-head');
    const tbody          = document.getElementById('schedule-body');
    const lastUpdate     = document.getElementById('last-update');
    const weekLabel      = document.getElementById('week-label');
    const btnApply       = document.getElementById('btn-apply-filters');
    const btnReset       = document.getElementById('btn-reset-filters');
    const btnExportPDF   = document.getElementById('btn-export-pdf');
    const btnExportXLSX  = document.getElementById('btn-export-xlsx');
    const emptyState     = document.getElementById('schedule-empty-state');
    const routePanel     = document.getElementById('route-search-results');

    let scheduleData = null;

    // ── Mensajes con estilo (el proyecto no carga FontAwesome → iconos SVG inline) ──
    const tr = (key, fallback) => {
        const val = (typeof AmaI18n !== 'undefined') ? AmaI18n.t(key) : null;
        return (val && val !== key) ? val : fallback;
    };
    const SVG_INFO  = '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v2h-2zm0 4h2v6h-2z"/></svg>';
    const SVG_ERROR = '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';

    // Muestra un estado (vacío / error) con estilo dentro del contenedor de carga
    function showScheduleStatus(kind, msg) {
        if (!loading) return;
        const icon = kind === 'error' ? SVG_ERROR : SVG_INFO;
        loading.className = (kind === 'error' ? 'alerta-error' : 'alerta-estado') + ' text-center alerta-con-icono';
        loading.style.display = '';
        loading.innerHTML = `<span class="alerta-icono">${icon}</span><span>${msg}</span>`;
    }

    // Aviso de error de exportación con estilo (reemplaza alert() nativo del navegador)
    function showExportNotice(msg) {
        let n = document.getElementById('export-notice');
        if (!n) {
            n = document.createElement('div');
            n.id = 'export-notice';
            n.className = 'alerta-error alerta-con-icono';
            n.style.marginTop = '1rem';
            n.setAttribute('role', 'alert');
            if (exportBar && exportBar.parentNode) {
                exportBar.parentNode.insertBefore(n, exportBar.nextSibling);
            } else {
                document.body.appendChild(n);
            }
        }
        n.innerHTML = `<span class="alerta-icono">${SVG_ERROR}</span><span>${msg}</span>`;
        n.style.display = 'flex';
        clearTimeout(n._hideTimer);
        n._hideTimer = setTimeout(() => { n.style.display = 'none'; }, 6000);
    }

    fetch('/api/schedules')
        .then(res => res.json())
        .then(data => {
            // Error reportado por la API (p. ej. fallo de base de datos): mensaje con estilo, nunca crudo
            if (data.status === 'error') {
                showScheduleStatus('error', tr('schedule.error_loading', 'Error al cargar la tabla de schedules. Por favor intente nuevamente.'));
                return;
            }
            if (data.status === 'sin_datos' || !data.rutas || data.rutas.length === 0) {
                showScheduleStatus('info', tr('schedule.no_data', 'No hay schedules publicados por el momento.'));
                return;
            }

            scheduleData = data;

            const titleEl = document.getElementById('schedule-title');
            const noteBar = document.getElementById('schedule-note-bar');
            if (titleEl && data.service_name) {
                titleEl.removeAttribute('data-i18n');
                titleEl.textContent = data.service_name;
            }
            if (noteBar) noteBar.style.display = 'flex';

            renderFreshness(data);
            if (weekLabel && data.week_number) weekLabel.innerText = data.week_number;

            populateFilters(data);

            if (loading)        loading.style.display        = 'none';
            if (controlsUI)     controlsUI.style.display     = 'grid';
            if (exportBar)      exportBar.style.display      = 'flex';

            renderMatrixTables();
            setupInteractions();
            if (typeof AmaI18n !== 'undefined') AmaI18n.updateDOM();
        })
        .catch(() => {
            showScheduleStatus('error', tr('schedule.error_loading', 'Error al cargar la tabla de schedules. Por favor intente nuevamente.'));
        });

    // ── Frescura del schedule ────────────────────────────────────────────
    // El archivo que publica CS es semanal, así que si la última carga tiene
    // más de 7 días quiere decir que la semana en curso todavía no se subió.
    // Eso cambia cómo hay que leer la tabla y hasta ahora no se decía en
    // ningún lado: la fecha de carga vivía en un recuadro del pie.
    const DIAS_SCHEDULE_VIEJO = 7;

    function parseDdMmYyyy(str) {
        if (!str || typeof str !== 'string') return null;
        const m = str.trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!m) return null;
        const d = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));
        return isNaN(d.getTime()) ? null : d;
    }

    function renderFreshness(data) {
        if (lastUpdate) lastUpdate.innerText = data.ultima_actualizacion || '--';

        const ageEl = document.getElementById('last-update-age');
        const fecha = parseDdMmYyyy(data.ultima_actualizacion);
        if (!ageEl || !fecha) return;

        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const dias = Math.round((hoy - fecha) / 86400000);
        if (dias < 0) return;

        ageEl.textContent = dias === 0
            ? ` · ${tr('schedule.updated_today', 'hoy')}`
            : ` · ${tr('schedule.updated_days_ago', 'hace {n} días').replace('{n}', dias)}`;

        if (dias > DIAS_SCHEDULE_VIEJO) {
            ageEl.classList.add('is-stale');
            showStaleNote(dias);
        }
    }

    function showStaleNote(dias) {
        const anchor = document.getElementById('export-bar');
        if (!anchor || document.getElementById('schedule-stale-note')) return;
        const note = document.createElement('div');
        note.id = 'schedule-stale-note';
        note.className = 'olt-note-bar olt-note-bar--warn';
        note.innerHTML =
            '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">'
            + '<path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM13 17h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>'
            + '<div class="olt-note-bar__text">'
            + `<span data-i18n="schedule.stale_note">${tr('schedule.stale_note', 'Este es el último schedule publicado y tiene más de una semana. Antes de operar, confirmá las fechas con Customer Service.')}</span> `
            + '<a href="mailto:customerserv@atlantic-agency.example.com">customerserv@atlantic-agency.example.com</a>'
            + '</div>';
        anchor.parentNode.insertBefore(note, anchor);
    }

    // ── Populate filter dropdowns ──────────────────────────────────────────────
    function populateFilters(data) {
        // Vessels
        while (vesselSelect.options.length > 1) vesselSelect.remove(1);
        data.rutas.forEach((ruta, idx) => {
            const opt   = document.createElement('option');
            opt.value   = idx;
            const vCode = ruta.voyage_code ? ` (${ruta.voyage_code})` : '';
            opt.textContent = ruta.nombre + vCode;
            vesselSelect.appendChild(opt);
        });

        // Unique Ports for POL and POD
        rebuildPortSelects();
    }

    function rebuildPortSelects(selectedVesselIdx) {
        const ports = new Set();

        if (selectedVesselIdx !== undefined && selectedVesselIdx !== '') {
            const ruta = scheduleData.rutas[parseInt(selectedVesselIdx)];
            if (ruta) {
                ruta.viajes.forEach(v => {
                    const pname = v.puerto || v.POL || v.puerto_origen || v.POD || v.puerto_destino;
                    if (pname) ports.add(pname);
                });
            }
        } else {
            scheduleData.rutas.forEach(ruta => {
                ruta.viajes.forEach(v => {
                    const pname = v.puerto || v.POL || v.puerto_origen || v.POD || v.puerto_destino;
                    if (pname) ports.add(pname);
                });
            });
        }

        const sortedPorts = Array.from(ports).filter(Boolean).sort();

        const prevPol = polSelect.value;
        const prevPod = podSelect.value;

        while (polSelect.options.length > 1) polSelect.remove(1);
        while (podSelect.options.length > 1) podSelect.remove(1);

        sortedPorts.forEach(port => {
            const optPol = document.createElement('option');
            optPol.value = port;
            optPol.textContent = port;
            polSelect.appendChild(optPol);

            const optPod = document.createElement('option');
            optPod.value = port;
            optPod.textContent = port;
            podSelect.appendChild(optPod);
        });

        // Restore previous selections if still valid
        if (prevPol && sortedPorts.includes(prevPol)) polSelect.value = prevPol;
        if (prevPod && sortedPorts.includes(prevPod)) podSelect.value = prevPod;
    }

    // ── Setup interactions ─────────────────────────────────────────────────────
    function setupInteractions() {
        const updateClass = (el) => {
            if (el.value) el.classList.add('has-value');
            else el.classList.remove('has-value');
        };

        vesselSelect.addEventListener('change', () => {
            updateClass(vesselSelect);
            rebuildPortSelects(vesselSelect.value);
            renderMatrixTables();
            renderRouteSearch();
        });

        polSelect.addEventListener('change', () => {
            updateClass(polSelect);
            renderMatrixTables();
            renderRouteSearch();
        });

        podSelect.addEventListener('change', () => {
            updateClass(podSelect);
            renderMatrixTables();
            renderRouteSearch();
        });

        if (btnApply) btnApply.addEventListener('click', () => {
            renderMatrixTables();
            renderRouteSearch();
        });

        // El botón del estado vacío hace lo mismo que el de la barra: cuando
        // un filtro no devuelve nada, la salida está ahí y no 600px más arriba.
        const resetAll = () => {
            vesselSelect.value = '';
            polSelect.value = '';
            podSelect.value = '';
            [vesselSelect, polSelect, podSelect].forEach(updateClass);
            rebuildPortSelects();
            renderMatrixTables();
            renderRouteSearch();
        };
        [btnReset, document.getElementById('btn-reset-filters-empty')].forEach(btn => {
            if (btn) btn.addEventListener('click', resetAll);
        });
    }

    // ── Route Search: Find departure/arrival dates ─────────────────────────────
    function findRoute(ruta, polName, podName) {
        let polVisit = null, podVisit = null, polIdx = -1;

        // Find the first POL visit
        for (let i = 0; i < ruta.viajes.length; i++) {
            const v = ruta.viajes[i];
            const pname = v.puerto || '';
            if (pname === polName && !v.is_feeder) {
                polVisit = v;
                polIdx = i;
                break;
            }
        }

        if (polVisit && polIdx >= 0) {
            // Find POD that comes AFTER POL in the sequence
            for (let i = polIdx + 1; i < ruta.viajes.length; i++) {
                const v = ruta.viajes[i];
                const pname = v.puerto || '';
                if (pname === podName && !v.is_feeder) {
                    podVisit = v;
                    break;
                }
            }
        }

        return (polVisit && podVisit) ? { pol: polVisit, pod: podVisit } : null;
    }

    function renderRouteSearch() {
        if (!routePanel || !scheduleData) return;

        const polValue = polSelect.value;
        const podValue = podSelect.value;
        const vIdx = vesselSelect.value;

        // Only show route search when both POL and POD are selected
        if (!polValue || !podValue) {
            routePanel.style.display = 'none';
            return;
        }

        if (polValue === podValue) {
            routePanel.style.display = 'none';
            return;
        }

        // Find routes for selected vessels (or all if none selected)
        const routesToCheck = vIdx !== ''
            ? [scheduleData.rutas[parseInt(vIdx)]]
            : scheduleData.rutas;

        const results = [];
        routesToCheck.forEach(ruta => {
            if (!ruta) return;
            const match = findRoute(ruta, polValue, podValue);
            if (match) {
                results.push({
                    vessel: ruta.nombre,
                    voyage: ruta.voyage_code || '',
                    polPort: polValue,
                    podPort: podValue,
                    ets: match.pol.ets || match.pol.eta || '--',
                    eta: match.pod.eta || '--',
                });
            }
        });

        if (results.length === 0) {
            routePanel.innerHTML = `
                <div class="route-search-header">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <span data-i18n="schedule_dynamic.route_search">Búsqueda de Ruta</span>
                </div>
                <div class="route-no-results">
                    <p><span data-i18n="schedule_dynamic.no_route">No se encontró ruta directa</span> <span data-i18n="schedule_dynamic.from">de</span> <strong>${escHtml(polValue)}</strong> <span data-i18n="schedule_dynamic.to">a</span> <strong>${escHtml(podValue)}</strong> <span data-i18n="schedule_dynamic.in_trips">en los viajes disponibles.</span></p>
                </div>`;
            routePanel.style.display = 'block';
            if (typeof AmaI18n !== 'undefined') AmaI18n.updateDOM();
            return;
        }

        let html = `
            <div class="route-search-header">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <span><span data-i18n="schedule_dynamic.results_for">Resultados de Ruta:</span> ${escHtml(polValue)} → ${escHtml(podValue)}</span>
            </div>
            <div class="route-results-grid">`;

        results.forEach(r => {
            html += `
                <div class="route-result-card">
                    <div class="route-vessel-info">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M20 21c-1.39 0-2.78-.47-4-1.32-2.44 1.71-5.56 1.71-8 0C6.78 20.53 5.39 21 4 21H2v2h2c1.38 0 2.74-.35 4-.99 2.52 1.29 5.48 1.29 8 0 1.26.65 2.62.99 4 .99h2v-2h-2zM3.95 19H4c1.6 0 3.02-.88 4-2 .98 1.12 2.4 2 4 2s3.02-.88 4-2c.98 1.12 2.4 2 4 2h.05l1.89-6.68c.08-.26.06-.54-.06-.78s-.34-.42-.6-.5L20 10.62V6c0-1.1-.9-2-2-2h-3V1H9v3H6c-1.1 0-2 .9-2 2v4.62l-1.29.42c-.26.08-.48.26-.6.5s-.15.52-.06.78L3.95 19zM6 6h12v3.97L12 8 6 9.97V6z"/></svg>
                        <strong>${escHtml(r.vessel)}</strong>
                        ${r.voyage ? `<span class="route-voyage-code">${escHtml(r.voyage)}</span>` : ''}
                    </div>
                    <div class="route-ports-row">
                        <div class="route-port-block route-port-departure">
                            <span class="route-port-label"><span data-i18n="schedule_dynamic.departure">Salida de</span> ${escHtml(r.polPort)}</span>
                            <span class="route-port-date">${escHtml(r.ets)}</span>
                            <span class="route-port-type">ETS</span>
                        </div>
                        <div class="route-arrow">
                            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                        </div>
                        <div class="route-port-block route-port-arrival">
                            <span class="route-port-label"><span data-i18n="schedule_dynamic.arrival">Arribo a</span> ${escHtml(r.podPort)}</span>
                            <span class="route-port-date">${escHtml(r.eta)}</span>
                            <span class="route-port-type">ETA</span>
                        </div>
                    </div>
                </div>`;
        });

        html += `</div>`;
        routePanel.innerHTML = html;
        routePanel.style.display = 'block';
        if (typeof AmaI18n !== 'undefined') AmaI18n.updateDOM();
    }

    // ── Matrix Table Filter Logic ──────────────────────────────────────────────
    function isRouteMatch(ruta, vesselIdx, polValue, podValue, index) {
        if (vesselIdx !== '' && parseInt(vesselIdx) !== index) return false;

        let hasPol = polValue === '';
        let hasPod = podValue === '';

        ruta.viajes.forEach(v => {
            const portName = v.puerto || v.POL || v.puerto_origen || v.POD || v.puerto_destino || '';
            if (polValue && portName.toLowerCase().includes(polValue.toLowerCase())) hasPol = true;
            if (podValue && portName.toLowerCase().includes(podValue.toLowerCase())) hasPod = true;
        });

        return hasPol && hasPod;
    }

    // ── Build the filtered Port × Vessel matrix model (shared by exports) ──────
    function buildMatrixModel() {
        if (!scheduleData) return { vessels: [], ports: [] };

        const vIdx = vesselSelect.value;
        const pPol = polSelect.value;
        const pPod = podSelect.value;

        const filteredRoutes = scheduleData.rutas.filter((ruta, idx) => isRouteMatch(ruta, vIdx, pPol, pPod, idx));
        if (filteredRoutes.length === 0) return { vessels: [], ports: [] };

        const portOrderMap = new Map();
        filteredRoutes.forEach(ruta => {
            ruta.viajes.forEach(v => {
                const pname = v.puerto || v.POL || v.puerto_origen || v.POD || v.puerto_destino || 'Unknown Port';
                const leg = v.leg || 'outbound';
                const key = pname + '||' + leg;
                if (!portOrderMap.has(key)) {
                    const order = (v.port_order !== undefined && v.port_order !== null) ? v.port_order : portOrderMap.size;
                    portOrderMap.set(key, { order: order, port: pname, leg: leg });
                } else {
                    const existing = portOrderMap.get(key);
                    if (v.port_order !== undefined && v.port_order !== null && v.port_order < existing.order) {
                        existing.order = v.port_order;
                    }
                }
            });
        });

        const uniquePortEntries = Array.from(portOrderMap.values()).sort((a, b) => a.order - b.order);

        const vessels = filteredRoutes.map(r => ({ nombre: r.nombre, voyage_code: r.voyage_code || '' }));
        const ports = uniquePortEntries.map(portInfo => {
            const isReturn = portInfo.leg === 'return';
            const isZarate = portInfo.port.toLowerCase().includes('zarate');
            const cells = filteredRoutes.map(ruta => {
                const visit = ruta.viajes.find(v => {
                    const pname = v.puerto || v.POL || v.puerto_origen || v.POD || v.puerto_destino;
                    const vleg = v.leg || 'outbound';
                    return pname === portInfo.port && vleg === portInfo.leg;
                });
                if (!visit) return { present: false, eta: '–', ets: '–', isFeeder: false, feederCode: '' };
                return {
                    present: true,
                    eta: visit.eta || visit.ETA || '–',
                    ets: visit.ets || visit.ETD || visit.ETS || '–',
                    isFeeder: !!visit.is_feeder,
                    feederCode: visit.feeder_code || ''
                };
            });
            return { name: portInfo.port, leg: portInfo.leg, isReturn: isReturn, isZarate: isZarate, cells: cells };
        });

        return { vessels: vessels, ports: ports };
    }

    // ── Render Matrix Table ────────────────────────────────────────────────────
    // Vista mobile: una tarjeta por buque con solo sus escalas reales.
    // La matriz sirve para comparar buques entre sí; en un ancho de teléfono
    // eso no entra, y lo que el visitante busca es "cuándo llega este buque".
    function renderVesselCards(routes, portEntries) {
        const cards = document.getElementById('schedule-cards');
        if (!cards) return;

        const etaLabel = tr('schedule_dynamic.eta', 'ETA');
        const etsLabel = tr('schedule_dynamic.ets', 'ETS');

        cards.innerHTML = routes.map(ruta => {
            const calls = portEntries.map(portInfo => {
                const visit = ruta.viajes.find(v => {
                    const pname = v.puerto || v.POL || v.puerto_origen || v.POD || v.puerto_destino;
                    return pname === portInfo.port && (v.leg || 'outbound') === portInfo.leg;
                });
                if (!visit) return '';

                const isZarate = portInfo.port.toLowerCase().includes('zarate');
                const isReturn = portInfo.leg === 'return';
                const eta = visit.is_feeder
                    ? `<span class="feeder-label">${escHtml(tr('schedule_dynamic.feeder', 'feeder'))}</span>`
                    : escHtml(visit.eta || visit.ETA || '—');
                const ets = (visit.is_feeder && visit.feeder_code)
                    ? `<span class="feeder-code">${escHtml(visit.feeder_code)}</span>`
                    : escHtml(visit.ets || visit.ETD || visit.ETS || '—');

                const cls = ['olt-sched-call'];
                if (isZarate) cls.push('is-zarate');
                if (isReturn) cls.push('is-return');

                return `
                    <li class="${cls.join(' ')}">
                        <span class="olt-sched-call__port">${escHtml(portInfo.port)}${isReturn ? ` <span class="port-return-indicator">↩</span>` : ''}</span>
                        <span class="olt-sched-call__dates">
                            <span class="olt-sched-call__date"><em>${escHtml(etaLabel)}</em>${eta}</span>
                            <span class="olt-sched-call__date"><em>${escHtml(etsLabel)}</em>${ets}</span>
                        </span>
                    </li>`;
            }).join('');

            return `
                <article class="olt-sched-card">
                    <header class="olt-sched-card__head">
                        <span class="olt-sched-card__vessel">${escHtml(ruta.nombre)}</span>
                        ${ruta.voyage_code ? `<span class="olt-sched-card__voyage">V. ${escHtml(ruta.voyage_code)}</span>` : ''}
                    </header>
                    <ul class="olt-sched-card__calls">${calls}</ul>
                </article>`;
        }).join('');
    }

    function renderMatrixTables() {
        if (!scheduleData) return;

        const vIdx = vesselSelect.value;
        const pPol = polSelect.value;
        const pPod = podSelect.value;

        // 1. Filter routes
        const filteredRoutes = scheduleData.rutas.filter((ruta, idx) => isRouteMatch(ruta, vIdx, pPol, pPod, idx));

        if (filteredRoutes.length === 0) {
            tableContainer.style.display = 'none';
            emptyState.style.display = 'flex';
            exportBar.style.display = 'none';
            return;
        }

        tableContainer.style.display = 'block';
        emptyState.style.display = 'none';
        exportBar.style.display = 'flex';

        // 2. Build unique port rows using port+leg as composite key
        //    Use port_order from the API (Excel row position) for correct ordering
        const portOrderMap = new Map();

        filteredRoutes.forEach(ruta => {
            ruta.viajes.forEach(v => {
                const pname = v.puerto || v.POL || v.puerto_origen || v.POD || v.puerto_destino || 'Unknown Port';
                const leg = v.leg || 'outbound';
                const key = pname + '||' + leg;
                if (!portOrderMap.has(key)) {
                    // Use port_order from API data if available, otherwise fall back to insertion order
                    const order = (v.port_order !== undefined && v.port_order !== null) ? v.port_order : portOrderMap.size;
                    portOrderMap.set(key, { order: order, port: pname, leg: leg });
                } else {
                    // If this visit has a lower port_order, use it (ensures correct minimum)
                    const existing = portOrderMap.get(key);
                    if (v.port_order !== undefined && v.port_order !== null && v.port_order < existing.order) {
                        existing.order = v.port_order;
                    }
                }
            });
        });

        // Sort by port_order to match Excel's original row order
        const uniquePortEntries = Array.from(portOrderMap.values()).sort((a, b) => a.order - b.order);

        // 3. Render Header (Two levels: Vessel name -> ETA|ETS)
        let headerHTML1 = `<tr><th rowspan="2" class="matrix-port-header" data-i18n="schedule_dynamic.port">${escHtml(tr('schedule_dynamic.port', 'Puerto'))}</th>`;
        let headerHTML2 = `<tr>`;

        filteredRoutes.forEach(ruta => {
            const voyageInfo = ruta.voyage_code ? `<div class="matrix-voyage-code">${escHtml(ruta.voyage_code)}</div>` : '';
            headerHTML1 += `<th colspan="2" class="matrix-vessel-header">${escHtml(ruta.nombre)}${voyageInfo}</th>`;
            headerHTML2 += `<th class="matrix-sub-header">ETA</th>`;
            headerHTML2 += `<th class="matrix-sub-header">ETS</th>`;
        });
        headerHTML1 += `</tr>`;
        headerHTML2 += `</tr>`;
        thead.innerHTML = headerHTML1 + headerHTML2;

        // 4. Render Body (One row per port+leg, in Excel order)
        let bodyHTML = '';
        uniquePortEntries.forEach(portInfo => {
            const isReturn = portInfo.leg === 'return';
            const isZarate = portInfo.port.toLowerCase().includes('zarate');
            const portDisplay = isReturn
                ? `${escHtml(portInfo.port)} <span class="port-return-indicator" data-i18n="schedule_dynamic.vuelta">↩ vuelta</span>`
                : escHtml(portInfo.port);

            let rowClasses = [];
            if (isReturn) rowClasses.push('row-return-leg');
            if (isZarate) rowClasses.push('row-zarate');
            const rowClassAttr = rowClasses.length ? ` class="${rowClasses.join(' ')}"` : '';

            bodyHTML += `<tr${rowClassAttr}><td style="font-weight:600;">${portDisplay}</td>`;

            filteredRoutes.forEach(ruta => {
                // Find visit matching both port name AND leg
                const visit = ruta.viajes.find(v => {
                    const pname = v.puerto || v.POL || v.puerto_origen || v.POD || v.puerto_destino;
                    const vleg = v.leg || 'outbound';
                    return pname === portInfo.port && vleg === portInfo.leg;
                });

                if (visit) {
                    const etaVal = visit.eta || visit.ETA || '-';
                    const etsVal = visit.ets || visit.ETD || visit.ETS || '-';

                    const etaDisp = visit.is_feeder ? '<span class="feeder-label" data-i18n="schedule_dynamic.feeder">feeder</span>' : escHtml(etaVal);
                    const etsDisp = (visit.is_feeder && visit.feeder_code) ? `<span class="feeder-code">${escHtml(visit.feeder_code)}</span>` : escHtml(etsVal);

                    bodyHTML += `<td style="text-align:center;">${etaDisp}</td>`;
                    bodyHTML += `<td style="text-align:center;">${etsDisp}</td>`;
                } else {
                    bodyHTML += `<td style="text-align:center; color:#cbd5e1;">-</td>`;
                    bodyHTML += `<td style="text-align:center; color:#cbd5e1;">-</td>`;
                }
            });
            bodyHTML += `</tr>`;
        });
        tbody.innerHTML = bodyHTML;

        renderVesselCards(filteredRoutes, uniquePortEntries);

        // Adjust sticky second header row position based on actual first row height
        requestAnimationFrame(() => {
            const firstHeaderRow = thead.querySelector('tr:first-child');
            if (firstHeaderRow) {
                const firstRowHeight = firstHeaderRow.offsetHeight;
                const secondRowCells = thead.querySelectorAll('tr:nth-child(2) th');
                secondRowCells.forEach(th => {
                    th.style.top = firstRowHeight + 'px';
                });
            }
        });
        if (typeof AmaI18n !== 'undefined') AmaI18n.updateDOM();
    }

    // ── Export: PDF via jsPDF & AutoTable ─────────────────────────────────────────
    if (btnExportPDF) {
        btnExportPDF.addEventListener('click', function () {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                showExportNotice(tr('schedule.pdf_lib_error', 'La librería PDF no se ha cargado correctamente.'));
                return;
            }

            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = `<svg class="svg-spin" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" style="vertical-align:sub;margin-right:.4rem;"><path d="M12 3a9 9 0 1 0 9 9" stroke-linecap="round"/></svg> ${typeof AmaI18n !== 'undefined' ? AmaI18n.t('exchange.generating_pdf') : 'Generando PDF...'}`;
            btn.disabled = true;

            const { jsPDF } = window.jspdf;

            // Landscape A4 (842 x 595 pt)
            const doc = new jsPDF('l', 'pt', 'a4');
            const PW = doc.internal.pageSize.width;
            const PH = doc.internal.pageSize.height;
            const M = 32;

            // Institutional brand palette
            const NAVY = [15, 57, 108], BLUE = [0, 105, 204], GOLD = [0, 105, 204];
            const NAVY2 = [0, 105, 204], INK = [51, 65, 85], MUTE = [120, 130, 145];
            const ZEBRA = [240, 245, 251], TINT = [234, 240, 248], GOLDTINT = [222, 234, 246];
            const RETTINT = [226, 232, 240], BORDER = [216, 222, 230];

            // Locale + labels
            const localeMap = { 'es': 'es-AR', 'en': 'en-US', 'it': 'it-IT', 'pt': 'pt-BR' };
            const currentLang = (typeof AmaI18n !== 'undefined') ? AmaI18n.getCurrentLang() : 'es';
            const regionalLocale = localeMap[currentLang] || 'es-AR';
            const weekStr = weekLabel ? weekLabel.innerText : 'actual';
            const weekLabelStr = typeof AmaI18n !== 'undefined' ? AmaI18n.t('schedule.pdf_week_label') : 'Semana Nro';
            const exportedStr = typeof AmaI18n !== 'undefined' ? AmaI18n.t('schedule.pdf_exported_label') : 'Exportado';
            const fullTitle = typeof AmaI18n !== 'undefined' ? AmaI18n.t('schedule.pdf_title') : 'VESSEL SCHEDULE - SOUTH AMERICA EXPRESS SERVICE';
            // Split "VESSEL SCHEDULE - SOUTH AMERICA EXPRESS SERVICE" → main title + service subtitle
            const titleParts = fullTitle.split(/\s[-–]\s/);
            const mainTitle = titleParts.length > 1 ? titleParts[0].trim() : 'VESSEL SCHEDULE';
            let serviceName = (scheduleData && scheduleData.service) ? scheduleData.service
                : (titleParts.length > 1 ? titleParts.slice(1).join(' - ').trim() : 'SOUTH AMERICA EXPRESS SERVICE');
            const formattedDate = new Date().toLocaleDateString(regionalLocale);

            // Load stacked logo (correct aspect, no distortion) + flag watermark (Rule #5)
            const logoImg = new Image();
            logoImg.src = '/public/assets/img/logo-mark.svg';
            const wmImg = new Image();
            wmImg.src = '/public/assets/img/pdf/watermark-flag.png';

            const generate = () => {
                const model = buildMatrixModel();
                if (!model.vessels.length) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return;
                }

                const header = () => {
                    // Stacked brand logo rendered at its true aspect ratio (no distortion)
                    try {
                        if (logoImg.complete && logoImg.naturalWidth !== 0) {
                            const lw = 92;
                            const lh = lw * (logoImg.naturalHeight / logoImg.naturalWidth);
                            doc.addImage(logoImg, 'PNG', M, 20, lw, lh);
                        }
                    } catch (e) { console.warn('logo', e); }

                    doc.setFont('helvetica', 'bold'); doc.setFontSize(15); doc.setTextColor(...NAVY);
                    doc.text(mainTitle, PW - M, 30, { align: 'right' });
                    doc.setFont('helvetica', 'bold'); doc.setFontSize(9.5); doc.setTextColor(...GOLD);
                    doc.text(serviceName.toUpperCase(), PW - M, 44, { align: 'right' });
                    doc.setFont('helvetica', 'normal'); doc.setFontSize(8); doc.setTextColor(...MUTE);
                    doc.text(`${weekLabelStr} ${weekStr}  ·  ${exportedStr} ${formattedDate}`, PW - M, 56, { align: 'right' });
                    doc.setDrawColor(...NAVY); doc.setLineWidth(1.4); doc.line(M, 66, PW - M, 66);
                    doc.setDrawColor(...GOLD); doc.setLineWidth(0.8); doc.line(M, 68.6, PW - M, 68.6);
                };

                const footer = () => {
                    const y = PH - 22;
                    doc.setDrawColor(...GOLD); doc.setLineWidth(0.8); doc.line(M, y, PW - M, y);
                    doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5); doc.setTextColor(...NAVY);
                    doc.text('ATLANTIC MARITIME AGENCY S.A.', M, y + 11);
                    doc.setFont('helvetica', 'normal'); doc.setTextColor(...MUTE);
                    doc.text('Av. Puerto Madero 450, Piso 8, CABA, Argentina  ·  www.atlantic-agency.example.com', PW - M, y + 11, { align: 'right' });
                };

                const watermark = () => {
                    try {
                        if (wmImg.complete && wmImg.naturalWidth !== 0) {
                            doc.saveGraphicsState();
                            doc.setGState(new doc.GState({ opacity: 0.04 }));
                            doc.addImage(wmImg, 'PNG', PW / 2 - 110, PH / 2 - 95, 220, 220);
                            doc.restoreGraphicsState();
                        }
                    } catch (e) { console.warn('watermark', e); }
                };

                // Build head (PORT + vessel colSpan2; then ETA/ETS) and body from the model
                const head = [
                    [{ content: 'PUERTO', rowSpan: 2, styles: { halign: 'left', valign: 'middle' } },
                        ...model.vessels.map(v => ({
                            content: v.nombre.toUpperCase() + (v.voyage_code ? '\n' + v.voyage_code : ''),
                            colSpan: 2, styles: { halign: 'center' }
                        }))],
                    [].concat(...model.vessels.map(() => [
                        { content: 'ETA', styles: { halign: 'center' } },
                        { content: 'ETS', styles: { halign: 'center' } }
                    ]))
                ];
                const body = model.ports.map(p => {
                    const row = [{ content: (p.isReturn ? '« ' : '') + p.name }];
                    p.cells.forEach(c => {
                        if (!c.present) { row.push({ content: '–' }); row.push({ content: '–' }); return; }
                        row.push({ content: c.isFeeder ? 'feeder' : c.eta });
                        row.push({ content: c.isFeeder ? (c.feederCode || '') : c.ets });
                    });
                    return row;
                });

                const ncols = model.vessels.length * 2;
                const portW = 132;
                const vw = (PW - 2 * M - portW) / ncols;

                doc.autoTable({
                    head: head, body: body, startY: 78, margin: { left: M, right: M, bottom: 34 },
                    theme: 'grid',
                    styles: { font: 'helvetica', fontSize: 7.6, cellPadding: 3.2, lineColor: BORDER, lineWidth: 0.4, textColor: INK, valign: 'middle' },
                    headStyles: { fillColor: NAVY, textColor: 255, fontStyle: 'bold', fontSize: 8, halign: 'center', lineColor: [255, 255, 255], lineWidth: 0.3 },
                    columnStyles: Object.assign(
                        { 0: { halign: 'left', cellWidth: portW, fontStyle: 'bold', textColor: NAVY, fillColor: TINT } },
                        ...Array.from({ length: ncols }, (_, i) => ({ [i + 1]: { cellWidth: vw, halign: 'center' } }))
                    ),
                    didParseCell: function (d) {
                        if (d.section === 'head' && d.row.index === 1) { d.cell.styles.fillColor = NAVY2; d.cell.styles.fontSize = 7.4; }
                        if (d.section === 'body') {
                            const p = model.ports[d.row.index];
                            if (!p) return;
                            if (d.column.index === 0) {
                                if (p.isZarate) { d.cell.styles.fillColor = GOLD; d.cell.styles.textColor = [255, 255, 255]; }
                                else if (p.isReturn) d.cell.styles.fillColor = RETTINT;
                            } else {
                                if (p.isZarate) d.cell.styles.fillColor = GOLDTINT;
                                else if (d.row.index % 2 === 1) d.cell.styles.fillColor = ZEBRA;
                                const raw = d.cell.raw && d.cell.raw.content;
                                if (raw === 'feeder' || raw === '–') { d.cell.styles.textColor = MUTE; d.cell.styles.fontStyle = 'italic'; }
                            }
                        }
                    },
                    didDrawPage: function () { watermark(); header(); footer(); }
                });

                // Legend / references
                let ly = doc.lastAutoTable.finalY + 14;
                if (ly > PH - 50) { doc.addPage(); ly = 90; }
                doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5); doc.setTextColor(...NAVY);
                doc.text('REFERENCIAS', M, ly);
                doc.setFont('helvetica', 'normal'); doc.setTextColor(...INK); doc.setFontSize(7.5);
                doc.setFillColor(...GOLD); doc.rect(M, ly + 6, 8, 8, 'F');
                doc.text('Zárate (puerto local)', M + 12, ly + 12.5);
                doc.text('«  tramo de regreso        ETA: arribo estimado        ETS: salida estimada        feeder: conexión por buque alimentador', M + 150, ly + 12.5);

                const selOpt = vesselSelect.options[vesselSelect.selectedIndex];
                const nameLabel = selOpt && selOpt.value !== '' ? selOpt.text : 'MATRIX';
                doc.save(`AMA_Schedule_${nameLabel.replace(/[\s/()\-]/g, '_')}.pdf`);

                // Restore button
                btn.innerHTML = originalText;
                btn.disabled = false;
            };

            // Wait for both logo and watermark to settle before generating
            let pending = 2;
            const ready = () => { if (--pending <= 0) generate(); };
            [logoImg, wmImg].forEach((img) => {
                if (img.complete) { ready(); }
                else { img.onload = ready; img.onerror = ready; }
            });
        });
    }

    // ── Export: XLSX via SheetJS ──────────────────────────────────────────────────
    if (btnExportXLSX) {
        btnExportXLSX.addEventListener('click', () => {
            if (!window.XLSX) {
                showExportNotice(tr('schedule.xlsx_lib_error', 'La librería XLSX no se ha cargado correctamente.'));
                return;
            }

            const table = document.getElementById('schedule-table');

            // Localised labels
            const localeMap = { 'es': 'es-AR', 'en': 'en-US', 'it': 'it-IT', 'pt': 'pt-BR' };
            const currentLang = (typeof AmaI18n !== 'undefined') ? AmaI18n.getCurrentLang() : 'es';
            const regionalLocale = localeMap[currentLang] || 'es-AR';
            const subtitle = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('schedule.pdf_title') : 'VESSEL SCHEDULE - SOUTH AMERICA EXPRESS SERVICE';
            const weekLabelStr = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('schedule.pdf_week_label') : 'Week Number';
            const exportedStr = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('schedule.pdf_exported_label') : 'Exported';
            const weekStr = weekLabel ? weekLabel.innerText : '';
            const metaLine = `${weekLabelStr}: ${weekStr}  |  ${exportedStr}: ${new Date().toLocaleString(regionalLocale)}`;

            // Institutional header rows above the table data
            const headerRows = [
                ['ATLANTIC MARITIME AGENCY S.A.'],
                [subtitle],
                [metaLine],
                []
            ];
            const ws = XLSX.utils.aoa_to_sheet(headerRows);
            XLSX.utils.sheet_add_dom(ws, table, { origin: 'A5', raw: true });

            // Merge title rows across the table width + set column widths
            const range = XLSX.utils.decode_range(ws['!ref']);
            const ncols = range.e.c + 1;
            const merges = ws['!merges'] || (ws['!merges'] = []);
            for (let r = 0; r < 3; r++) merges.push({ s: { r, c: 0 }, e: { r, c: ncols - 1 } });
            ws['!cols'] = Array.from({ length: ncols }, (_, i) => ({ wch: i === 0 ? 26 : 14 }));

            const selOpt = vesselSelect.options[vesselSelect.selectedIndex];
            const nameLabel = selOpt && selOpt.value !== '' ? selOpt.text : 'MATRIX';
            const filename = `AMA_Schedule_${nameLabel.replace(/[\s/()\-]/g, '_')}.xlsx`;

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Schedule');
            XLSX.writeFile(wb, filename);
        });
    }

    // ── Utility ───────────────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});
