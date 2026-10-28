<?php
/**
 * AMA — tipo_cambio.php (core)
 * Regla de negocio única del tipo de cambio.
 *
 * La vigencia de una cotización (horario comercial + carga del día) es la
 * misma la consulte quien la consulte: la web, el bot de correo o cualquier
 * canal que se agregue después. Vivía dentro del endpoint HTTP, así que un
 * segundo consumidor obligaba a copiarla, y dos copias de una regla horaria
 * terminan contestando distinto a la misma hora.
 */

// Defensa en profundidad: archivo solo para inclusión, nunca acceso web directo.
if (isset($_SERVER['SCRIPT_FILENAME']) && @realpath($_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

/** Horario comercial en el que la cotización tiene vigencia. */
const TC_HORA_DESDE = 9;
const TC_HORA_HASTA = 17;

/**
 * Horario comercial en el formato que consume el frontend.
 *
 * @return array{desde: string, hasta: string, dias: string}
 */
function tcHorario(): array {
    return [
        'desde' => sprintf('%02d:00', TC_HORA_DESDE),
        'hasta' => sprintf('%02d:00', TC_HORA_HASTA),
        'dias'  => 'L-V',
    ];
}

/**
 * Indica si el momento dado cae dentro del horario comercial.
 *
 * El reloj que vale es el de la agencia (America/Argentina/Buenos_Aires,
 * fijado en config.php), nunca el del visitante ni el del servidor de turno.
 *
 * @param  int|null $timestamp Momento a evaluar. Por defecto, ahora.
 * @return bool
 */
function tcEnHorario(?int $timestamp = null): bool {
    $ts = $timestamp ?? time();
    $dia  = (int)date('N', $ts); // 1 = Lunes, 7 = Domingo
    $hora = (int)date('H', $ts);

    return $dia < 6 && $hora >= TC_HORA_DESDE && $hora < TC_HORA_HASTA;
}

/**
 * Datos que no dependen del horario: los datos bancarios no vencen a las
 * 17:00 y son justo lo que necesita quien entra de noche a preparar una
 * transferencia. Se devuelven en todos los estados, con la cotización
 * afuera, que es lo único que sí tiene vigencia horaria.
 *
 * @param  PDO|null $pdo
 * @return string HTML de los datos bancarios, o cadena vacía si no hay.
 */
function tcDatosBancarios(?PDO $pdo): string {
    if (!$pdo) {
        return '';
    }
    try {
        $row = $pdo->query("SELECT contenido_html FROM bank_details WHERE id = 1")->fetch();
        return $row ? (string)$row['contenido_html'] : '';
    } catch (\PDOException $e) {
        return '';
    }
}

/**
 * Estado actual del tipo de cambio, resuelto de punta a punta.
 *
 * Estados posibles:
 *   - `cerrado`       → fuera del horario comercial (L-V 09:00-17:00).
 *   - `no_disponible` → en horario, pero Caja todavía no cargó el dólar de hoy.
 *   - `ok`            → hay cotización vigente.
 *   - `error`         → la base no respondió. Quien consuma esto NO debe
 *                       inventar una respuesta: sin dato no hay dato.
 *
 * @param  PDO|null $pdo
 * @param  int|null $ahora Momento a evaluar. Por defecto, ahora. Se inyecta
 *                         desde los tests para no depender del reloj real.
 * @return array{status: string, mensaje: string, dolar_valor: ?string,
 *               euro_valor: ?string, ultima_actualizacion: ?string,
 *               datos_bancarios: string, hora_local: string,
 *               fecha_local: string, horario: array}
 */
function tcEstadoActual(?PDO $pdo, ?int $ahora = null): array {
    $ts = $ahora ?? time();

    $base = [
        'status'               => 'error',
        'mensaje'              => '',
        'dolar_valor'          => null,
        'euro_valor'           => null,
        'ultima_actualizacion' => null,
        'datos_bancarios'      => '',
        'hora_local'           => date('H:i', $ts),
        'fecha_local'          => date('Y-m-d', $ts),
        'horario'              => tcHorario(),
    ];

    // 1. Horario comercial. Se evalúa antes que la base: fuera de hora la
    //    cotización no se publica aunque esté cargada.
    if (!tcEnHorario($ts)) {
        return array_merge($base, [
            'status'          => 'cerrado',
            'mensaje'         => 'El servicio de consulta de tipo de cambio opera de lunes a viernes de 09:00 a 17:00 hs.',
            'datos_bancarios' => tcDatosBancarios($pdo),
        ]);
    }

    if (!$pdo) {
        return array_merge($base, ['mensaje' => 'Error de conexión con la base de datos financiera.']);
    }

    try {
        // 2. Último USD cargado hoy. Sin dólar no hay cotización: el euro es opcional.
        $stmtUSD = $pdo->query(
            "SELECT valor, creado_en FROM exchange_rates
             WHERE moneda = 'USD' AND fecha_cotizacion = CURDATE()
             ORDER BY creado_en DESC LIMIT 1"
        );
        $usd = $stmtUSD->fetch();

        if (!$usd) {
            return array_merge($base, [
                'status'          => 'no_disponible',
                'mensaje'         => 'Tipo de cambio para el día de la fecha aún no disponible.',
                'datos_bancarios' => tcDatosBancarios($pdo),
            ]);
        }

        $stmtEUR = $pdo->query(
            "SELECT valor FROM exchange_rates
             WHERE moneda = 'EUR' AND fecha_cotizacion = CURDATE()
             ORDER BY creado_en DESC LIMIT 1"
        );
        $eur = $stmtEUR->fetch();

        return array_merge($base, [
            'status'               => 'ok',
            'mensaje'              => 'Cotización válida.',
            'dolar_valor'          => $usd['valor'],
            'euro_valor'           => $eur ? $eur['valor'] : '',
            'ultima_actualizacion' => $usd['creado_en'],
            'datos_bancarios'      => tcDatosBancarios($pdo),
        ]);

    } catch (\PDOException $e) {
        return array_merge($base, ['mensaje' => 'Error de conexión con la base de datos financiera.']);
    }
}
