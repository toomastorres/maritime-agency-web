/**
 * AMA — servicios.js
 * Lógica de la página de servicios: mapa mundial D3.js, rutas y puertos.
 */

// ── PORT & ROUTE DATA (mirrors mockup routes-map.tsx) ─────────────
const AMA_PORTS = [
  { name: 'Zárate',       country: 'Argentina',       coords: [-59.03, -34.09], isMain: true  },
  { name: 'Montevideo',   country: 'Uruguay',          coords: [-56.16, -34.90], isMain: false },
  { name: 'Paranaguá',    country: 'Brasil',           coords: [-48.51, -25.52], isMain: false },
  { name: 'Santos',       country: 'Brasil',           coords: [-46.31, -23.96], isMain: false },
  { name: 'Rio de Janeiro', country: 'Brasil',         coords: [-43.17, -22.91], isMain: false },
  { name: 'Vitória',      country: 'Brasil',           coords: [-40.29, -20.32], isMain: false },
  { name: 'Lagos',        country: 'Nigeria',          coords: [ 3.39,   6.45],  isMain: false },
  { name: 'Cotonou',      country: 'Benín',            coords: [ 2.42,   6.37],  isMain: false },
  { name: 'Tema',         country: 'Ghana',            coords: [-0.02,   5.67],  isMain: false },
  { name: 'Abidjan',      country: 'Costa de Marfil',  coords: [-4.01,   5.31],  isMain: false },
  { name: 'Dakar',        country: 'Senegal',          coords: [-17.44, 14.69],  isMain: false },
  { name: 'Tenerife',     country: 'España',           coords: [-16.25, 28.47],  isMain: false },
  { name: 'Amberes',      country: 'Bélgica',          coords: [ 4.40,  51.22],  isMain: false },
  { name: 'Rotterdam',    country: 'Países Bajos',     coords: [ 4.48,  51.92],  isMain: false },
  { name: 'Hamburgo',     country: 'Alemania',         coords: [ 9.99,  53.55],  isMain: false },
  { name: 'Tilbury',      country: 'Reino Unido',      coords: [ 0.36,  51.46],  isMain: false },
  { name: 'Taicang',      country: 'China',            coords: [121.10, 31.46],  isMain: false },
  { name: 'Ningbo',       country: 'China',            coords: [121.55, 29.87],  isMain: false },
];

const AMA_ROUTES = [
  // South America coastal
  { from: [-40.29, -20.32], to: [-43.17, -22.91] },
  { from: [-43.17, -22.91], to: [-46.31, -23.96] },
  { from: [-46.31, -23.96], to: [-48.51, -25.52] },
  { from: [-48.51, -25.52], to: [-56.16, -34.90] },
  { from: [-56.16, -34.90], to: [-59.03, -34.09] },
  // Brazil to West Africa
  { from: [-40.29, -20.32], to: [-4.01,   5.31] },
  { from: [-4.01,   5.31],  to: [-0.02,   5.67] },
  { from: [-0.02,   5.67],  to: [ 2.42,   6.37] },
  { from: [ 2.42,   6.37],  to: [ 3.39,   6.45] },
  // West Africa to Dakar & Tenerife
  { from: [-4.01,   5.31],  to: [-17.44, 14.69] },
  { from: [-17.44, 14.69],  to: [-16.25, 28.47] },
  // Tenerife to North Europe
  { from: [-16.25, 28.47],  to: [ 0.36,  51.46] },
  { from: [ 0.36,  51.46],  to: [ 4.40,  51.22] },
  { from: [ 4.40,  51.22],  to: [ 4.48,  51.92] },
  { from: [ 4.48,  51.92],  to: [ 9.99,  53.55] },
  // Europe to China
  { from: [ 9.99,  53.55],  to: [121.10, 31.46] },
  // China coastal
  { from: [121.10, 31.46],  to: [121.55, 29.87] },
];

