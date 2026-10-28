<?php
/**
 * AMA — auth/callback.php (Admin)
 * Retorno de Microsoft Entra ID tras autenticar al usuario.
 *
 * Valida el token, resuelve el rol a partir de los App Roles, vincula o
 * aprovisiona la cuenta local y abre la sesión del portal.
 * Los tokens se descartan: en la sesión sólo quedan identidad y rol.
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__, 3) . '/src/core/entra_oidc.php';
require_once dirname(__DIR__, 3) . '/src/core/entra_roles.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

/**
 * Registra un evento en la auditoría del portal.
 *
 * @param  PDO|null   $pdo
 * @param  string     $accion
 * @param  string|null $detalles
 * @param  int|null   $userId
 * @return void
 */
function entra_audit(?PDO $pdo, string $accion, ?string $detalles = null, ?int $userId = null): void
{
    if (!$pdo instanceof PDO) {
        return;
    }
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO admin_audit_logs (user_id, accion, detalles, ip_address)
             VALUES (:uid, :accion, :detalles, :ip)"
        );
        $stmt->execute([
            'uid'      => $userId,
            'accion'   => $accion,
            'detalles' => $detalles !== null ? mb_substr($detalles, 0, 250) : null,
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (\PDOException $e) {
        // La auditoría no debe bloquear el login.
    }
}

/**
 * Corta el flujo devolviendo al login con un mensaje.
 *
 * @param  string $motivo Clave de mensaje para index.php.
 * @return void
 */
function entra_deny(string $motivo): void
{
    $_SESSION['entra_error'] = EntraOidc::lastError();
    header('Location: /gestion-interna-agencia/index.php?err=' . urlencode($motivo));
    exit;
}

// ── Errores devueltos por Microsoft ────────────────────────
// Ej: el usuario canceló, o Conditional Access bloqueó el acceso.

if (isset($_GET['error'])) {
    $desc = (string) ($_GET['error_description'] ?? '');
    error_log('[entra] callback con error: ' . substr((string)$_GET['error'], 0, 80) . ' — ' . substr($desc, 0, 200));

    // AADSTS50105: el usuario no está asignado a la aplicación.
    $noAsignado = strpos($desc, 'AADSTS50105') !== false;
    entra_audit($pdo ?? null, $noAsignado ? 'oidc_sin_asignacion' : 'oidc_rechazado',
        substr((string)$_GET['error'], 0, 80));

    $_SESSION['entra_error'] = $noAsignado
        ? 'Tu cuenta de Microsoft no tiene acceso asignado a este portal. Solicitalo al administrador.'
        : 'Microsoft no autorizó el acceso. Si el problema persiste, contactá al administrador.';
    header('Location: /gestion-interna-agencia/index.php?err=entra_denied');
    exit;
}

// ── Parámetros obligatorios ────────────────────────────────

$code  = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');

if ($code === '' || $state === '') {
    entra_audit($pdo ?? null, 'oidc_callback_incompleto');
    $_SESSION['entra_error'] = 'La respuesta de Microsoft está incompleta. Volvé a intentar.';
    header('Location: /gestion-interna-agencia/index.php?err=entra_denied');
    exit;
}

if (!EntraOidc::isConfigured()) {
    header('Location: /gestion-interna-agencia/index.php?err=entra_off');
    exit;
}

// ── Validación completa del token ──────────────────────────

$claims = EntraOidc::handleCallback($code, $state);

if ($claims === null) {
    entra_audit($pdo ?? null, EntraOidc::lastAuditCode());
    entra_deny('entra_invalid');
}

$oid   = (string) $claims['oid'];
$tid   = (string) $claims['tid'];
$email = strtolower(trim((string) ($claims['email'] ?? $claims['preferred_username'] ?? '')));
$nombre = trim((string) ($claims['name'] ?? '')) ?: ($email ?: 'Usuario');
$upn   = strtolower(trim((string) ($claims['preferred_username'] ?? '')));

// ── Rol efectivo desde los App Roles ───────────────────────

$rol = entra_map_role($claims['roles'] ?? null);

if ($rol === null) {
    $desconocidos = entra_unknown_roles($claims['roles'] ?? null);
    $detalle = 'oid: ' . substr($oid, 0, 36)
        . ($desconocidos ? ' — roles no reconocidos: ' . implode(',', array_slice($desconocidos, 0, 3)) : ' — sin roles');
    entra_audit($pdo ?? null, 'oidc_sin_rol', $detalle);

    $_SESSION['entra_error'] = 'Tu cuenta se autenticó correctamente pero no tiene un rol asignado en este portal. '
        . 'Solicitá al administrador que te asigne uno en Microsoft Entra ID.';
    header('Location: /gestion-interna-agencia/index.php?err=entra_norole');
    exit;
}

// ── Vinculación / aprovisionamiento de la cuenta local ─────

if (!isset($pdo) || !$pdo instanceof PDO) {
    error_log('[entra] sin conexión a BD en el callback');
    $_SESSION['entra_error'] = 'El sistema no está disponible en este momento.';
    header('Location: /gestion-interna-agencia/index.php?err=entra_db');
    exit;
}

try {
    // 1. Buscar por identidad federada (oid + tid). Es el único vínculo estable.
    $stmt = $pdo->prepare(
        "SELECT id, nombre, username, email, rol_portal, is_active, is_break_glass
           FROM usuarios_admin
          WHERE entra_oid = :oid AND entra_tid = :tid
          LIMIT 1"
    );
    $stmt->execute(['oid' => $oid, 'tid' => $tid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Primer login: vincular una cuenta existente por email.
    if (!$user && $email !== '') {
        $stmt = $pdo->prepare(
            "SELECT id, nombre, username, email, rol_portal, is_active, is_break_glass
               FROM usuarios_admin
              WHERE LOWER(email) = :email AND entra_oid IS NULL
              ORDER BY id ASC
              LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $link = $pdo->prepare(
                "UPDATE usuarios_admin
                    SET entra_oid = :oid, entra_tid = :tid, auth_provider = 'entra'
                  WHERE id = :id"
            );
            $link->execute(['oid' => $oid, 'tid' => $tid, 'id' => $user['id']]);
            entra_audit($pdo, 'oidc_cuenta_vinculada', 'email: ' . $email, (int) $user['id']);
        }
    }

    // 3. Sin cuenta previa: aprovisionar (JIT). Sin contraseña.
    if (!$user) {
        $baseUsername = $upn !== '' ? explode('@', $upn)[0] : ('u' . substr($oid, 0, 8));
        $baseUsername = preg_replace('/[^a-zA-Z0-9._-]/', '', $baseUsername);
        if ($baseUsername === '') {
            $baseUsername = 'u' . substr($oid, 0, 8);
        }
        $baseUsername = substr($baseUsername, 0, 50);

        // Garantizar unicidad del username.
        $username = $baseUsername;
        $sufijo = 1;
        $chk = $pdo->prepare("SELECT 1 FROM usuarios_admin WHERE username = :u LIMIT 1");
        $chk->execute(['u' => $username]);
        while ($chk->fetchColumn()) {
            $username = substr($baseUsername, 0, 52) . '_' . $sufijo;
            $sufijo++;
            $chk->execute(['u' => $username]);
        }

        $ins = $pdo->prepare(
            "INSERT INTO usuarios_admin
                (nombre, username, email, entra_oid, entra_tid, password_hash,
                 rol_portal, auth_provider, is_break_glass, is_active, creado_en)
             VALUES
                (:nombre, :username, :email, :oid, :tid, NULL,
                 :rol, 'entra', 0, 1, NOW())"
        );
        $ins->execute([
            'nombre'   => mb_substr($nombre, 0, 100),
            'username' => $username,
            'email'    => mb_substr($email !== '' ? $email : ($upn ?: $oid . '@sin-email'), 0, 100),
            'oid'      => $oid,
            'tid'      => $tid,
            'rol'      => $rol,
        ]);

        $newId = (int) $pdo->lastInsertId();
        entra_audit($pdo, 'oidc_usuario_creado', 'username: ' . $username . ' rol: ' . $rol, $newId);

        $user = [
            'id' => $newId, 'nombre' => mb_substr($nombre, 0, 100), 'username' => $username,
            'email' => $email, 'rol_portal' => $rol, 'is_active' => 1, 'is_break_glass' => 0,
        ];
    }

    // 4. Bloqueo local: corta aunque Entra haya autenticado.
    if ((int) $user['is_active'] === 0) {
        entra_audit($pdo, 'oidc_cuenta_bloqueada', 'oid: ' . substr($oid, 0, 36), (int) $user['id']);
        $_SESSION['entra_error'] = 'Esta cuenta fue desactivada por un administrador.';
        header('Location: /gestion-interna-agencia/index.php?err=entra_blocked');
        exit;
    }

    // 5. Sincronizar el reflejo local del rol y los datos de contacto.
    $upd = $pdo->prepare(
        "UPDATE usuarios_admin
            SET rol_portal = :rol,
                nombre = :nombre,
                auth_provider = 'entra',
                ultimo_login = NOW()
          WHERE id = :id"
    );
    $upd->execute([
        'rol'    => $rol,
        'nombre' => mb_substr($nombre, 0, 100),
        'id'     => $user['id'],
    ]);

    if ($user['rol_portal'] !== $rol) {
        entra_audit($pdo, 'oidc_rol_actualizado',
            'de ' . $user['rol_portal'] . ' a ' . $rol, (int) $user['id']);
    }

} catch (\PDOException $e) {
    error_log('[entra] error de BD en callback: ' . $e->getMessage());
    $_SESSION['entra_error'] = 'El sistema no está disponible en este momento.';
    header('Location: /gestion-interna-agencia/index.php?err=entra_db');
    exit;
}

// ── Sesión del portal ──────────────────────────────────────
// A partir de acá el token ya cumplió su función y se descarta.

$returnTo = entra_safe_redirect($claims['__return_to'] ?? null);

session_regenerate_id(true);

$_SESSION['last_regeneration'] = time();
$_SESSION['last_activity']     = time();
$_SESSION['fingerprint']       = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

$_SESSION['user_id']       = (int) $user['id'];
$_SESSION['user_name']     = $user['nombre'];
$_SESSION['user_role']     = $rol;
$_SESSION['auth_provider'] = 'entra';
$_SESSION['entra_oid']     = $oid;
$_SESSION['login_ip']      = $ip;

unset($_SESSION['entra_error'], $_SESSION['entra_audit_code']);

entra_audit($pdo, 'login_exitoso', 'via Microsoft Entra ID (' . $rol . ')', (int) $user['id']);

header('Location: ' . $returnTo);
exit;
