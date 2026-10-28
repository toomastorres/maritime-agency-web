<?php
/**
 * Diagnóstico de conexión a base de datos — HERRAMIENTA TEMPORAL.
 *
 * Se sube A MANO por FTP a /public_html/ cuando el portal muestra
 * "Servicio temporalmente no disponible" y hay que averiguar por qué.
 * Está excluido del deploy automático (tests/** no se sincroniza).
 *
 * Uso:  https://atlantic-agency.example.com/diagnostico-env.php?t=<TOKEN>
 *
 * NO imprime contraseñas ni secretos: solo longitudes, nombres de
 * variables y códigos SQLSTATE.
 *
 * ⚠️ BORRAR DEL SERVIDOR APENAS SE RESUELVA EL PROBLEMA.
 */

const DIAG_TOKEN = '918cd1dc63418ea5fe40ae47d72c85d9';

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if (!hash_equals(DIAG_TOKEN, (string)($_GET['t'] ?? ''))) {
    http_response_code(404);
    exit("Not Found\n");
}

$out = [];
function say(string $s = ''): void { global $out; $out[] = $s; }
function mask(string $v): string {
    $n = strlen($v);
    if ($n === 0) return '(vacío)';
    if ($n <= 4) return str_repeat('*', $n);
    return substr($v, 0, 2) . str_repeat('*', max(1, $n - 4)) . substr($v, -2);
}

say('DIAGNÓSTICO DE CONEXIÓN — ' . date('Y-m-d H:i:s'));
say(str_repeat('=', 62));
say();

// ── 1. Ubicación del .env ──────────────────────────────────
$esperado = __DIR__ . '/.env';
say('1. ARCHIVO .env');
say('   Ruta esperada: ' . $esperado);

if (!file_exists($esperado)) {
    say('   ✗ NO EXISTE en esa ruta.');
    say();
    say('   Buscando en ubicaciones alternativas...');
    foreach ([__DIR__ . '/.env.txt', __DIR__ . '/env', dirname(__DIR__) . '/.env',
              __DIR__ . '/gestion-interna-agencia/.env',
              __DIR__ . '/private/gestion-interna-agencia/.env'] as $alt) {
        if (file_exists($alt)) say('   → ENCONTRADO en: ' . $alt . '  (está en el lugar equivocado)');
    }
    say();
    say('   ACCIÓN: subir el .env a ' . $esperado);
    echo implode("\n", $out) . "\n";
    exit;
}

say('   ✓ Existe.');
$perm  = substr(sprintf('%o', fileperms($esperado)), -4);
$bytes = filesize($esperado);
say('   Permisos: ' . $perm . ($perm === '0644' ? '  ✓' : '  ← debería ser 0644'));
say('   Tamaño:   ' . $bytes . ' bytes' . ($bytes < 100 ? '  ← sospechosamente chico' : ''));

$raw = @file_get_contents($esperado);
if ($raw === false) {
    say('   ✗ EXISTE PERO PHP NO PUEDE LEERLO (permisos o dueño).');
    say('   ACCIÓN: poner permisos 644 desde el File Manager de cPanel.');
    echo implode("\n", $out) . "\n";
    exit;
}
say('   ✓ Legible por PHP.');

$bom = substr($raw, 0, 3) === "\xEF\xBB\xBF";
say('   BOM UTF-8: ' . ($bom ? '✗ SÍ — rompe la primera variable' : 'no  ✓'));
$crlf = strpos($raw, "\r\n") !== false;
say('   Fin de línea: ' . ($crlf ? 'CRLF (Windows) — tolerado' : 'LF  ✓'));
say();

// ── 2. Parseo (misma lógica que loadEnv) ───────────────────
say('2. VARIABLES ENCONTRADAS');
$vars = [];
foreach (preg_split('/\R/', $raw) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
    if (strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $vars[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
}
say('   Total de claves: ' . count($vars));
say('   Nombres: ' . (count($vars) ? implode(', ', array_keys($vars)) : '(ninguna)'));
say();

// ── 3. Bloque de base de datos ─────────────────────────────
say('3. CONFIGURACIÓN DE BASE DE DATOS');
$faltan = [];
foreach (['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'] as $k) {
    $tiene = isset($vars[$k]) && $vars[$k] !== '';
    if (!$tiene && $k !== 'DB_PASS') $faltan[] = $k;
    $muestra = !$tiene ? '(FALTA o vacío)'
        : (in_array($k, ['DB_HOST', 'DB_NAME'], true)
            ? $vars[$k]
            : ($k === 'DB_PASS' ? mask($vars[$k]) . '  (' . strlen($vars[$k]) . ' caracteres)' : mask($vars[$k])));
    say(sprintf('   %-9s %s %s', $k, $tiene ? '✓' : '✗', $muestra));
}
say();

if ($faltan) {
    say('   ✗ CAUSA ENCONTRADA: faltan ' . implode(', ', $faltan));
    say('   El .env se guardó perdiendo el bloque de base de datos.');
    say('   ACCIÓN: restaurarlo completo usando .env.example como plantilla.');
    echo implode("\n", $out) . "\n";
    exit;
}

// ── 4. Prueba de conexión real ─────────────────────────────
say('4. PRUEBA DE CONEXIÓN');
foreach ([$vars['DB_HOST'], '127.0.0.1'] as $i => $host) {
    if ($i === 1 && $host === $vars['DB_HOST']) break;
    $etiqueta = $i === 0 ? 'con DB_HOST del .env' : 'reintento con 127.0.0.1';
    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$vars['DB_NAME']};charset=utf8mb4",
            $vars['DB_USER'], $vars['DB_PASS'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $n = $pdo->query('SELECT COUNT(*) FROM usuarios_admin')->fetchColumn();
        say("   ✓ CONEXIÓN OK ({$etiqueta}) — host={$host}");
        say("   ✓ Tabla usuarios_admin accesible: {$n} cuentas.");
        $cols = $pdo->query("SHOW COLUMNS FROM usuarios_admin LIKE 'entra_%'")->fetchAll(PDO::FETCH_COLUMN);
        say('   Columnas de Entra presentes: ' . ($cols ? implode(', ', $cols) : 'NINGUNA — falta correr la migración'));
        say();
        say('   El problema NO es la base de datos. Revisar src/data/logs/app.log');
        say('   por si el error viene de otra parte.');
        echo implode("\n", $out) . "\n";
        exit;
    } catch (PDOException $e) {
        $code = $e->errorInfo[0] ?? 'HY000';
        $num  = $e->errorInfo[1] ?? 0;
        say("   ✗ Falló ({$etiqueta}) — SQLSTATE {$code}, error {$num}");
        $causa = [
            1045 => 'Usuario o contraseña de MySQL incorrectos. Verificar DB_USER y DB_PASS en cPanel > MySQL Databases.',
            1044 => 'El usuario existe pero no tiene permisos sobre esa base. Asignarlo en cPanel > MySQL Databases.',
            1049 => "La base '{$vars['DB_NAME']}' no existe. Revisar el nombre exacto en phpMyAdmin.",
            2002 => 'El servidor MySQL no responde en ese host. En este hosting DB_HOST debe ser exactamente localhost; si ya lo es, el servicio MySQL está caído y hay que abrir ticket a el hosting compartido.',
            2005 => 'El host de DB_HOST no resuelve. Debe ser localhost.',
        ][$num] ?? 'Error no catalogado. Revisar src/data/logs/app.log.';
        say('     → ' . $causa);
    }
}
say();
say('   ACCIÓN: corregir el .env según la causa de arriba y recargar esta página.');

echo implode("\n", $out) . "\n";
say();
