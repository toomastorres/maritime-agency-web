<?php
/**
 * AMA — password_policy.php
 * Validador central de contraseñas según NIST SP 800-63B (Digital Identity Guidelines).
 *
 * Reglas aplicadas:
 *   1. Longitud mínima 12 caracteres (NIST recomienda mínimo 8, elegimos 12 para portal admin).
 *   2. Longitud máxima 128 caracteres (corte sano para evitar DoS por bcrypt).
 *   3. Sin requisitos de complejidad obligatoria (mayúscula/número/símbolo) — NIST lo desaconseja.
 *   4. Rechazo de passwords del top-10000 más comunes (blacklist local).
 *   5. Rechazo de passwords que contengan el username o el local-part del email (>= 4 chars).
 *   6. Rechazo de secuencias triviales obvias (12345, qwerty, abcdef, password, etc).
 *
 * Uso:
 *   require_once __DIR__ . '/password_policy.php';
 *   $errors = validate_password_strength($newPassword, $username, $email);
 *   if (!empty($errors)) {
 *       // mostrar $errors[0] al usuario, todos están en español listos para imprimir
 *   }
 */

if (!defined('AMA_PASSWORD_POLICY_LOADED')) {
    define('AMA_PASSWORD_POLICY_LOADED', true);
    define('AMA_PASSWORD_MIN_LENGTH', 12);
    define('AMA_PASSWORD_MAX_LENGTH', 128);
    define('AMA_PASSWORD_BLACKLIST_PATH', dirname(__DIR__) . '/data/common-passwords.txt');
}

/**
 * Carga la blacklist de passwords comunes en memoria. Cachea estática para
 * no leer el archivo más de una vez por request.
 *
 * @return array<string,bool> Set asociativo (lookup O(1) con isset()).
 */
function load_password_blacklist(): array
{
    static $blacklist = null;
    if ($blacklist !== null) {
        return $blacklist;
    }

    $blacklist = [];
    $path = AMA_PASSWORD_BLACKLIST_PATH;

    if (!is_readable($path)) {
        // Si la blacklist no está disponible, fallar abierto: el resto de las
        // reglas (longitud, secuencias, contención de username) siguen activas.
        return $blacklist;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $blacklist;
    }

    foreach ($lines as $line) {
        $word = trim($line);
        if ($word !== '') {
            $blacklist[strtolower($word)] = true;
        }
    }

    return $blacklist;
}

/**
 * Valida la fuerza de una contraseña según NIST 800-63B.
 *
 * @param string      $password Contraseña en claro a validar.
 * @param string|null $username Username del usuario (opcional, para evitar inclusión).
 * @param string|null $email    Email del usuario (opcional, se usa el local-part).
 * @return string[] Array de mensajes de error en español. Vacío = válida.
 */
function validate_password_strength(string $password, ?string $username = null, ?string $email = null): array
{
    $errors = [];

    // ── 1. Longitud ──────────────────────────────────────────
    $len = strlen($password);
    if ($len < AMA_PASSWORD_MIN_LENGTH) {
        $errors[] = 'La contraseña debe tener al menos ' . AMA_PASSWORD_MIN_LENGTH . ' caracteres.';
        return $errors; // corto-circuito: no tiene sentido seguir validando una cadena demasiado corta
    }
    if ($len > AMA_PASSWORD_MAX_LENGTH) {
        $errors[] = 'La contraseña no puede superar los ' . AMA_PASSWORD_MAX_LENGTH . ' caracteres.';
        return $errors;
    }

    $passwordLower = strtolower($password);

    // ── 2. Blacklist de passwords comunes ────────────────────
    $blacklist = load_password_blacklist();
    if (isset($blacklist[$passwordLower])) {
        $errors[] = 'Esta contraseña es demasiado común y aparece en listas públicas de filtraciones. Elegí otra.';
        return $errors;
    }

    // ── 3. No contener username (si tiene >= 4 chars) ────────
    if ($username !== null) {
        $usernameLower = strtolower(trim($username));
        if (strlen($usernameLower) >= 4 && strpos($passwordLower, $usernameLower) !== false) {
            $errors[] = 'La contraseña no puede contener tu nombre de usuario.';
        }
    }

    // ── 4. No contener local-part del email (si tiene >= 4 chars) ──
    if ($email !== null && strpos($email, '@') !== false) {
        $localPart = strtolower(trim(substr($email, 0, strpos($email, '@'))));
        if (strlen($localPart) >= 4 && strpos($passwordLower, $localPart) !== false) {
            $errors[] = 'La contraseña no puede contener la parte local de tu correo electrónico.';
        }
    }

    // ── 5. Secuencias triviales obvias ───────────────────────
    $trivialPatterns = [
        '/0123456789/',
        '/123456/',
        '/abcdef/',
        '/qwerty/',
        '/asdfgh/',
        '/zxcvbn/',
        '/password/',
        '/contrase[ñn]a/',
        '/AMA/',
        '/admin/',
    ];
    foreach ($trivialPatterns as $pattern) {
        if (preg_match($pattern, $passwordLower)) {
            $errors[] = 'La contraseña contiene una secuencia o palabra demasiado predecible (ej: 123456, qwerty, password, AMA). Elegí algo más original.';
            break;
        }
    }

    // ── 6. Repetición trivial (mismo carácter >= 5 veces seguidas) ──
    if (preg_match('/(.)\1{4,}/', $password)) {
        $errors[] = 'La contraseña no puede contener el mismo carácter repetido 5 o más veces seguidas.';
    }

    return $errors;
}

/**
 * Texto de ayuda en español para mostrar debajo del campo de contraseña.
 * Mantenido en sincronía con las reglas del validador.
 *
 * @return string
 */
function password_policy_help_text(): string
{
    return 'Mínimo ' . AMA_PASSWORD_MIN_LENGTH . ' caracteres. No puede contener tu usuario, tu correo, '
         . 'palabras comunes (password, qwerty, 123456, AMA) ni caracteres repetidos. '
         . 'Recomendamos una frase larga fácil de recordar (ej: "AyerLloviaEnElPuerto2026").';
}
