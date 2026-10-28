<?php
/**
 * Test del bot de tipo de cambio: regla horaria, detección de correo
 * automático y armado de las respuestas.
 *
 * Cubre lo que puede romperse en silencio: un bucle de respuestas
 * automáticas, una cotización enviada fuera de hora, o HTML del cliente
 * inyectado en el cuerpo del mail.
 *
 * Uso:  php tests/test_tc_mailbot.php
 */

date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/../src/core/tipo_cambio.php';
require_once __DIR__ . '/../src/core/tc_mailbot.php';

$fail = 0;

/**
 * @param  string $nombre
 * @param  bool   $ok
 * @param  string $detalle
 * @return void
 */
function check(string $nombre, bool $ok, string $detalle = ''): void {
    global $fail;
    if (!$ok) { $fail++; }
    printf("  %s  %-46s %s\n", $ok ? 'PASS' : 'FAIL', $nombre, $detalle);
}

/**
 * Invoca un método privado.
 *
 * @param  object $obj
 * @param  string $metodo
 * @param  array  $args
 * @return mixed
 */
function invocar($obj, string $metodo, array $args) {
    $m = (new ReflectionClass($obj))->getMethod($metodo);
    $m->setAccessible(true);
    return $m->invokeArgs($obj, $args);
}

// PDO de utilería: los casos de abajo cortan antes de tocar la base.
$pdo = new PDO('sqlite::memory:');
$bot = new TcMailBot($pdo, new GraphMail([
    'TC_MAIL_TENANT_ID'     => 'tenant',
    'TC_MAIL_CLIENT_ID'     => 'client',
    'TC_MAIL_CLIENT_SECRET' => 'secret',
    'TC_MAIL_BUZON'         => 'tc@atlantic-agency.example.com',
]));

// ── 1. Horario comercial ───────────────────────────────────

echo "\n1. Regla de horario comercial (L-V 09:00-17:00)\n";

$casos = [
    // [fecha,                   esperado, descripción]
    ['2026-09-14 08:59:00', false, 'lunes 08:59 — antes de abrir'],
    ['2026-09-14 09:00:00', true,  'lunes 09:00 — apertura'],
    ['2026-09-14 12:30:00', true,  'lunes al mediodía'],
    ['2026-09-14 16:59:00', true,  'lunes 16:59 — último minuto'],
    ['2026-09-14 17:00:00', false, 'lunes 17:00 — cierre'],
    ['2026-09-14 23:30:00', false, 'lunes de noche'],
    ['2026-09-18 10:00:00', true,  'viernes 10:00'],
    ['2026-09-19 10:00:00', false, 'sábado 10:00'],
    ['2026-09-20 10:00:00', false, 'domingo 10:00'],
];

foreach ($casos as [$fecha, $esperado, $desc]) {
    $ts  = strtotime($fecha);
    $got = tcEnHorario($ts);
    check($desc, $got === $esperado, $got ? 'en horario' : 'fuera de horario');
}

// ── 2. Detección de correo automático ──────────────────────

echo "\n2. Detección de correo automático (anti-bucle)\n";

$casosHeaders = [
    ['humano',                 ['from' => 'cliente@empresa.com'],              null],
    ['auto-submitted',         ['auto-submitted' => 'auto-replied'],           'auto_submitted'],
    ['auto-submitted: no',     ['auto-submitted' => 'no'],                     null],
    ['respuesta propia',       ['x-ama-autoreply' => 'tipo-de-cambio'],   'respuesta_propia'],
    ['autoresponder ajeno',    ['x-autoreply' => 'yes'],                       'auto_reply_header'],
    ['supresión declarada',    ['x-auto-response-suppress' => 'All'],          'auto_reply_header'],
    ['lista de distribución',  ['list-id' => '<news.empresa.com>'],            'lista_distribucion'],
    ['baja de lista',          ['list-unsubscribe' => '<mailto:x@y.com>'],     'lista_distribucion'],
    ['precedence bulk',        ['precedence' => 'bulk'],                       'precedence_bulk'],
    ['rebote (return-path <>)',['return-path' => '<>'],                        'rebote'],
];

