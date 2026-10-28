<?php
/**
 * AMA — auth/login.php (Admin)
 * Inicia el flujo OpenID Connect contra Microsoft Entra ID.
 *
 * No recibe credenciales: sólo genera la transacción de login
 * (state, nonce, PKCE) y redirige a Microsoft.
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__, 3) . '/src/core/entra_oidc.php';
require_once dirname(__DIR__, 3) . '/src/core/entra_roles.php';

// Ya autenticado: no rehacer el flujo.
if (is_logged_in()) {
    header('Location: /gestion-interna-agencia/dashboard.php');
    exit;
}

// Entra no configurado: volver al login local con aviso.
if (!EntraOidc::isConfigured()) {
    header('Location: /gestion-interna-agencia/index.php?err=entra_off');
    exit;
}

// Destino posterior al login, validado contra lista blanca.
$returnTo = entra_safe_redirect($_GET['return_to'] ?? null);

$url = EntraOidc::buildAuthorizationUrl($returnTo);

// La sesión debe estar escrita antes de salir del proceso.
session_write_close();

header('Location: ' . $url, true, 302);
exit;
