<?php
/**
 * Pruebas de interfaz: que el portal no ofrezca contraseñas.
 *
 * Extraen los fragmentos de vista de los archivos reales y los renderizan
 * con variables controladas. No necesitan base de datos ni red.
 *
 * Verifican el requisito central: con Entra ID activo, un empleado nunca ve
 * un campo de contraseña, y el listado de usuarios no se desalinea.
 */

$ROOT = dirname(__DIR__);
$fail = 0;

function chk(string $name, bool $ok): void
{
    global $fail;
    if (!$ok) {
        $fail++;
    }
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $name);
}

/**
 * Extrae un fragmento de un archivo fuente entre dos marcas y lo guarda
 * en un temporal para poder incluirlo con variables controladas.
 */
function fragmento(string $file, string $desde, string $hasta): string
{
    $src = file_get_contents($file);
    $ini = strpos($src, $desde);
    if ($ini === false) {
        fwrite(STDERR, "No se encontró '$desde' en $file\n");
        exit(1);
    }
    $fin = $hasta === '' ? strlen($src) : strpos($src, $hasta, $ini) + strlen($hasta);
    $tmp = tempnam(sys_get_temp_dir(), 'frag') . '.php';
    file_put_contents($tmp, substr($src, $ini, $fin - $ini));
    return $tmp;
}

// ── Stubs mínimos que las vistas esperan ───────────────────

function generate_csrf_token(): string
{
    return 'token-de-prueba';
}

/** Las vistas versionan los assets con filemtime; acá basta la ruta. */
function admin_asset(string $relativo): string
{
    return '../../public/assets/' . ltrim($relativo, '/');
}

$_SESSION['user_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'GET';
$rolesDict = ['admin_global' => 'Admin Global', 'cs' => 'Customer Service', 'caja' => 'Caja'];

// ── 1. Pantalla de login ───────────────────────────────────

$loginView = fragmento($ROOT . '/private/gestion-interna-agencia/index.php', '<!DOCTYPE html>', '');

function renderLogin(string $view, bool $entraOn, bool $modoEmergencia, string $error = ''): string
{
    $returnTo = '/gestion-interna-agencia/dashboard.php';
    ob_start();
    include $view;
    return ob_get_clean();
}

echo "── Login: lo que ve un empleado (Entra activo) ──\n";
$html = renderLogin($loginView, true, false);
chk('Ve el boton "Ingresar con Microsoft"', strpos($html, 'Ingresar con Microsoft') !== false);
chk('NO hay ningun input type="password"',  strpos($html, 'type="password"') === false);
chk('NO hay campo de usuario',              strpos($html, 'name="username"') === false);
chk('NO ve "Olvidaste tu contrasena"',      strpos($html, 'Olvidaste tu contrase') === false);
chk('Ve el enlace discreto de emergencia',  strpos($html, 'Acceso de emergencia') !== false);

echo "\n── Login: cuenta de emergencia (?emergencia=1) ──\n";
$html = renderLogin($loginView, true, true);
chk('Sigue ofreciendo el boton de Microsoft', strpos($html, 'Ingresar con Microsoft') !== false);
chk('Ahora SI aparece el campo de contrasena', strpos($html, 'type="password"') !== false);
chk('Aclara que es solo para emergencia',     strpos($html, 'cuenta de emergencia') !== false);

echo "\n── Login: Entra apagado (rollback) ──\n";
$html = renderLogin($loginView, false, true);
chk('NO ofrece el boton de Microsoft',   strpos($html, 'Ingresar con Microsoft') === false);
chk('Muestra el formulario de siempre',  strpos($html, 'type="password"') !== false);

echo "\n── Login: mensajes de error ──\n";
$html = renderLogin($loginView, true, false, '<script>alert(1)</script>');
chk('XSS en el error queda escapado',
    strpos($html, '<script>alert(1)</script>') === false && strpos($html, '&lt;script&gt;') !== false);

@unlink($loginView);

// ── 2. Listado de usuarios ─────────────────────────────────

$tablaView = fragmento($ROOT . '/private/gestion-interna-agencia/views/usuarios.php',
    '<table class="admin-table">', '</table>');

function renderTabla(string $view, array $flags, array $usuarios): string
{
    global $rolesDict;
    extract($flags);
    ob_start();
    include $view;
    return ob_get_clean();
}

/** @return array{0:int,1:int} cabeceras y celdas de la primera fila */
function columnas(string $html): array
{
    preg_match('#<thead>(.*?)</thead>#s', $html, $h);
    preg_match('#<tbody>(.*?)</tbody>#s', $html, $b);
    preg_match('#<tr>(.*?)</tr>#s', $b[1] ?? '', $fila);
    return [substr_count($h[1] ?? '', '<th'), substr_count($fila[1] ?? '', '<td')];
}

$base = [
    'id' => 2, 'nombre' => 'Ana Pérez', 'username' => 'aperez', 'email' => 'ana@atlantic-agency.example.com',
    'rol_portal' => 'cs', 'creado_en' => '2026-01-01', 'ultimo_login' => '2026-09-01 10:00:00',
    'is_active' => 1, 'password_updated_at' => null, 'auth_provider' => 'entra', 'is_break_glass' => 0,
];

$flagsPost = ['hasUsername' => true, 'hasIsActive' => true, 'hasUltimoLogin' => true,
              'hasAuthProvider' => true, 'mostrarClave' => false];

echo "\n── Listado de usuarios: alineacion de columnas ──\n";

$html = renderTabla($tablaView, $flagsPost, [$base]);
list($th, $td) = columnas($html);
chk("Post-migracion: $th cabeceras = $td celdas", $th === $td);
chk('Muestra la columna "Acceso"',    strpos($html, '>Acceso<') !== false);
chk('Oculta "Cambio de Clave"',       strpos($html, 'Cambio de Clave') === false);
chk('Badge "Microsoft" si es federada', strpos($html, '>Microsoft<') !== false);

$bg = $base; $bg['is_break_glass'] = 1; $bg['auth_provider'] = 'local';
chk('Badge "Emergencia" si es break-glass',
    strpos(renderTabla($tablaView, $flagsPost, [$bg]), '>Emergencia<') !== false);

$loc = $base; $loc['auth_provider'] = 'local';
chk('Badge "Contrasena" si aun no migro',
    strpos(renderTabla($tablaView, $flagsPost, [$loc]), '>Contraseña<') !== false);

// Compatibilidad con la BD sin migrar
$viejo = array_diff_key($base, ['auth_provider' => 1, 'is_break_glass' => 1]);
$html = renderTabla($tablaView, ['hasUsername' => true, 'hasIsActive' => true, 'hasUltimoLogin' => true,
                                 'hasAuthProvider' => false, 'mostrarClave' => true], [$viejo]);
list($th, $td) = columnas($html);
chk("Pre-migracion: $th cabeceras = $td celdas", $th === $td);
chk('Pre-migracion mantiene "Cambio de Clave"', strpos($html, 'Cambio de Clave') !== false);

$min = ['id' => 2, 'nombre' => 'X', 'email' => 'x@y.z', 'rol_portal' => 'cs', 'creado_en' => '2026-01-01'];
$html = renderTabla($tablaView, ['hasUsername' => false, 'hasIsActive' => false, 'hasUltimoLogin' => false,
                                 'hasAuthProvider' => false, 'mostrarClave' => false], [$min]);
list($th, $td) = columnas($html);
chk("BD minima: $th cabeceras = $td celdas", $th === $td);

@unlink($tablaView);

echo $fail === 0 ? "\nTODOS OK\n" : "\n$fail FALLOS\n";
exit($fail === 0 ? 0 : 1);
