<?php
/**
 * Diagnóstico de envío de correo — HERRAMIENTA TEMPORAL.
 *
 * Se sube A MANO por FTP a /public_html/ para verificar que la casilla
 * del hosting envía y que el DNS del subdominio está publicado.
 * Excluido del deploy automático (tests/** no se sincroniza).
 *
 * Uso:  https://atlantic-agency.example.com/diagnostico-email.php?t=<TOKEN>&to=vos@ejemplo.com
 *
 * NO imprime la contraseña: solo su longitud.
 *
 * ⚠️ BORRAR DEL SERVIDOR APENAS SE RESUELVA.
 */

const DIAG_TOKEN = 'c3ef545f516e82206d98627c7ce908a7';

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if (!hash_equals(DIAG_TOKEN, (string)($_GET['t'] ?? ''))) {
    http_response_code(404);
    exit("Not Found\n");
}

require_once __DIR__ . '/src/config/config.php';

function line(string $s = ''): void { echo $s . "\n"; }
function chk(bool $ok, string $label, string $detail = ''): bool {
    printf("  %-5s  %-44s %s\n", $ok ? 'OK' : 'FALLA', $label, $detail);
    return $ok;
}

line('DIAGNÓSTICO DE CORREO — ' . date('Y-m-d H:i:s'));
line(str_repeat('=', 66));
line();

// ── 1. Configuración cargada ───────────────────────────────
line('1. CONFIGURACIÓN (.env)');
$cfg = [
    'SMTP_HOST'   => defined('SMTP_HOST')   ? SMTP_HOST   : '',
    'SMTP_PORT'   => defined('SMTP_PORT')   ? SMTP_PORT   : '',
    'SMTP_SECURE' => defined('SMTP_SECURE') ? SMTP_SECURE : '',
    'SMTP_USER'   => defined('SMTP_USER')   ? SMTP_USER   : '',
    'SMTP_FROM'   => defined('SMTP_FROM')   ? SMTP_FROM   : '',
];
foreach ($cfg as $k => $v) {
    chk($v !== '', $k, $v !== '' ? $v : '(vacío)');
}
$pass = defined('SMTP_PASS') ? SMTP_PASS : '';
chk($pass !== '', 'SMTP_PASS', $pass !== '' ? strlen($pass) . ' caracteres' : '(vacío)');
line();

// ── 2. El dominio remitente y su DNS ───────────────────────
$fromDomain = substr(strrchr($cfg['SMTP_FROM'] ?: '@', '@'), 1);
line("2. DNS DEL DOMINIO REMITENTE  ({$fromDomain})");

if ($fromDomain === '') {
    line('   No se puede determinar: SMTP_FROM está vacío.');
} elseif (!checkdnsrr($fromDomain, 'A') && !checkdnsrr($fromDomain, 'MX')) {
    chk(false, 'El dominio remitente existe en DNS', 'NO EXISTE (NXDOMAIN)');
    line();
    line('   CAUSA RAIZ. Un dominio remitente inexistente no se puede');
    line('   verificar ni recibir rebotes, asi que el servidor de destino');
    line('   RECHAZA el mensaje: no llega ni a la carpeta de spam. Y el aviso');
    line('   de rechazo tampoco vuelve, porque se manda a este mismo dominio.');
    line('   El resultado es que el correo desaparece sin dejar rastro.');
    line();
    line('   ACCION: publicar el registro A del subdominio antes que nada.');
    line('   Los de SPF y DKIM solo importan una vez que el dominio exista.');
} else {
    chk(true, 'El dominio remitente existe en DNS', 'resuelve');
    $txt = @dns_get_record($fromDomain, DNS_TXT) ?: [];
    $spf = '';
    foreach ($txt as $r) {
        if (stripos($r['txt'] ?? '', 'v=spf1') === 0) { $spf = $r['txt']; }
    }
    chk($spf !== '', 'Registro SPF publicado', $spf ?: 'FALTA — el correo caera en spam');

    $dkim = @dns_get_record('mail._domainkey.' . $fromDomain, DNS_TXT) ?: [];
    $dkimOk = false;
    foreach ($dkim as $r) {
        if (stripos($r['txt'] ?? '', 'v=DKIM1') !== false) { $dkimOk = true; }
    }
    chk($dkimOk, 'DKIM en mail._domainkey', $dkimOk ? 'publicado' : 'FALTA — el correo caera en spam');

    $mx = @dns_get_record($fromDomain, DNS_MX) ?: [];
    chk(!empty($mx), 'MX (para recibir rebotes)',
        $mx ? $mx[0]['target'] : 'falta — los rebotes se pierden, no impide enviar');
}
line();

