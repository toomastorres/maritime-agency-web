<?php
/**
 * AMA — index.php
 * Front Controller: router principal de la aplicación.
 */
require_once __DIR__ . '/src/config/config.php';

// Output buffering: soporte para compresión GZIP
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

// ── Seguridad: Headers HTTP ─────────────────────────────────
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

$requestBase = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$dirName = dirname($_SERVER['SCRIPT_NAME']);
if ($dirName !== '/' && $dirName !== '\\' && strpos($requestBase, $dirName) === 0) {
    $request = substr($requestBase, strlen($dirName));
} else {
    $request = $requestBase;
}

$request = trim($request, '/');

// ── Seguridad: Sanitizar ruta ──────────────────────────────

// Bloquear null bytes, path traversal y caracteres peligrosos
if (preg_match('/(\.\.|\\0|<|>|\'|"|%00|%2e%2e)/i', $request)) {
    http_response_code(400);
    exit;
}

// Solo permitir caracteres alfanuméricos, guiones, puntos y barras
if ($request !== '' && !preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $request)) {
    http_response_code(400);
    exit;
}

// ── Modo Mantenimiento ───────────────────────────────────────
if (file_exists(__DIR__ . '/.maintenance')) {
    http_response_code(503);
    header('Retry-After: 3600');
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sitio en Mantenimiento | Atlantic Maritime Agency</title>
        <style>
            body { font-family: "DM Sans", Verdana, Geneva, Arial, sans-serif; background: #F0F0F0; color: #1A1A1C; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 24px; box-sizing: border-box; }
            .olt-maint-card { max-width: 480px; width: 100%; background: #fff; border: 1px solid rgba(128, 128, 131, .35); padding: 48px 40px; text-align: center; box-sizing: border-box; }
            .olt-maint-card img { height: 32px; max-width: 100%; width: auto; margin-bottom: 28px; }
            .olt-maint-kicker { font-size: 10px; font-weight: 600; letter-spacing: .16em; text-transform: uppercase; color: #808083; margin-bottom: 16px; }
            h1 { font-family: Georgia, "DM Serif Display", serif; font-weight: 400; color: #0F396C; font-size: 26px; margin: 0 0 16px; line-height: 1.2; }
            p { color: #1A1A1C; line-height: 1.7; font-size: 15px; margin: 0 0 8px; }
            .olt-maint-en { color: #808083; font-size: 14px; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(128, 128, 131, .25); }
        </style>
    </head>
    <body>
        <div class="olt-maint-card">
            <img src="' . ASSETS_URL . '/img/logo-blue.svg" alt="Atlantic Maritime Agency">
            <div class="olt-maint-kicker">Atlantic Maritime Agency S.A.</div>
            <h1>Sitio en mantenimiento</h1>
            <p>Estamos realizando mejoras temporales en nuestra plataforma para brindarle un mejor servicio. Por favor, vuelva a intentarlo en unos minutos.</p>
            <p class="olt-maint-en">We are temporarily improving our platform to serve you better. Please try again shortly.</p>
        </div>
    </body>
    </html>';
    exit;
}

// ── Router API ─────────────────────────────────────────────

if (strpos($request, 'api/') === 0) {
    $apiRoute = substr($request, 4);

    // Solo permitir nombres de archivo simples (sin subdirectorios)
    if (strpos($apiRoute, '/') !== false || strpos($apiRoute, '..') !== false) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $apiPath = __DIR__ . '/src/logic/api/' . $apiRoute;

    // Verificar que el archivo resuelto esté dentro del directorio esperado
    $resolvedApi = realpath($apiPath) ?: realpath($apiPath . '.php');
    $allowedApiDir = realpath(__DIR__ . '/src/logic/api');

    if ($resolvedApi && $allowedApiDir && strpos($resolvedApi, $allowedApiDir) === 0 && is_file($resolvedApi)) {
        require $resolvedApi;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
    }
    exit;
}

// ── Router Páginas ─────────────────────────────────────────

if ($request === '' || $request === 'index.html' || $request === 'index.php') {
    $page = 'index.php';
} else {
    // Solo permitir nombres de archivo simples (sin subdirectorios)
    $page = basename($request);
}

$pagePath = __DIR__ . '/src/pages/' . $page;

// Verificar que el archivo resuelto esté dentro del directorio de páginas
$allowedPagesDir = realpath(__DIR__ . '/src/pages');

$resolvedPage = realpath($pagePath) ?: realpath($pagePath . '.php') ?: realpath($pagePath . '.html');

if ($resolvedPage && $allowedPagesDir && strpos($resolvedPage, $allowedPagesDir) === 0 && is_file($resolvedPage)) {
    require $resolvedPage;
} else {
    http_response_code(404);
    if (file_exists(__DIR__ . '/404.php')) {
        require __DIR__ . '/404.php';
    } else {
        echo '<h1>404 Not Found</h1>';
    }
}
