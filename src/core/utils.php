<?php
/**
 * AMA — utils.php
 * Funciones utilitarias centrales: carga .env, respuestas JSON.
 */

/**
 * Carga de variables de entorno (standalone, sin dependencias externas)
 * Primero lee el archivo .env físico, luego incorpora las de ambiente (Host).
 */
function loadEnv(string $path): array {
    $vars = [];
    
    // 1. Intentar cargar del archivo físico .env
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                // Ignorar comentarios
                if (strpos($line, '#') === 0 || strpos($line, ';') === 0) continue;
                if (strpos($line, '=') === false) continue;
                [$key, $value] = explode('=', $line, 2);
                // Limpiar comillas iniciales/finales si existen
                $vars[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
    }
    
    // 2. Fallback: fusionar con variables de entorno del servidor (Hosting)
    // Especialmente útil si el archivo .env no se sube por estar ignorado y las env variables vienen del CPanel/cPanel
    $systemVars = [
        'APP_ENV',
        'DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME',
        'SMTP_HOST', 'SMTP_PORT', 'SMTP_SECURE', 'SMTP_USER', 'SMTP_PASS',
        'SMTP_FROM', 'SMTP_FROM_NAME', 'SMTP_REPLY_TO',
        'NOTIFY_TO',
        'NOTIFY_RRHH', 'NOTIFY_CAJA', 'NOTIFY_DOCS', 'NOTIFY_CS',
        'NOTIFY_COMERCIAL_AUTOS', 'NOTIFY_COMERCIAL_PROYECTO', 'NOTIFY_COMERCIAL_CONTAINERS',
        'BRIDGE_TOKEN', 'ADMIN_USER', 'ADMIN_PASS_HASH',
        'HEALTH_TOKEN', 'SETUP_TOKEN',
        'ENTRA_TENANT_ID', 'ENTRA_CLIENT_ID', 'ENTRA_CLIENT_SECRET',
        'ENTRA_REDIRECT_URI', 'ENTRA_POST_LOGOUT_URI',
        'TC_BOT_TENANT_ID', 'TC_BOT_CLIENT_ID', 'TC_BOT_CLIENT_SECRET',
        'TC_BOT_MAILBOX', 'TC_BOT_CRON_TOKEN', 'TC_BOT_DRY_RUN'
    ];
    foreach ($systemVars as $key) {
        if (!isset($vars[$key]) || $vars[$key] === '') {
            $val = getenv($key);
            if ($val !== false && $val !== '') {
                $vars[$key] = $val;
            } elseif (isset($_ENV[$key]) && $_ENV[$key] !== '') {
                $vars[$key] = $_ENV[$key];
            } elseif (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
                $vars[$key] = $_SERVER[$key];
            }
        }
    }
    
    return $vars;
}

/**
 * Rate limiter simple basado en archivos temporales.
 * Limita peticiones por IP para endpoints públicos.
 *
 * @param int $maxRequests Máximo de peticiones permitidas
 * @param int $windowSeconds Ventana de tiempo en segundos
 * @return bool true si la petición está dentro del límite
 */
function checkRateLimit(int $maxRequests = 60, int $windowSeconds = 60): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
    $key = md5($ip . ':' . parse_url($endpoint, PHP_URL_PATH));

    $tmpDir = sys_get_temp_dir() . '/ama_rate_limit';
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0700, true);
    }

    // Garbage collection probabilística (1% de requests)
    if (mt_rand(1, 100) === 1) {
        $gcFiles = @glob($tmpDir . '/*');
        if ($gcFiles) {
            $now = time();
            foreach ($gcFiles as $gcf) {
                if (is_file($gcf) && $now - filemtime($gcf) > 3600) {
                    @unlink($gcf);
                }
            }
        }
    }

    $file = $tmpDir . '/' . $key;

    $data = ['count' => 0, 'start' => time()];
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if ($content) {
            $data = json_decode($content, true) ?: $data;
        }
    }

    // Reset ventana si expiró
    if (time() - $data['start'] > $windowSeconds) {
        $data = ['count' => 0, 'start' => time()];
    }

    $data['count']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);

    if ($data['count'] > $maxRequests) {
        http_response_code(429);
        header('Retry-After: ' . ($windowSeconds - (time() - $data['start'])));
        echo json_encode(['status' => 'error', 'error' => 'Demasiadas solicitudes. Intente más tarde.']);
        exit;
    }

    return true;
}

/**
 * Función centralizada para enviar respuestas JSON estructuradas
 * Finaliza la ejecución del script después de emitir la respuesta.
 */
function jsonResponse(int $code, $data, ?string $error = null): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'status' => ($code >= 200 && $code < 300) ? 'ok' : 'error',
        'timestamp' => date('c')
    ];
    
    if ($error !== null) {
        $response['error'] = $error;
    } else {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Escritura atómica de archivos de cache.
 * Escribe a archivo temporal y luego renombra, evitando lecturas parciales.
 */
function atomicCacheWrite(string $file, string $data): bool {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $data, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    return @rename($tmp, $file);
}

/**
 * Invalida el cache de archivo de una API pública borrando todos los
 * archivos que empiecen con el prefijo dado (p. ej. 'schedules_' cubre
 * todas las variantes cacheadas por filtro de buque/POL/POD).
 * Se llama desde el portal interno justo después de escribir datos
 * nuevos, para que la publicación sea inmediata en vez de esperar el TTL.
 */
function clearCacheByPrefix(string $cacheDir, string $prefix): int {
    $cleared = 0;
    foreach (glob(rtrim($cacheDir, '/') . '/' . $prefix . '*.json') ?: [] as $file) {
        if (@unlink($file)) {
            $cleared++;
        }
    }
    return $cleared;
}

/**
 * Calcula el tamaño total de un directorio de forma recursiva (en bytes).
 */
function get_dir_size(string $dir): int {
    $size = 0;
    if (!is_dir($dir)) return $size;
    
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

/**
 * Lee las últimas N líneas de un archivo de forma eficiente sin sobrecargar memoria.
 */
function tail_file(string $file, int $lines = 100): array {
    if (!file_exists($file) || !is_readable($file)) {
        return ["(El archivo no existe o no se puede leer: " . basename($file) . ")"];
    }

    $handle = fopen($file, "r");
    if (!$handle) return ["(Error al abrir el archivo)"];

    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = [];

    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) rewind($handle);
        $text[$lines - $linecounter - 1] = fgets($handle);
        if ($beginning) break;
    }
    fclose($handle);
    
    // Filtrar vacíos y reordenar el array para mostrar cronológicamente
    $text = array_filter($text, function($line) { return trim($line) !== ''; });
    return array_reverse($text);
}
