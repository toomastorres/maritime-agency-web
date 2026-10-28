<?php
/**
 * AMA — assets.php (Admin)
 * URL de un asset del portal con marca de versión.
 *
 * El portal enlazaba sus hojas de estilo sin ninguna versión
 * (`href=".../admin-portal.css"`), a diferencia del sitio público, que usa
 * assetUrl() y le agrega ?v=<hash>. Consecuencia: después de cada despliegue
 * el navegador de cada empleado seguía sirviendo el CSS viejo de su caché.
 * El HTML nuevo llegaba con clases que esa copia no conocía y el portal se
 * veía a medio maquetar —títulos sin tipografía, pestañas como links pelados,
 * filtros apilados— hasta que alguien hacía Ctrl+F5.
 *
 * filemtime cambia sola con cada despliegue, así que la caché se invalida
 * justo cuando tiene que hacerlo y sigue funcionando el resto del tiempo.
 */

if (!function_exists('admin_asset')) {
    function admin_asset(string $relativo): string {
        $base = '../../public/assets/';
        $abs  = dirname(__DIR__, 3) . '/public/assets/' . ltrim($relativo, '/');
        $v    = is_file($abs) ? filemtime($abs) : time();
        return $base . ltrim($relativo, '/') . '?v=' . $v;
    }
}