// ── WORLD MAP INITIALIZATION ──────────────────────────────────────
async function initWorldMap() {
  const container = document.getElementById('map-container');
  const loadingEl = document.getElementById('map-loading');
  if (!container || typeof d3 === 'undefined' || typeof topojson === 'undefined') return;

  const width  = container.offsetWidth  || 600;
  const height = Math.round(width * 0.625); // 16:10

  const svg = d3.select('#world-map')
    .attr('width',   width)
    .attr('height',  height)
    .attr('viewBox', `0 0 ${width} ${height}`)
    .style('background', '#002b5c');

  const projection = d3.geoMercator()
    .scale(width * 0.145)
    .center([0, 20])
    .translate([width / 2, height / 2]);

  const path = d3.geoPath().projection(projection);

  try {
    const world = await d3.json('https://cdn.jsdelivr.net/npm/world-atlas@2/countries-110m.json');
    const countries = topojson.feature(world, world.objects.countries);

    // Draw land
    svg.append('g')
      .selectAll('path')
      .data(countries.features)
      .join('path')
        .attr('d', path)
        .attr('fill', '#1a4a7a')
        .attr('stroke', '#2a5a8a')
        .attr('stroke-width', 0.5);

    // Draw route lines
    AMA_ROUTES.forEach((route) => {
      const from = projection(route.from);
      const to   = projection(route.to);
      if (!from || !to) return;
      svg.append('line')
        .attr('x1', from[0]).attr('y1', from[1])
        .attr('x2', to[0])  .attr('y2', to[1])
        .attr('stroke', '#C5A059')
        .attr('stroke-width', 1.5)
        .attr('stroke-dasharray', '4 2')
        .attr('stroke-opacity', 0.75)
        .attr('stroke-linecap', 'round');
    });

    // Draw port markers + tooltip group
    const tooltip = d3.select('#map-container')
      .append('div')
      .attr('class', 'map-tooltip')
      .style('position', 'absolute')
      .style('background', 'rgba(0,0,0,0.75)')
      .style('color', '#fff')
      .style('padding', '4px 10px')
      .style('border-radius', '6px')
      .style('font-size', '12px')
      .style('pointer-events', 'none')
      .style('opacity', 0)
      .style('transition', 'opacity 0.15s');

    AMA_PORTS.forEach((port) => {
      const pos = projection(port.coords);
      if (!pos) return;
      const g = svg.append('g').attr('class', 'map-port').style('cursor', 'pointer');

      if (port.isMain) {
        g.append('circle')
          .attr('cx', pos[0]).attr('cy', pos[1])
          .attr('r', 11)
          .attr('fill', 'none')
          .attr('stroke', '#C5A059')
          .attr('stroke-width', 1)
          .attr('stroke-opacity', 0.45);
      }

      g.append('circle')
        .attr('cx', pos[0]).attr('cy', pos[1])
        .attr('r',  port.isMain ? 6 : 4)
        .attr('fill', '#C5A059')
        .attr('stroke', '#fff')
        .attr('stroke-width', port.isMain ? 2 : 1)
        .on('mouseover', function(event) {
          tooltip.style('opacity', 1)
            .html(`<strong>${port.name}</strong><br>${port.country}`);
        })
        .on('mousemove', function(event) {
          const rect = container.getBoundingClientRect();
          tooltip
            .style('left', (event.clientX - rect.left + 12) + 'px')
            .style('top',  (event.clientY - rect.top  - 10) + 'px');
        })
        .on('mouseleave', function() {
          tooltip.style('opacity', 0);
        });
    });

    // Label: Zárate (main port)
    const zaratePos = projection([-59.03, -34.09]);
    if (zaratePos) {
      const lg = svg.append('g').attr('transform', `translate(${zaratePos[0] - 35},${zaratePos[1] + 12})`);
      lg.append('rect').attr('width', 70).attr('height', 20).attr('rx', 4)
        .attr('fill', 'rgba(255,255,255,0.15)').attr('stroke', 'rgba(255,255,255,0.3)').attr('stroke-width', 0.8);
      lg.append('text').attr('x', 35).attr('y', 14).attr('text-anchor', 'middle')
        .attr('fill', '#fff').attr('font-size', 10).attr('font-weight', '600')
        .attr('font-family', 'Inter, sans-serif').text('Zárate');
    }

    // Label de Taicang eliminado — todos los puertos usan tooltip al hover

    // Hide loading indicator
    if (loadingEl) loadingEl.style.display = 'none';

  } catch (err) {
    if (loadingEl) loadingEl.textContent = 'Mapa no disponible.';
  }
}

// ── INIT ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Delay map init slightly so D3 + TopoJSON scripts are fully parsed
  if (typeof d3 !== 'undefined' && typeof topojson !== 'undefined') {
    initWorldMap();
  } else {
    // Scripts loaded with defer/async — wait for them
    window.addEventListener('load', initWorldMap);
  }
});

// Redraw map on significant resize
let _mapResizeTimer;
window.addEventListener('resize', () => {
  clearTimeout(_mapResizeTimer);
  _mapResizeTimer = setTimeout(() => {
    const svg = document.getElementById('world-map');
    const loading = document.getElementById('map-loading');
    if (svg) { svg.innerHTML = ''; }
    if (loading) { loading.style.display = 'flex'; }
    initWorldMap();
  }, 400);
});