foreach ($casosHeaders as [$desc, $headers, $esperado]) {
    $got = invocar($bot, 'motivoAutomatico', [$headers]);
    check($desc, $got === $esperado, $got === null ? 'se responde' : 'omitido: ' . $got);
}

// ── 3. Filtros previos a la base ───────────────────────────

echo "\n3. Filtros de remitente y antigüedad\n";

$ahora  = date('c');
$viejo  = date('c', time() - 48 * 3600);
$estado = ['status' => 'ok', 'horario' => tcHorario()];

$casosMsg = [
    ['remitente vacío', ['subject' => 'TC', 'receivedDateTime' => $ahora], 'remitente_vacio'],
    ['mail propio del buzón', [
        'from' => ['emailAddress' => ['address' => 'TC@atlantic-agency.example.com']],
        'subject' => 'TC', 'receivedDateTime' => $ahora,
    ], 'mensaje_propio'],
    ['remitente no-reply', [
        'from' => ['emailAddress' => ['address' => 'no-reply@banco.com']],
        'subject' => 'Resumen', 'receivedDateTime' => $ahora,
    ], 'remitente_automatico'],
    ['mailer-daemon', [
        'from' => ['emailAddress' => ['address' => 'MAILER-DAEMON@correo.com']],
        'subject' => 'Error', 'receivedDateTime' => $ahora,
    ], 'remitente_automatico'],
    ['asunto de ausencia', [
        'from' => ['emailAddress' => ['address' => 'cliente@empresa.com']],
        'subject' => 'Automatic reply: consulta', 'receivedDateTime' => $ahora,
    ], 'asunto_automatico'],
    ['asunto de rebote en español', [
        'from' => ['emailAddress' => ['address' => 'cliente@empresa.com']],
        'subject' => 'Respuesta automática de Juan', 'receivedDateTime' => $ahora,
    ], 'asunto_automatico'],
    ['apellido que contiene "abuse" SÍ se contesta', [
        'from' => ['emailAddress' => ['address' => 'jabusero@naviera-ejemplo.example']],
        'subject' => 'Tipo de cambio', 'receivedDateTime' => $ahora,
    ], null],
    ['bounces@ se omite', [
        'from' => ['emailAddress' => ['address' => 'bounces@lista.com']],
        'subject' => 'Aviso', 'receivedDateTime' => $ahora,
    ], 'remitente_automatico'],
    ['backlog de 48 horas', [
        'from' => ['emailAddress' => ['address' => 'cliente@empresa.com']],
        'subject' => 'Tipo de cambio', 'receivedDateTime' => $viejo,
    ], 'mensaje_antiguo'],
];

foreach ($casosMsg as [$desc, $msg, $motivoEsperado]) {
    if ($motivoEsperado === null) {
        // Pasa los filtros previos: procesar() sigue hasta la base, que acá
        // no existe. Lo que importa es que NO se haya frenado por remitente.
        try {
            $r = invocar($bot, 'procesar', [$msg, $estado]);
            $freno = $r['motivo'];
        } catch (\Throwable $e) {
            $freno = 'llego_a_la_base';
        }
        check($desc, $freno !== 'remitente_automatico', $freno);
        continue;
    }
    $r = invocar($bot, 'procesar', [$msg, $estado]);
    check($desc, $r['accion'] === 'omitido' && $r['motivo'] === $motivoEsperado,
        $r['accion'] . ' / ' . $r['motivo']);
}

// ── 4. Clave de idempotencia ───────────────────────────────

echo "\n4. Clave de idempotencia\n";

$c1 = invocar($bot, 'claveMensaje', [['internetMessageId' => '<abc@mail.com>', 'id' => 'AAA']]);
check('usa el Message-ID cuando existe', $c1 === '<abc@mail.com>', $c1);

