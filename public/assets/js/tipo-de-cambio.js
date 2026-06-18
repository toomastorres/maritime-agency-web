/**
 * AMA — tipo-de-cambio.js
 * Lógica de la página de tipo de cambio: cotizaciones, horario comercial.
 */

document.addEventListener('DOMContentLoaded', () => {
    const loading = document.getElementById('tc-loading');
    const content = document.getElementById('tc-content');
    const alertCont = document.getElementById('tc-alert-container');

    // ── Mensajes con estilo (el proyecto no carga FontAwesome → iconos SVG inline) ──
    const trc = (key, fallback) => (typeof AMAI18n !== 'undefined') ? AMAI18n.t(key) : fallback;
    const TC_SVG_ERROR = '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';
    const TC_SVG_CLOCK = '<svg viewBox="0 0 24 24" width="40" height="40" fill="currentColor" aria-hidden="true" style="display:block;margin:0 auto .75rem;"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>';

    // Error con estilo dentro del contenedor de carga (nunca expone detalles técnicos)
    function showTcError(msg) {
        if (!loading) return;
        loading.className = 'alerta-error text-center alerta-con-icono';
        loading.style.display = '';
        loading.innerHTML = `<span class="alerta-icono">${TC_SVG_ERROR}</span><span>${msg}</span>`;
    }

    // For testing purposes: Set to true to bypass time limitations locally
    const isTestMode = false;

    // Time gating check
    function isWithinBusinessHours() {
        if (isTestMode) return true;

        const now = new Date();
        const day = now.getDay(); // 0 is Sunday, 6 is Saturday
        const hour = now.getHours();

        // Weekday between 9:00 and 16:59
        return (day >= 1 && day <= 5 && hour >= 9 && hour < 17);
    }

    // Date validation check
    function isDateValid(dateStr) {
        if (isTestMode) return true;

        const today = new Date().toISOString().split('T')[0];
        return dateStr === today; // format: YYYY-MM-DD
    }

    // Fetch real exchange rate data from Caja API
    fetch('/api/tipo_cambio.php')
        .then(res => res.json())
        .then(result => {
            // Error reportado por la API (p. ej. fallo de base de datos): mensaje con estilo, nunca crudo
            if (result.status === 'error') {
                showTcError(trc('exchange.db_error', 'Error al conectar con la base de datos.'));
                return;
            }

            if (loading) loading.style.display = 'none';

            // Extract data from API response wrapper
            const data = (result.status === 'ok') ? result.data : {};

            // Derive fecha_cotizacion from ultima_actualizacion
            if (data.ultima_actualizacion && !data.fecha_cotizacion) {
                data.fecha_cotizacion = data.ultima_actualizacion.split(' ')[0];
            }

            if (!isWithinBusinessHours()) {
                alertCont.innerHTML = `
                    <div class="gated-overlay">
                        <div class="alerta-error text-center" style="font-size: 1.2rem; padding: 2.5rem; border:none; box-shadow: var(--AMA-shadow-lg);">
                            ${TC_SVG_CLOCK}
                            <span data-i18n="exchange.out_of_hours" style="color: var(--AMA-text);">Fuera de horario de atención (09:00 a 17:00hs)</span>
                        </div>
                    </div>
                `;
                if (typeof AMAI18n !== 'undefined') AMAI18n.updateDOM();
                return; // Stop execution
            }

            if (!isDateValid(data.fecha_cotizacion)) {
                const currentLang = (typeof AMAI18n !== 'undefined') ? AMAI18n.getCurrentLang() : 'es';
                const localeMap = { 'es': 'es-AR', 'en': 'en-US', 'it': 'it-IT', 'pt': 'pt-BR' };
                const regionalLocale = localeMap[currentLang] || 'es-AR';
                const hoy = new Date();
                const fechaHoy = hoy.toLocaleDateString(regionalLocale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                const horaActual = hoy.toLocaleTimeString(regionalLocale, { hour: '2-digit', minute: '2-digit' });

                alertCont.innerHTML = `
                    <div class="tc-no-data">
                        <div class="tc-no-data__icon">
                            <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                        </div>
                        <h3 class="tc-no-data__title" data-i18n="exchange.no_data_title">Cotización pendiente</h3>
                        <p class="tc-no-data__desc">
                            <span data-i18n="exchange.no_data_today">La cotización del día aún no fue cargada por el departamento de Caja.</span>
                        </p>
                        <div class="tc-no-data__date">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                            <span>${fechaHoy} — ${horaActual} hs</span>
                        </div>
                        <div class="tc-no-data__actions">
                            <a href="contacto" class="tc-no-data__btn">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                <span data-i18n="exchange.btn_contact">Contactar a Caja</span>
                            </a>
                            <button class="tc-no-data__btn tc-no-data__btn--outline" onclick="location.reload()">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                                <span data-i18n="exchange.btn_retry">Reintentar</span>
                            </button>
                        </div>
                        <p class="tc-no-data__hint" data-i18n="exchange.no_data_hint">El tipo de cambio se actualiza habitualmente entre las 09:00 y las 10:00 hs.</p>
                    </div>
                `;
                if (typeof AMAI18n !== 'undefined') AMAI18n.updateDOM();
                return;
            }

            // Render Data
            document.getElementById('val-usd').innerText = '$' + parseFloat(data.dolar_valor).toFixed(2);
            document.getElementById('val-eur').innerText = '€' + parseFloat(data.euro_valor).toFixed(2);
            document.getElementById('bank-data').textContent = data.datos_bancarios;
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

            const currentLang = (typeof AMAI18n !== 'undefined') ? AMAI18n.getCurrentLang() : 'es';
            const localeMap = { 'es': 'es-AR', 'en': 'en-US', 'it': 'it-IT', 'pt': 'pt-BR' };
            const regionalLocale = localeMap[currentLang] || 'es-AR';
            const dateDisplay = new Date().toLocaleDateString(regionalLocale);
            document.querySelectorAll('.tc-date-display').forEach(el => el.innerText = dateDisplay);

            content.style.display = 'block';

            // PDF Generation Setup
            setupPdfExport(data);
        })
        .catch(() => {
            showTcError(trc('exchange.db_error', 'Error al conectar con la base de datos.'));
        });

    function setupPdfExport(data) {
        document.getElementById('btn-descargar-pdf').addEventListener('click', function () {
            const btn = this;
            const generatingLabel = (typeof AMAI18n !== 'undefined') ? AMAI18n.t('exchange.generating_pdf') : 'Generando PDF...';
            btn.innerHTML = `<svg class="svg-spin" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" style="vertical-align:sub;margin-right:.4rem;"><path d="M12 3a9 9 0 1 0 9 9" stroke-linecap="round"/></svg> ${generatingLabel}`;
            btn.disabled = true;

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            const PW = doc.internal.pageSize.width;
            const PH = doc.internal.pageSize.height;
            const M = 42;

            // Institutional brand palette
            const NAVY = [0, 26, 61], BLUE = [0, 66, 122], GOLD = [197, 160, 89];
            const INK = [51, 65, 85], MUTE = [120, 130, 145], BORDER = [216, 222, 230];

            const currentLang = (typeof AMAI18n !== 'undefined') ? AMAI18n.getCurrentLang() : 'es';
            const localeMap = { 'es': 'es-AR', 'en': 'en-US', 'it': 'it-IT', 'pt': 'pt-BR' };
            const regionalLocale = localeMap[currentLang] || 'es-AR';

            // Load stacked logo (correct aspect, no distortion) + flag watermark (Rule #5)
            const logoImg = new Image();
            logoImg.src = '/public/assets/img/GGroupFlat-Blu.png';
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
                const pdfTitle = (typeof AMAI18n !== 'undefined') ? AMAI18n.t('exchange.pdf_title') : 'COTIZACIÓN OFICIAL DE TIPO DE CAMBIO';
                doc.setFont('helvetica', 'bold'); doc.setFontSize(16); doc.setTextColor(...NAVY);
                doc.text(pdfTitle, PW / 2, 128, { align: 'center' });
                doc.setFont('helvetica', 'normal'); doc.setFontSize(9.5); doc.setTextColor(...MUTE);
                doc.text('Tasas vigentes declaradas por la agencia · ' + (data.ultima_actualizacion || fechaEmision), PW / 2, 144, { align: 'center' });

                // Two value cards (USD / EUR)
                const cardY = 168, cardH = 120, gap = 18, cardW = (PW - 2 * M - gap) / 2;
                const usdLabel = ((typeof AMAI18n !== 'undefined') ? AMAI18n.t('exchange.usd') : 'Dólar (USD)').toUpperCase();
                const eurLabel = ((typeof AMAI18n !== 'undefined') ? AMAI18n.t('exchange.eur') : 'Euro (EUR)').toUpperCase();
                const card = (x, sym, label, value) => {
                    doc.setFillColor(248, 250, 252); doc.setDrawColor(...BORDER); doc.setLineWidth(0.8);
                    doc.roundedRect(x, cardY, cardW, cardH, 8, 8, 'FD');
                    doc.setFillColor(...GOLD); doc.roundedRect(x, cardY, cardW, 6, 8, 8, 'F'); doc.rect(x, cardY + 3, cardW, 3, 'F');
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
                const disclaimer1 = (typeof AMAI18n !== 'undefined') ? AMAI18n.t('exchange.pdf_disclaimer_1') : 'Este comprobante certifica la tasa de cambio vigente declarada por la agencia para la fecha de emisión,';
                const disclaimer2 = (typeof AMAI18n !== 'undefined') ? AMAI18n.t('exchange.pdf_disclaimer_2') : 'y es válido para la liquidación de facturas y operaciones comerciales con AMA Agencies Argentina.';
                doc.setFillColor(240, 245, 251); doc.setDrawColor(...BORDER); doc.roundedRect(M, vy, PW - 2 * M, 64, 6, 6, 'FD');
                doc.setFont('helvetica', 'bold'); doc.setFontSize(9); doc.setTextColor(...NAVY);
                doc.text('VALIDEZ DEL COMPROBANTE', M + 16, vy + 18);
                doc.setFont('helvetica', 'normal'); doc.setFontSize(9); doc.setTextColor(...INK);
                doc.text(disclaimer1, M + 16, vy + 35);
                doc.text(disclaimer2, M + 16, vy + 49);

                // Footer
                const fy = PH - 40;
                const pdfLegalFooter = (typeof AMAI18n !== 'undefined') ? AMAI18n.t('exchange.pdf_legal_footer') : 'Documento legal generado automáticamente por la plataforma ama-demo.example';
                doc.setDrawColor(...GOLD); doc.setLineWidth(0.8); doc.line(M, fy, PW - M, fy);
                doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5); doc.setTextColor(...NAVY);
                doc.text('AMA AGENCIES ARGENTINA S.A.', M, fy + 13);
                doc.setFont('helvetica', 'normal'); doc.setTextColor(...MUTE);
                doc.text('25 de Mayo 702, 1er Piso, CABA  ·  www.ama-demo.example', PW - M, fy + 13, { align: 'right' });
                doc.setFont('helvetica', 'italic'); doc.setFontSize(7); doc.setTextColor(...MUTE);
                doc.text(pdfLegalFooter, PW / 2, fy + 26, { align: 'center' });

                doc.save(`AMA_Cotizacion_${data.fecha_cotizacion}.pdf`);

                // Reset button
                const downloadLabel = (typeof AMAI18n !== 'undefined') ? AMAI18n.t('exchange.btn_download') : 'Descargar PDF';
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
