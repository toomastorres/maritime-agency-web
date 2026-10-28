/**
 * AMA — mapa-red.js
 * Mapa interactivo de la red de servicios (D3 + world-atlas).
 * Módulo embebido por iframe en Servicios (P4); notifica su alto
 * real al padre vía postMessage para el auto-resize del iframe.
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // AmaI18n.t() devuelve la propia key cuando no encuentra traducción,
    // lo que también pasa mientras el JSON de idioma sigue cargando.
    const tr = (key, fallback) => {
        if (typeof AmaI18n === 'undefined') return fallback;
        const val = AmaI18n.t(key);
        return (val && val !== key) ? val : fallback;
    };

    const PORTS = [
        { n: 'Zárate', c: 'Argentina', r: 'Sudamérica', co: [-59.03, -34.09], main: true, dx: -13, dy: -2, a: 'end' },
        { n: 'Montevideo', c: 'Uruguay', r: 'Sudamérica', co: [-56.16, -34.90], dx: 12, dy: 20, a: 'start' },
        { n: 'Paranaguá', c: 'Brasil', r: 'Sudamérica', co: [-48.51, -25.52], dx: 13, dy: 38, a: 'start' },
        { n: 'Santos', c: 'Brasil', r: 'Sudamérica', co: [-46.31, -23.96], dx: 13, dy: 18, a: 'start' },
        { n: 'Rio de Janeiro', c: 'Brasil', r: 'Sudamérica', co: [-43.17, -22.91], dx: 13, dy: 2, a: 'start' },
        { n: 'Vitória', c: 'Brasil', r: 'Sudamérica', co: [-40.29, -20.32], dx: 13, dy: -14, a: 'start' },
        { n: 'Lagos', c: 'Nigeria', r: 'África Occidental', co: [3.39, 6.45], dx: 13, dy: -10, a: 'start' },
        { n: 'Cotonou', c: 'Benín', r: 'África Occidental', co: [2.42, 6.37], dx: 13, dy: 6, a: 'start' },
        { n: 'Tema', c: 'Ghana', r: 'África Occidental', co: [-0.02, 5.67], dx: 13, dy: 22, a: 'start' },
        { n: 'Abidjan', c: 'Costa de Marfil', r: 'África Occidental', co: [-4.01, 5.31], dx: -11, dy: 14, a: 'end' },
        { n: 'Dakar', c: 'Senegal', r: 'África Occidental', co: [-17.44, 14.69], dx: -11, dy: 3, a: 'end' },
        { n: 'Tenerife', c: 'España', r: 'Europa', co: [-16.25, 28.47], dx: -11, dy: 3, a: 'end' },
        { n: 'Tilbury', c: 'Reino Unido', r: 'Europa', co: [0.36, 51.46], dx: -11, dy: 8, a: 'end' },
        { n: 'Amberes', c: 'Bélgica', r: 'Europa', co: [4.40, 51.22], dx: 14, dy: 13, a: 'start' },
        { n: 'Rotterdam', c: 'Países Bajos', r: 'Europa', co: [4.48, 51.92], dx: 14, dy: -3, a: 'start' },
        { n: 'Hamburgo', c: 'Alemania', r: 'Europa', co: [9.99, 53.55], dx: 14, dy: -19, a: 'start' },
        { n: 'Taicang', c: 'China', r: 'Lejano Oriente', co: [121.10, 31.46], dx: -11, dy: -5, a: 'end' },
        { n: 'Singapur', c: 'Singapur', r: 'Lejano Oriente', co: [103.82, 1.35], dx: -11, dy: 3, a: 'end' },
        { n: 'Ningbo', c: 'China', r: 'Lejano Oriente', co: [121.55, 29.87], dx: -11, dy: 9, a: 'end' }
    ];
    const P = {};
    PORTS.forEach(p => P[p.n] = p);

    // Los textos editoriales del servicio (nombre, nota, frecuencia, tipo de
    // carga) llevan clave de i18n; los nombres de puerto no se traducen a
    // propósito: son los mismos que muestra la tabla del schedule y cambiarlos
    // por idioma haría imposible cruzar las dos pantallas.
    const SERVICES = [
        { id: 'all', name: 'Los dos servicios', k: 'both', meta: '3 regiones', metaK: 'meta_regions',
          legs: [], rot: 'Zárate', rotN: 'Los dos servicios regulares terminan en Terminal Zárate.',
          freq: 'Semanal', freqK: 'freq_weekly', freqN: 'South America Express semanal; China — Sudamérica mensual.',
          cargo: 'Buques multipropósito', cargoK: 'cargo_multipurpose_vessels',
          note: 'Ambos servicios regulares juntos. Elegí uno para aislar su rotación.' },
        { id: 'eur', name: 'Europa del Norte — Sudamérica', k: 'eur', meta: 'Multipropósito', metaK: 'meta_multipurpose',
          legs: [['Hamburgo', 'Rotterdam'], ['Rotterdam', 'Amberes'], ['Amberes', 'Tilbury'], ['Tilbury', 'Tenerife'], ['Tenerife', 'Vitória'], ['Vitória', 'Rio de Janeiro'], ['Rio de Janeiro', 'Santos'], ['Santos', 'Paranaguá'], ['Paranaguá', 'Montevideo'], ['Montevideo', 'Zárate']],
          rot: 'Hamburgo → Zárate', rotN: 'Hamburgo, Rotterdam, Amberes, Tilbury, Tenerife, Vitória, Rio, Santos, Paranaguá, Montevideo, Zárate.',
          freq: 'Semanal', freqK: 'freq_weekly', freqN: 'Tránsito estimado de 22 a 26 días a Zárate.',
          cargo: 'Multipropósito', cargoK: 'cargo_multipurpose',
          note: 'Servicio principal de la agencia: la carga europea entra por Zárate.' },
        { id: 'asia', name: 'China — Sudamérica', k: 'asia', meta: 'Multipropósito', metaK: 'meta_multipurpose',
          legs: [['Taicang', 'Ningbo'], ['Ningbo', 'Singapur'], ['Singapur', 'Paranaguá'], ['Paranaguá', 'Santos'], ['Santos', 'Montevideo'], ['Montevideo', 'Zárate']],
          rot: 'Taicang → Zárate', rotN: 'Taicang, Ningbo, Singapur, Paranaguá, Santos, Montevideo, Zárate.',
          freq: 'Mensual', freqK: 'freq_monthly', freqN: 'Servicio directo: no hace escala en Europa.',
          cargo: 'Contenedores, rodante y proyecto', cargoK: 'cargo_asia',
          note: 'La carga china llega a Zárate por la ruta del sur, sin transbordo europeo.' }
    ];

    const svcName = s => tr(`map.svc_${s.k}_name`, s.name);
    const svcNote = s => tr(`map.svc_${s.k}_note`, s.note);
    const svcRotN = s => tr(`map.svc_${s.k}_rot_note`, s.rotN);
    const svcFreqN = s => tr(`map.svc_${s.k}_freq_note`, s.freqN);

    const REGIONS = [
        { es: 'Sudamérica',        k: 'south_america' },
        { es: 'Europa',            k: 'europe' },
        { es: 'Lejano Oriente',    k: 'far_east' },
        { es: 'África Occidental', k: 'west_africa', transship: true }
    ];

    const ALL_LEGS = SERVICES.filter(s => s.id !== 'all').flatMap(s => s.legs);

    // Panel de datos del servicio: son datos locales, no dependen de D3.
    function fillDetail(svc) {
        const act = activePorts(svc);
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('port-count', act.size);
        set('svc-note', svcNote(svc));
        set('d-rot', svc.rot);
        set('d-rot-n', svcRotN(svc));
        set('d-freq', tr(`map.${svc.freqK}`, svc.freq));
        set('d-freq-n', svcFreqN(svc));
        set('d-cargo', tr(`map.${svc.cargoK}`, svc.cargo));
        document.querySelectorAll('.svc').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.id === svc.id)));
    }

    function buildServiceList(onSelect) {
        const list = document.getElementById('svc-list');
        if (!list) return;
        list.innerHTML = '';
        SERVICES.forEach(s => {
            const b = document.createElement('button');
            b.className = 'svc'; b.dataset.id = s.id; b.type = 'button';
            b.innerHTML = `<span class="nm">${svcName(s)}</span><span class="mt">${activePorts(s).size} ${tr('map.ports_word', 'puertos')} · ${tr(`map.${s.metaK}`, s.meta)}</span>`;
            b.addEventListener('click', () => onSelect(s));
            list.appendChild(b);
        });
    }

    function buildRegionGrid(onHover) {
        const grid = document.getElementById('region-grid');
        if (!grid) return;
        grid.innerHTML = '';
        REGIONS.forEach(reg => {
            const r = reg.es;
            const ps = PORTS.filter(p => p.r === r);
            const sec = document.createElement('section');
            const label = tr(`map.region_${reg.k}`, r);
            const tag = reg.transship ? ` · ${tr('map.transship_word', 'transbordo')}` : '';
            sec.innerHTML = `<h3>${label} · ${ps.length}${tag}</h3><ul>` + ps.map(p =>
                `<li class="${p.main ? 'main' : ''}" data-port="${p.n}"><i></i><span><span class="pn">${p.n}</span> <span class="pc">${p.c}</span></span></li>`).join('') + '</ul>';
            grid.appendChild(sec);
        });
        if (onHover) {
            grid.querySelectorAll('li').forEach(li => {
                li.addEventListener('mouseenter', () => onHover(P[li.dataset.port]));
                li.addEventListener('mouseleave', () => { const t = document.getElementById('tip'); if (t) t.style.opacity = 0; });
            });
        }
    }

    // ── Puente con el padre (Servicios) ──────────────────────────────
    // Va antes del corte por D3: si el CDN del mapa no carga, el módulo hacía
    // return acá mismo y nunca llegaba a registrar el auto-resize, así que el
    // iframe se quedaba con su alto por defecto y recortaba el contenido.
    function postHeight() {
        try { parent.postMessage({ __gmap: 1, h: document.documentElement.scrollHeight }, '*'); } catch (e) {}
    }
    window.addEventListener('load', postHeight);
    window.addEventListener('resize', postHeight);
    if (window.ResizeObserver) new ResizeObserver(postHeight).observe(document.body);
    setTimeout(postHeight, 400);
    setTimeout(postHeight, 1500);

    // El iframe no comparte el selector de idioma del header: el padre le
    // avisa cuando el visitante cambia de idioma.
    let redraw = null;

    // init() lo dispara main.js, que este módulo no carga: sin esta llamada
    // AmaI18n nunca leía el JSON y el mapa se quedaba en castellano
    // aunque la página padre estuviera en otro idioma. Es asíncrono, así
    // que el primer pintado sale con los textos del HTML y se corrige al
    // resolver; para el visitante en castellano no cambia nada.
    if (typeof AmaI18n !== 'undefined') {
        Promise.resolve(AmaI18n.init()).then(() => {
            document.documentElement.lang = AmaI18n.getCurrentLang();
            if (redraw) redraw();
            postHeight();
        }).catch(() => { /* sin traducciones se queda el texto del HTML */ });
    }
    window.addEventListener('message', function (e) {
        const d = e.data;
        if (!d || !d.__gmapLang || typeof AmaI18n === 'undefined') return;
        if (d.__gmapLang === AmaI18n.getCurrentLang()) return;
        Promise.resolve(AmaI18n.setLanguage(d.__gmapLang)).then(() => {
            document.documentElement.lang = d.__gmapLang;
            if (redraw) redraw();
            postHeight();
        });
    });
    try { parent.postMessage({ __gmap: 1, ready: 1 }, '*'); } catch (e) {}

    // Sin D3/topojson (CDN bloqueado, red corporativa, offline) el dibujo del
    // mapa no se puede hacer, pero todo lo demás sí: servicios, rotación,
    // frecuencia y listado de puertos son datos locales. Antes se hacía
    // `return` en seco y quedaba un bloque de ~1300px vacío con guiones.
    if (typeof d3 === 'undefined' || typeof topojson === 'undefined') {
        let fallbackCurrent = 'eur';
        const paintFallback = () => {
            buildServiceList(s => { fallbackCurrent = s.id; fillDetail(s); });
            buildRegionGrid(null);
            fillDetail(SERVICES.find(s => s.id === fallbackCurrent));
        };
        redraw = paintFallback;
        paintFallback();

        const stage = document.querySelector('.stage');
        if (stage) {
            stage.classList.add('stage--nomap');
            stage.innerHTML = `<p class="stage__fallback" data-i18n="map.no_map">${tr('map.no_map', 'No pudimos cargar el mapa interactivo. El detalle de cada servicio y el listado completo de puertos siguen disponibles más abajo.')}</p>`;
        }
        return;
    }

    let current = 'eur';
    const W = 980, H = 560;
    const svg = d3.select('#map').attr('viewBox', `0 0 ${W} ${H}`).attr('width', W).attr('height', H);
    const projection = d3.geoNaturalEarth1().rotate([-25, 0]);
    projection.fitExtent([[14, 18], [W - 14, H - 18]], { type: 'Sphere' });
    const path = d3.geoPath(projection);
    const gLand = svg.append('g'), gGraticule = svg.append('g'), gNet = svg.append('g'), gRoute = svg.append('g'), gPorts = svg.append('g');
    const tip = document.getElementById('tip');

    function lineFeature(a, b) { return { type: 'LineString', coordinates: [P[a].co, P[b].co] }; }

    function drawLegs(g, legs, cls) {
        g.selectAll('path').data(legs).join('path')
            .attr('d', d => path(lineFeature(d[0], d[1])))
            .attr('fill', 'none')
            .attr('stroke', cls === 'net' ? 'rgba(128,128,131,.55)' : '#0F396C')
            .attr('stroke-width', cls === 'net' ? 1 : 2)
            .attr('stroke-linecap', 'round');
    }

    function activePorts(svc) {
        if (svc.id === 'all') { const a = new Set(); ALL_LEGS.forEach(l => { a.add(l[0]); a.add(l[1]); }); return a; }
        const s = new Set(); svc.legs.forEach(l => { s.add(l[0]); s.add(l[1]); }); return s;
    }

    function render() {
        const svc = SERVICES.find(s => s.id === current);
        const act = activePorts(svc);
        drawLegs(gNet, svc.id === 'all' ? [] : ALL_LEGS.filter(l => !svc.legs.includes(l)), 'net');
        drawLegs(gRoute, svc.id === 'all' ? ALL_LEGS : svc.legs, 'route');

        const marks = gPorts.selectAll('g.port').data(PORTS, d => d.n).join(
            enter => {
                const g = enter.append('g').attr('class', 'port').style('cursor', 'pointer')
                    .attr('transform', d => { const q = projection(d.co); return `translate(${q[0]},${q[1]})`; });
                g.append('rect').attr('class', 'hit').attr('x', -11).attr('y', -11).attr('width', 22).attr('height', 22).attr('fill', 'transparent');
                g.append('rect').attr('class', 'dot');
                g.append('text').attr('class', 'lbl').attr('font-family', "'DM Sans',sans-serif").attr('font-size', 13).attr('font-weight', 500)
                    .attr('paint-order', 'stroke').attr('stroke', '#fff').attr('stroke-width', 3.5).attr('stroke-linejoin', 'round');
                return g;
            }
        );
        marks.select('rect.dot')
            .attr('x', d => d.main ? -5 : -3.5).attr('y', d => d.main ? -5 : -3.5)
            .attr('width', d => d.main ? 10 : 7).attr('height', d => d.main ? 10 : 7)
            .attr('fill', d => act.has(d.n) ? (d.main ? '#0069CC' : '#0F396C') : 'rgba(128,128,131,.5)');
        marks.select('text.lbl')
            .attr('x', d => d.dx)
            .attr('y', d => d.dy)
            .attr('text-anchor', d => d.a)
            .attr('fill', d => act.has(d.n) ? '#1A1A1C' : 'rgba(128,128,131,.75)')
            .attr('font-weight', d => d.main ? 600 : 400)
            .text(d => (d.main || act.has(d.n)) ? d.n : '');
        marks
            .on('mouseenter', function (e, d) { showTip(d); })
            .on('mousemove', function (e, d) { showTip(d); })
            .on('mouseleave', () => { tip.style.opacity = 0; });

        fillDetail(svc);
    }

    function showTip(d) {
        const q = projection(d.co);
        const stage = document.querySelector('.stage').getBoundingClientRect();
        const scale = stage.width / W;
        tip.querySelector('.t').textContent = d.n;
        tip.querySelector('.s').textContent = d.c + ' · ' + d.r + (d.main ? ' · puerto base' : '');
        tip.style.left = (q[0] * scale) + 'px';
        tip.style.top = (q[1] * scale - 14) + 'px';
        tip.style.opacity = 1;
    }

    const paintMap = () => {
        buildServiceList(s => { current = s.id; render(); });
        buildRegionGrid(showTip);
        render();
    };
    redraw = paintMap;
    buildServiceList(s => { current = s.id; render(); });
    buildRegionGrid(showTip);

    d3.json('https://cdn.jsdelivr.net/npm/world-atlas@2.0.2/countries-110m.json').then(world => {
        const countries = topojson.feature(world, world.objects.countries);
        gGraticule.append('path').datum(d3.geoGraticule10()).attr('d', path)
            .attr('fill', 'none').attr('stroke', 'rgba(128,128,131,.16)').attr('stroke-width', .5);
        gLand.selectAll('path').data(countries.features).join('path')
            .attr('d', path).attr('fill', '#F0F0F0').attr('stroke', 'rgba(128,128,131,.45)').attr('stroke-width', .6);
        gGraticule.raise(); gNet.raise(); gRoute.raise(); gPorts.raise();
        render();
    }).catch(() => { render(); });

});