$c2 = invocar($bot, 'claveMensaje', [['id' => 'AAA']]);
$c3 = invocar($bot, 'claveMensaje', [['id' => 'AAA']]);
check('sin Message-ID cae al id de Graph, estable', $c2 === $c3 && strpos($c2, 'graph-sha1:') === 0, $c2);

$c4 = invocar($bot, 'claveMensaje', [['internetMessageId' => str_repeat('x', 400)]]);
check('recorta a 255 (largo de la columna)', strlen($c4) === 255, strlen($c4) . ' chars');

// ── 5. Cuerpo de las respuestas ────────────────────────────

echo "\n5. Cuerpo de las respuestas\n";

$estadoOk = [
    'status'               => 'ok',
    'dolar_valor'          => '1450.5000',
    'euro_valor'           => '1580.2500',
    'ultima_actualizacion' => '2026-09-17 09:32:00',
    'datos_bancarios'      => "Banco Galicia\nCuenta 123-456",
    'horario'              => tcHorario(),
];

$msgCliente = ['bodyPreview' => 'Buen día, necesito el tipo de cambio de hoy.'];
$html = invocar($bot, 'construirRespuesta', [$estadoOk, $msgCliente]);

check('formatea el dólar en pesos', strpos($html, '1.450,50') !== false);
check('incluye el euro cuando está cargado', strpos($html, '1.580,25') !== false);
check('aclara la vigencia hasta las 17:00', strpos($html, '17:00') !== false);
check('adjunta los datos bancarios', strpos($html, 'Banco Galicia') !== false);
check('cita la consulta original', strpos($html, 'necesito el tipo de cambio') !== false);

$estadoSinEuro = array_merge($estadoOk, ['euro_valor' => '']);
$htmlSinEuro = invocar($bot, 'construirRespuesta', [$estadoSinEuro, []]);
check('omite el euro si no se cargó', strpos($htmlSinEuro, 'Euro (EUR)') === false);

$estadoPendiente = array_merge($estadoOk, ['status' => 'no_disponible']);
$htmlPendiente = invocar($bot, 'construirRespuesta', [$estadoPendiente, []]);
check('pendiente: avisa que no se cargó', strpos($htmlPendiente, 'todavía no fue cargada') !== false);
check('pendiente: no filtra la cotización', strpos($htmlPendiente, '1.450,50') === false);

$estadoCerrado = array_merge($estadoOk, ['status' => 'cerrado']);
$htmlCerrado = invocar($bot, 'construirRespuesta', [$estadoCerrado, []]);
check('cerrado: informa el horario de atención', strpos($htmlCerrado, 'lunes a viernes') !== false);
check('cerrado: no filtra la cotización', strpos($htmlCerrado, '1.450,50') === false);
check('cerrado: igual manda datos bancarios', strpos($htmlCerrado, 'Banco Galicia') !== false);

// ── 6. Escapado de contenido ajeno ─────────────────────────

echo "\n6. Escapado de contenido ajeno\n";

$estadoXss = array_merge($estadoOk, [
    'datos_bancarios' => '<script>alert(1)</script>Banco',
]);
$msgXss = ['bodyPreview' => '<img src=x onerror="alert(2)">'];
$htmlXss = invocar($bot, 'construirRespuesta', [$estadoXss, $msgXss]);

check('escapa HTML de los datos bancarios',
    strpos($htmlXss, '<script>alert(1)</script>') === false
    && strpos($htmlXss, '&lt;script&gt;') !== false);
check('escapa HTML de la consulta citada',
    strpos($htmlXss, '<img src=x') === false
    && strpos($htmlXss, '&lt;img') !== false);

$largo = ['bodyPreview' => str_repeat('a', 500)];
$htmlLargo = invocar($bot, 'construirRespuesta', [$estadoOk, $largo]);
check('recorta la cita larga a 200 caracteres', substr_count($htmlLargo, 'aaaa') > 0
    && strpos($htmlLargo, str_repeat('a', 201)) === false);

