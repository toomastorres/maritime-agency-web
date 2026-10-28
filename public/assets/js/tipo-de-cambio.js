/**
 * AMA — tipo-de-cambio.js
 * Lógica de la página de tipo de cambio: cotizaciones, horario comercial.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Los <a> generados acá no pasan por PHP, así que la base sale del
    // propio documento en vez de asumir que el sitio vive en la raíz.
    const BASE = (document.querySelector('base') || {}).href
        || location.origin + '/';
    const loading = document.getElementById('tc-loading');
    const content = document.getElementById('tc-content');
    const alertCont = document.getElementById('tc-alert-container');

    // ── Mensajes con estilo (el proyecto no carga FontAwesome → iconos SVG inline) ──
    const trc = (key, fallback) => {
        const val = (typeof AmaI18n !== 'undefined') ? AmaI18n.t(key) : null;
        return (val && val !== key) ? val : fallback;
    };
    const TC_SVG_ERROR = '<svg class="olt-state__icon" viewBox="0 0 24 24" width="32" height="32" fill="currentColor" aria-hidden="true" style="margin-inline:auto"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';
    // El color y el margen los pone .olt-state__icon; antes iban inline y el
    // reloj salía en tinta plena, compitiendo con el título.
    const TC_SVG_CLOCK = '<svg class="olt-state__icon" viewBox="0 0 24 24" width="40" height="40" fill="currentColor" aria-hidden="true" style="margin-inline:auto"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>';

    // Nodo traducible: lleva su data-i18n encima además del texto ya
    // resuelto. Hace falta porque este HTML se arma en cuanto responde la
    // API, que puede llegar antes que el JSON de idioma — sin el atributo
    // el estado quedaba en castellano para siempre en un sitio en inglés,
    // y tampoco cambiaba al tocar el selector de idioma.
    const tnode = (tag, cls, key, fallback) =>
        `<${tag} class="${cls}" data-i18n="${key}">${trc(key, fallback)}</${tag}>`;

    // Estado (cerrado / todavía sin cargar / fallo real). Reemplaza el
    // bloque de cotización y deja intacto el resto de la página.
    function showState({ icon, title, desc, meta, actions, hint, isError }) {
        if (loading) loading.style.display = 'none';
        if (!alertCont) return;
        alertCont.innerHTML = `
            <div class="olt-state${isError ? ' olt-state--error' : ''}">
                ${icon || ''}
                ${title ? tnode('h2', 'olt-state__title', title[0], title[1]) : ''}
                ${desc ? tnode('p', 'olt-state__desc', desc[0], desc[1]) : ''}
                ${meta ? `<div class="olt-state__meta">${meta}</div>` : ''}
                ${actions ? `<div class="olt-state__actions">${actions}</div>` : ''}
                ${hint ? tnode('p', 'olt-state__hint', hint[0], hint[1]) : ''}
            </div>`;
        if (typeof AmaI18n !== 'undefined') AmaI18n.updateDOM();
    }

    function showTcError() {
        showState({
            icon: TC_SVG_ERROR,
            desc: ['exchange.db_error', 'Error al conectar con la base de datos.'],
            isError: true,
        });
    }

    // Los datos bancarios no tienen vigencia horaria: la API los manda en
    // todos los estados y se pintan siempre, haya cotización o no.
    function renderBankData(data) {
        const box = document.getElementById('bank-data');
        const empty = document.getElementById('bank-data-empty');
        const texto = (data && data.datos_bancarios) ? String(data.datos_bancarios).trim() : '';
        if (box) { box.textContent = texto; box.style.display = texto ? '' : 'none'; }
        if (empty) empty.style.display = texto ? 'none' : '';
    }

    function horarioTexto(data) {
        const h = (data && data.horario) || { desde: '09:00', hasta: '17:00' };
        return `${h.desde}–${h.hasta}`;
    }

    const SVG_CAL = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>';

    function fechaLarga(iso) {
        const currentLang = (typeof AmaI18n !== 'undefined') ? AmaI18n.getCurrentLang() : 'es';
        const localeMap = { es: 'es-AR', en: 'en-US', it: 'it-IT', pt: 'pt-BR' };
        const d = iso ? new Date(iso + 'T12:00:00') : new Date();
        return d.toLocaleDateString(localeMap[currentLang] || 'es-AR',
            { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }

    // Fetch real exchange rate data from Caja API
    fetch('/api/tipo_cambio.php')
        .then(res => res.json())
        .then(result => {
            const data = result.data || {};

            // El horario comercial lo resuelve la API con el reloj de la
            // agencia (America/Argentina/Buenos_Aires). Antes se recalculaba
            // acá con new Date().getHours(), o sea con el reloj del visitante:
            // desde Italia o Brasil la página se cerraba a la hora equivocada
            // y escondía una cotización que la API había devuelto válida.
            // Antes del corte por error: si la base no responde no hay datos
            // bancarios, y entonces el panel muestra a quién escribir en vez
            // de quedar como una caja blanca vacía.
            renderBankData(data);

            if (result.status === 'error') {
                showTcError();
                return;
            }

            if (result.status === 'cerrado') {
                showState({
                    icon: TC_SVG_CLOCK,
                    title: ['exchange.closed_title', 'Cotización fuera de vigencia'],
                    desc: ['exchange.closed_desc', 'La cotización se publica de lunes a viernes antes de las 10:00 y pierde vigencia a las 17:00. Las operaciones que cierres ahora se liquidan con la cotización del próximo día hábil.'],
                    meta: SVG_CAL + tnode('span', '', 'exchange.local_time', 'Hora en Buenos Aires')
                        + `<span>${data.hora_local || '--'} · ${horarioTexto(data)}</span>`,
                    actions: `<a href="${BASE}contacto" class="olt-btn olt-btn--outline" data-i18n="exchange.btn_contact">${trc('exchange.btn_contact', 'Contactar a Caja')}</a>`,
                    hint: ['exchange.closed_hint', 'Los datos bancarios de esta página siguen vigentes: podés preparar la transferencia ahora.'],
                });
                return;
            }

            if (result.status === 'no_disponible') {
                showState({
                    icon: TC_SVG_CLOCK,
                    title: ['exchange.no_data_title', 'Cotización pendiente'],
                    desc: ['exchange.no_data_today', 'La cotización del día aún no fue cargada por el departamento de Caja.'],
                    meta: `${SVG_CAL}<span>${fechaLarga(data.fecha_local)}${data.hora_local ? ' — ' + data.hora_local + ' hs' : ''}</span>`,
                    actions: `<button type="button" class="olt-btn olt-btn--primary" id="tc-retry" data-i18n="exchange.btn_retry">${trc('exchange.btn_retry', 'Reintentar')}</button>`
                           + `<a href="${BASE}contacto" class="olt-btn olt-btn--outline" data-i18n="exchange.btn_contact">${trc('exchange.btn_contact', 'Contactar a Caja')}</a>`,
                    hint: ['exchange.no_data_hint', 'El tipo de cambio se actualiza habitualmente entre las 09:00 y las 10:00 hs.'],
                });
                const retry = document.getElementById('tc-retry');
                if (retry) retry.addEventListener('click', () => location.reload());
                return;
            }

            if (loading) loading.style.display = 'none';

            // Derive fecha_cotizacion from ultima_actualizacion
            if (data.ultima_actualizacion && !data.fecha_cotizacion) {
                data.fecha_cotizacion = data.ultima_actualizacion.split(' ')[0];
            }

            // Render Data
            // Sin prefijo de moneda: la celda ya dice "ARS" y el número es
            // pesos por unidad. El "€1523.80" anterior se leía como 1523
            // euros, que es justo lo contrario de lo que informa la página.
            // Formato es-AR (1.412,50) para que coincida con la franja del Home.
            const fmtMonto = (v) => {
                const n = parseFloat(v);
                return isNaN(n) ? '—' : n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };
            document.getElementById('val-usd').innerText = fmtMonto(data.dolar_valor);
            document.getElementById('val-eur').innerText = fmtMonto(data.euro_valor);
            // Formatear timestamp a formato legible (dd/mm/YYYY HH:mm)
            const rawTs = data.ultima_actualizacion;
            let formattedTs = rawTs;
            try {
                const tsDate = new Date(rawTs.replace(' ', 'T'));
                if (!isNaN(tsDate.getTime())) {
                    const dd = String(tsDate.getDate()).padStart(2, '0');
                    const mm = String(tsDate.getMonth() + 1).padStart(2, '0');
                    const yyyy = tsDate.getFullYear();
                    const hh = String(tsDate.getHours()).padStart(2, '0');
                    const mi = String(tsDate.getMinutes()).padStart(2, '0');
                    formattedTs = `${dd}/${mm}/${yyyy} — ${hh}:${mi} hs`;
                }
            } catch(e) { /* fallback al valor crudo */ }
            document.getElementById('last-update').innerText = formattedTs;

            const currentLang = (typeof AmaI18n !== 'undefined') ? AmaI18n.getCurrentLang() : 'es';
            const localeMap = { 'es': 'es-AR', 'en': 'en-US', 'it': 'it-IT', 'pt': 'pt-BR' };
            const regionalLocale = localeMap[currentLang] || 'es-AR';
            const dateDisplay = new Date().toLocaleDateString(regionalLocale);
            document.querySelectorAll('.tc-date-display').forEach(el => el.innerText = dateDisplay);

            content.style.display = 'block';

            // PDF Generation Setup
            setupPdfExport(data);
        })
        .catch(() => {
            showTcError();
        });

    function setupPdfExport(data) {
        document.getElementById('btn-descargar-pdf').addEventListener('click', function () {
            const btn = this;
            const generatingLabel = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('exchange.generating_pdf') : 'Generando PDF...';
            btn.innerHTML = `<svg class="svg-spin" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" style="vertical-align:sub;margin-right:.4rem;"><path d="M12 3a9 9 0 1 0 9 9" stroke-linecap="round"/></svg> ${generatingLabel}`;
            btn.disabled = true;

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            const PW = doc.internal.pageSize.width;
            const PH = doc.internal.pageSize.height;
            const M = 42;

            // Institutional brand palette — Oltremare
            const NAVY = [15, 57, 108], BLUE = [0, 105, 204], GOLD = [0, 105, 204];
            const INK = [51, 65, 85], MUTE = [120, 130, 145], BORDER = [216, 222, 230];

            const currentLang = (typeof AmaI18n !== 'undefined') ? AmaI18n.getCurrentLang() : 'es';
            const localeMap = { 'es': 'es-AR', 'en': 'en-US', 'it': 'it-IT', 'pt': 'pt-BR' };
            const regionalLocale = localeMap[currentLang] || 'es-AR';

            // Load stacked logo (correct aspect, no distortion) + flag watermark (Rule #5)
            const logoImg = new Image();
            logoImg.src = '/public/assets/img/logo-mark.svg';
            const wmImg = new Image();
            wmImg.src = '/public/assets/img/pdf/watermark-flag.png';

            const generate = () => {
                const fechaEmision = new Date().toLocaleDateString(regionalLocale);
                // Comprobante number, stable per quotation date (COT-YYYY-MMDD)
                const fc = (data.fecha_cotizacion || new Date().toISOString().split('T')[0]).split('-');
                const compNum = `COT-${fc[0]}-${(fc[1] || '')}${(fc[2] || '')}`;

                // Subtle AMA flag watermark (institutional identity)
                try {
                    if (wmImg.complete && wmImg.naturalWidth !== 0) {
                        doc.saveGraphicsState();
                        doc.setGState(new doc.GState({ opacity: 0.04 }));
                        doc.addImage(wmImg, 'PNG', PW / 2 - 120, PH / 2 - 60, 240, 240);
                        doc.restoreGraphicsState();
                    }
                } catch (e) { console.warn('Could not draw watermark in PDF', e); }

                // Letterhead: stacked brand logo at its true aspect ratio (no distortion)
                try {
                    if (logoImg.complete && logoImg.naturalWidth !== 0) {
                        const lw = 150;
                        const lh = lw * (logoImg.naturalHeight / logoImg.naturalWidth);
                        doc.addImage(logoImg, 'PNG', M, 36, lw, lh);
                    }
                } catch (e) { console.warn('Could not draw logo in PDF', e); }

                doc.setFont('helvetica', 'bold'); doc.setFontSize(10); doc.setTextColor(...NAVY);
                doc.text('COMPROBANTE OFICIAL', PW - M, 48, { align: 'right' });
                doc.setFont('helvetica', 'normal'); doc.setFontSize(8); doc.setTextColor(...MUTE);
                doc.text('N° ' + compNum, PW - M, 61, { align: 'right' });
                doc.text('Emitido: ' + fechaEmision, PW - M, 72, { align: 'right' });
                doc.setDrawColor(...NAVY); doc.setLineWidth(1.4); doc.line(M, 92, PW - M, 92);
                doc.setDrawColor(...GOLD); doc.setLineWidth(0.8); doc.line(M, 95, PW - M, 95);

                // Title
                const pdfTitle = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('exchange.pdf_title') : 'COTIZACIÓN OFICIAL DE TIPO DE CAMBIO';
                doc.setFont('helvetica', 'bold'); doc.setFontSize(16); doc.setTextColor(...NAVY);
                doc.text(pdfTitle, PW / 2, 128, { align: 'center' });
                doc.setFont('helvetica', 'normal'); doc.setFontSize(9.5); doc.setTextColor(...MUTE);
                doc.text('Tasas vigentes declaradas por la agencia · ' + (data.ultima_actualizacion || fechaEmision), PW / 2, 144, { align: 'center' });

                // Two value cards (USD / EUR)
                const cardY = 168, cardH = 120, gap = 18, cardW = (PW - 2 * M - gap) / 2;
                const usdLabel = ((typeof AmaI18n !== 'undefined') ? AmaI18n.t('exchange.usd') : 'Dólar (USD)').toUpperCase();
                const eurLabel = ((typeof AmaI18n !== 'undefined') ? AmaI18n.t('exchange.eur') : 'Euro (EUR)').toUpperCase();
                const card = (x, sym, label, value) => {
                    doc.setFillColor(248, 250, 252); doc.setDrawColor(...BORDER); doc.setLineWidth(0.8);
                    doc.rect(x, cardY, cardW, cardH, 'FD');
                    doc.setFillColor(...GOLD); doc.rect(x, cardY, cardW, 6, 'F');
                    doc.setFont('helvetica', 'normal'); doc.setFontSize(10); doc.setTextColor(...MUTE);
                    doc.text(label, x + 22, cardY + 34);
                    doc.setFont('helvetica', 'bold'); doc.setFontSize(34); doc.setTextColor(...NAVY);
                    doc.text(sym, x + 22, cardY + 68);
                    doc.setFont('helvetica', 'bold'); doc.setFontSize(30); doc.setTextColor(...BLUE);
                    doc.text(value, x + cardW - 22, cardY + 70, { align: 'right' });
                    doc.setFont('helvetica', 'normal'); doc.setFontSize(8.5); doc.setTextColor(...MUTE);
                    doc.text('Pesos argentinos (ARS) por unidad', x + 22, cardY + 100);
                };
                card(M, '$', usdLabel, parseFloat(data.dolar_valor).toFixed(2));
                card(M + cardW + gap, '€', eurLabel, parseFloat(data.euro_valor).toFixed(2));

                // Validity box
                const vy = cardY + cardH + 26;
                const disclaimer1 = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('exchange.pdf_disclaimer_1') : 'Este comprobante certifica la tasa de cambio vigente declarada por la agencia para la fecha de emisión,';
                const disclaimer2 = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('exchange.pdf_disclaimer_2') : 'y es válido para la liquidación de facturas y operaciones comerciales con Atlantic Maritime Agency.';
                doc.setFillColor(240, 245, 251); doc.setDrawColor(...BORDER); doc.rect(M, vy, PW - 2 * M, 64, 'FD');
                doc.setFont('helvetica', 'bold'); doc.setFontSize(9); doc.setTextColor(...NAVY);
                doc.text('VALIDEZ DEL COMPROBANTE', M + 16, vy + 18);
                doc.setFont('helvetica', 'normal'); doc.setFontSize(9); doc.setTextColor(...INK);
                doc.text(disclaimer1, M + 16, vy + 35);
                doc.text(disclaimer2, M + 16, vy + 49);

                // Footer
                const fy = PH - 40;
                const pdfLegalFooter = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('exchange.pdf_legal_footer') : 'Documento legal generado automáticamente por la plataforma atlantic-agency.example.com';
                doc.setDrawColor(...GOLD); doc.setLineWidth(0.8); doc.line(M, fy, PW - M, fy);
                doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5); doc.setTextColor(...NAVY);
                doc.text('ATLANTIC MARITIME AGENCY S.A.', M, fy + 13);
                doc.setFont('helvetica', 'normal'); doc.setTextColor(...MUTE);
                doc.text('Av. Puerto Madero 450, Piso 8, CABA  ·  www.atlantic-agency.example.com', PW - M, fy + 13, { align: 'right' });
                doc.setFont('helvetica', 'italic'); doc.setFontSize(7); doc.setTextColor(...MUTE);
                doc.text(pdfLegalFooter, PW / 2, fy + 26, { align: 'center' });

                doc.save(`AMA_Cotizacion_${data.fecha_cotizacion}.pdf`);

                // Reset button
                const downloadLabel = (typeof AmaI18n !== 'undefined') ? AmaI18n.t('exchange.btn_download') : 'Descargar PDF';
                btn.innerHTML = `<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align: sub; margin-right: 0.5rem;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> <span data-i18n="exchange.btn_download">${downloadLabel}</span>`;
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
});