// ── 3. Que el destino interno resuelva a Microsoft ─────────
line('3. RUTA HACIA EL DESTINO INTERNO');
$destDomain = 'atlantic-agency.example.com';
$mxDest = @dns_get_record($destDomain, DNS_MX) ?: [];
$target = $mxDest[0]['target'] ?? '';
chk(stripos($target, 'outlook.com') !== false || stripos($target, 'protection') !== false,
    "MX de {$destDomain}", $target ?: '(no resuelve)');
if ($fromDomain === '') {
    line('        (omitido) SMTP_FROM vacío.');
} else {
    chk($fromDomain !== $destDomain,
        'Remitente en subdominio distinto',
        $fromDomain === $destDomain
            ? 'MISMO DOMINIO: el hosting puede entregar localmente'
            : "{$fromDomain} != {$destDomain}, el servidor hará consulta MX");
}
line();

// ── 4. Envío real ──────────────────────────────────────────
$to = trim($_GET['to'] ?? '');
line('4. ENVÍO DE PRUEBA');
if ($to === '') {
    line('   (omitido) Agregá &to=direccion@ejemplo.com a la URL para enviar.');
} elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    line('   Dirección inválida: ' . $to);
} else {
    require_once __DIR__ . '/src/core/email.php';
    $svc  = new EmailService();
    $html = '<p>Prueba de envío desde el portal AMA.</p>'
          . '<p>Generada el ' . date('d/m/Y H:i:s') . '.</p>';
    $ok = $svc->send('Prueba de configuración SMTP', $html, $to, 'diagnostico');
    chk($ok, 'Envío a ' . $to, $ok ? 'aceptado por el servidor SMTP' : 'rechazado');

    line();
    line('   Últimas líneas del registro de envíos:');
    foreach (array_slice($svc->getAuditTail(3), -3) as $l) {
        line('     ' . (is_string($l) ? $l : json_encode($l, JSON_UNESCAPED_UNICODE)));
    }
    if ($ok) {
        line();
        line('   SIGUIENTE PASO: abrí el correo recibido y mirá el encabezado');
        line('   Authentication-Results. Debe decir spf=pass y dkim=pass.');
    }
}
line();

// ── 5. Destinatarios de las notificaciones ─────────────────
line('5. A DÓNDE VAN LAS NOTIFICACIONES');
line('   Cada aviso del portal se manda a la dirección de su área. Si alguna');
line('   no existe, ese aviso se pierde sin que nadie se entere.');
line();
require_once __DIR__ . '/src/core/email.php';
$d = (new EmailService())->getDiagnostics();
printf("  %-24s %s\n", 'NOTIFY_TO (respaldo)', $d['notify_to']);
printf("  %-24s %s\n", 'Reply-To', $d['reply_to']);
line();
foreach ($d['channels'] as $ch => $info) {
    $usaRespaldo = ($info['value'] === $d['notify_to']);
    printf("  %-24s %-42s %s\n", $ch, $info['value'], $usaRespaldo ? '<- usa el respaldo' : '');
}
line();
line('   Verificá una por una que existan y que alguien las lea.');
line('   Una casilla compartida de Microsoft 365 no consume licencia.');
line();
line('Recordá BORRAR este archivo del servidor.');
