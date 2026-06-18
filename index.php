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
        <title>Sitio en Mantenimiento | AMA Agencies</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: "Inter", sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; }
            .box { max-width: 500px; padding: 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
            h1 { color: #020617; font-size: 1.5rem; margin-top: 0; }
            p { color: #475569; line-height: 1.5; }
            .brand { color: #0033a0; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem; display: block; }
        </style>
    </head>
    <body>
        <div class="box">
            <span class="brand">AMA Agencies Argentina</span>
            <h1>Sitio en Mantenimiento</h1>
            <p>Estamos realizando mejoras temporales en nuestra plataforma para brindarle un mejor servicio. Por favor, vuelva a intentarlo en unos minutos.</p>
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
