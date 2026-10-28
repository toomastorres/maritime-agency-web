<?php
/**
 * AMA — mapa-red.php
 * Módulo embebible del mapa de la red de servicios. Pensado para
 * insertarse por iframe en Servicios (P4), con auto-resize por
 * postMessage. No incluye header/footer del sitio: es un componente
 * autocontenido con su propio <head>.
 */
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Red de servicios — Atlantic Maritime Agency</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo assetUrl('css/mapa-red.css'); ?>">
<!-- El módulo va por iframe y no heredaba nada del sitio: cargaba sin i18n,
     así que toda la sección de la red salía en castellano en las versiones
     en inglés, italiano y portugués de Servicios. -->
<script src="<?php echo assetUrl('js/i18n.js'); ?>" defer></script>
<script src="https://cdn.jsdelivr.net/npm/d3@7.9.0/dist/d3.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/topojson-client@3.1.0/dist/topojson-client.min.js" defer></script>
</head>
<body>
<div class="wrap">

  <div class="intro">
    <div class="kick" data-i18n="map.kicker">Nuestra red</div>
    <h1 data-i18n="map.title">Puertos y servicios regulares</h1>
    <p data-i18n="map.intro">Elegí un servicio para ver su rotación, o pasá el cursor por un puerto para conocer su detalle.</p>
  </div>

  <div class="layout" style="border-top:1px solid var(--rule)">
    <div class="rail">
      <h2 data-i18n="map.services_title">Servicios regulares</h2>
      <div id="svc-list"></div>
      <div class="railfoot">
        <div class="lb" data-i18n="map.ports_in_service">Puertos en el servicio</div>
        <div class="vl" id="port-count">—</div>
        <div class="dsc" id="svc-note">—</div>
      </div>
    </div>
    <div class="stage">
      <svg id="map" role="img" aria-label="Mapa de la red de servicios de AMA" data-i18n-aria="map.svg_alt"></svg>
      <div class="legend">
        <div><i style="width:9px;height:9px;background:#0069CC"></i><span data-i18n="map.legend_base">Puerto base — Zárate</span></div>
        <div><i style="width:7px;height:7px;background:#0F396C"></i><span data-i18n="map.legend_call">Puerto de escala</span></div>
        <div><i style="width:22px;height:2px;background:#0F396C"></i><span data-i18n="map.legend_selected">Servicio seleccionado</span></div>
        <div><i style="width:22px;height:1px;background:rgba(128,128,131,.55)"></i><span data-i18n="map.legend_other">Otro servicio regular</span></div>
        <div><i style="width:7px;height:7px;background:rgba(128,128,131,.5)"></i><span data-i18n="map.legend_transship">Red del grupo · por transbordo</span></div>
      </div>
      <div class="tip" id="tip"><div class="t"></div><div class="s"></div></div>
    </div>
  </div>

  <div class="detail">
    <div><div class="lb" data-i18n="map.rotation">Rotación</div><div class="v" id="d-rot">—</div><div class="n" id="d-rot-n">—</div></div>
    <div><div class="lb" data-i18n="map.frequency">Frecuencia</div><div class="v" id="d-freq">—</div><div class="n" id="d-freq-n">—</div></div>
    <div><div class="lb" data-i18n="map.cargo_types">Tipos de carga</div><div class="v" id="d-cargo">—</div><div class="n" data-i18n="map.cargo_note">Consultá el schedule para las fechas confirmadas de cada buque.</div></div>
  </div>

  <div class="ports">
    <h2 data-i18n="map.ports_title">Puertos de la red</h2>
    <p class="sub" data-i18n="map.ports_sub">Los puertos de nuestros dos servicios regulares, más los de África Occidental que se alcanzan por transbordo dentro de la red de Atlantic Maritime Group. Todo termina en Terminal Zárate.</p>
    <div class="grid" id="region-grid"></div>
  </div>

</div>

<script src="<?php echo assetUrl('js/mapa-red.js'); ?>" defer></script>
</body>
</html>
