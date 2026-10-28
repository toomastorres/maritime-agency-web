<?php
require_once __DIR__ . '/../src/core/entra_roles.php';

$fail = 0;
function check($name, $got, $exp) {
    global $fail;
    $ok = ($got === $exp);
    if (!$ok) $fail++;
    printf("  %s  %-46s %s\n", $ok ? 'PASS' : 'FAIL', $name, var_export($got, true));
}

echo "── Mapeo de App Roles ──\n";
check('Portal.Caja -> caja',                 entra_map_role(['Portal.Caja']), 'caja');
check('Portal.CS -> cs',                     entra_map_role(['Portal.CS']), 'cs');
check('Portal.RRHH -> rrhh',                 entra_map_role(['Portal.RRHH']), 'rrhh');
check('Portal.Documentacion -> documentacion', entra_map_role(['Portal.Documentacion']), 'documentacion');
check('Portal.AdminGlobal -> admin_global',  entra_map_role(['Portal.AdminGlobal']), 'admin_global');

echo "\n── Fail-closed ──\n";
check('Sin claim roles (null)',              entra_map_role(null), null);
check('Array vacio',                         entra_map_role([]), null);
check('String en vez de array',              entra_map_role('Portal.AdminGlobal'), null);
check('Rol inventado',                       entra_map_role(['Portal.Inventado']), null);
check('Rol vacio',                           entra_map_role(['']), null);
check('Case distinto NO matchea',            entra_map_role(['portal.adminglobal']), null);
check('Valor no-string se ignora',           entra_map_role([123, null, true]), null);

echo "\n── Prioridad con roles multiples ──\n";
check('CS + AdminGlobal -> admin_global',    entra_map_role(['Portal.CS','Portal.AdminGlobal']), 'admin_global');
check('AdminGlobal primero -> admin_global', entra_map_role(['Portal.AdminGlobal','Portal.Caja']), 'admin_global');
check('Caja + RRHH -> rrhh (mayor prio)',    entra_map_role(['Portal.Caja','Portal.RRHH']), 'rrhh');
check('Desconocido + Caja -> caja',          entra_map_role(['Portal.Ninguno','Portal.Caja']), 'caja');
check('Espacios alrededor se toleran',       entra_map_role(['  Portal.Caja  ']), 'caja');

echo "\n── Roles no reconocidos (aviso al admin) ──\n";
check('Detecta desconocidos',                entra_unknown_roles(['Portal.Caja','Portal.Raro']), ['Portal.Raro']);
check('Ninguno desconocido',                 entra_unknown_roles(['Portal.Caja']), []);
check('Null seguro',                         entra_unknown_roles(null), []);

echo "\n── Redirect seguro (backoffice) ──\n";
$RT = '/gestion-interna-agencia/dashboard.php';
check('Ruta interna valida',                 entra_safe_redirect('/gestion-interna-agencia/dashboard.php?section=caja'), '/gestion-interna-agencia/dashboard.php?section=caja');
check('Null -> fallback',                    entra_safe_redirect(null), $RT);
check('//evil.com',                          entra_safe_redirect('//evil.com'), $RT);
check('https://evil.com',                    entra_safe_redirect('https://evil.com'), $RT);
check('javascript:',                         entra_safe_redirect('javascript:alert(1)'), $RT);
check('Traversal ..',                        entra_safe_redirect('/gestion-interna-agencia/../../etc/passwd'), $RT);
check('Traversal %2e%2e',                    entra_safe_redirect('/gestion-interna-agencia/%2e%2e/x'), $RT);
check('CRLF injection',                      entra_safe_redirect("/gestion-interna-agencia/x\r\nSet-Cookie: a=b"), $RT);
check('Zona publica NO permitida',           entra_safe_redirect('/servicios'), $RT);
check('Prefijo parecido rechazado',          entra_safe_redirect('/gestion-interna-agencia-falso/x'), $RT);

echo $fail === 0 ? "\nTODOS OK\n" : "\n$fail FALLOS\n";
exit($fail === 0 ? 0 : 1);