// ── 7. Estado del tipo de cambio contra base ───────────────
// Misma función que consume el endpoint web (/api/tipo_cambio), así que
// esto cubre los dos canales a la vez. Se usa SQLite con CURDATE()
// registrada como función para poder correr el SQL real sin un MySQL.

echo "\n7. Estado del tipo de cambio (core compartido)\n";

/**
 * Base de prueba con el esquema mínimo de exchange_rates y bank_details.
 *
 * @param  array $cotizaciones Filas [moneda, valor, fecha].
 * @return PDO
 */
function baseDePrueba(array $cotizaciones): PDO {
    $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->sqliteCreateFunction('CURDATE', function () { return date('Y-m-d'); }, 0);

    $db->exec("CREATE TABLE exchange_rates (
        id INTEGER PRIMARY KEY AUTOINCREMENT, moneda TEXT, valor TEXT,
        fecha_cotizacion TEXT, creado_en TEXT)");
    $db->exec("CREATE TABLE bank_details (id INTEGER PRIMARY KEY, contenido_html TEXT)");
    $db->exec("INSERT INTO bank_details (id, contenido_html) VALUES (1, 'Banco Galicia')");

    $stmt = $db->prepare("INSERT INTO exchange_rates (moneda, valor, fecha_cotizacion, creado_en)
                          VALUES (?, ?, ?, ?)");
    foreach ($cotizaciones as $c) {
        $stmt->execute([$c[0], $c[1], $c[2], date('Y-m-d H:i:s')]);
    }
    return $db;
}

$hoy       = date('Y-m-d');
$ayer      = date('Y-m-d', strtotime('-1 day'));
$enHorario = strtotime('2026-09-14 10:00:00');  // lunes
$cerrado   = strtotime('2026-09-14 19:00:00');  // lunes de noche

$e = tcEstadoActual(baseDePrueba([['USD', '1450.50', $hoy], ['EUR', '1580.25', $hoy]]), $enHorario);
check('con cotización de hoy → ok', $e['status'] === 'ok', $e['status']);
check('devuelve el dólar cargado', $e['dolar_valor'] === '1450.50', (string)$e['dolar_valor']);
check('devuelve el euro cargado', $e['euro_valor'] === '1580.25', (string)$e['euro_valor']);

$e = tcEstadoActual(baseDePrueba([['USD', '1450.50', $hoy]]), $enHorario);
check('sin euro igual resuelve ok', $e['status'] === 'ok' && $e['euro_valor'] === '', $e['status']);

$e = tcEstadoActual(baseDePrueba([['USD', '1400.00', $ayer]]), $enHorario);
check('cotización de ayer no sirve → no_disponible', $e['status'] === 'no_disponible', $e['status']);
check('no_disponible igual trae datos bancarios', $e['datos_bancarios'] === 'Banco Galicia');

$e = tcEstadoActual(baseDePrueba([['USD', '1450.50', $hoy]]), $cerrado);
check('fuera de hora → cerrado aunque esté cargada', $e['status'] === 'cerrado', $e['status']);
check('cerrado no expone la cotización', $e['dolar_valor'] === null);
check('cerrado igual trae datos bancarios', $e['datos_bancarios'] === 'Banco Galicia');

$e = tcEstadoActual(null, $enHorario);
check('sin base en horario → error', $e['status'] === 'error', $e['status']);
check('error nunca inventa un valor', $e['dolar_valor'] === null);

// Toma la última carga del día: Caja puede corregir la cotización.
$db = baseDePrueba([['USD', '1400.00', $hoy]]);
$db->exec("INSERT INTO exchange_rates (moneda, valor, fecha_cotizacion, creado_en)
           VALUES ('USD', '1455.00', '$hoy', '" . date('Y-m-d H:i:s', time() + 60) . "')");
$e = tcEstadoActual($db, $enHorario);
check('usa la última carga del día', $e['dolar_valor'] === '1455.00', (string)$e['dolar_valor']);

echo $fail === 0 ? "\nTODOS OK\n" : "\n$fail FALLOS\n";
exit($fail === 0 ? 0 : 1);
