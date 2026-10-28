<?php
/**
 * AMA — tc_mailbot.php
 * Bot de respuesta automática del buzón de tipo de cambio.
 *
 * Lee los mensajes sin leer de tc@atlantic-agency.example.com, resuelve el estado
 * del tipo de cambio con la MISMA regla que la web (core/tipo_cambio.php) y
 * responde en el hilo con la cotización, con "todavía no se cargó" o con
 * "fuera de horario".
 *
 * Reglas de convivencia con las personas que atienden el buzón:
 *
 *   - Solo se marcan como leídos los mensajes que el bot efectivamente
 *     contestó. Todo lo que omite queda SIN LEER, para que lo vea alguien.
 *   - Contesta una sola vez por hilo. Si el cliente responde de vuelta, ese
 *     mensaje queda sin leer y lo atiende una persona.
 *   - Si la base no responde, no contesta nada. Un silencio es recuperable;
 *     una cotización inventada, no.
 */

// Defensa en profundidad: archivo solo para inclusión, nunca acceso web directo.
if (isset($_SERVER['SCRIPT_FILENAME']) && @realpath($_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/tipo_cambio.php';
require_once __DIR__ . '/graph_mail.php';
require_once __DIR__ . '/email.php';

class TcMailBot
{
    /**
     * Remitentes que nunca deben recibir una respuesta automática.
     *
     * Se compara por palabra y no por substring a propósito: "abuse" como
     * substring también matchea el apellido Abusero, y dejar sin contestar
     * a un cliente real es peor que mandarle un mail a una casilla que no
     * lee nadie (ese rebote, además, lo filtra motivoAutomatico()).
     */
    const REGEX_LOCAL_PROHIBIDO = '/(^|[^a-z0-9])(no.?reply|do.?not.?reply|postmaster'
        . '|mailer.?daemon|bounces?|abuse|undisclosed.?recipients)([^a-z0-9]|$)/';

    /**
     * Asuntos que delatan una respuesta automática del otro lado.
     * Contestarlos es la forma más rápida de armar un bucle infinito.
     */
    const ASUNTOS_AUTOMATICOS = [
        'automatic reply', 'auto reply', 'autoreply', 'out of office',
        'respuesta automática', 'respuesta automatica', 'fuera de la oficina',
        'undeliverable', 'delivery status notification', 'mail delivery failed',
        'returned mail', 'correo no entregado', 'risposta automatica',
        'resposta automática', 'resposta automatica',
    ];

    /** @var PDO */
    private $pdo;

    /** @var GraphMail */
    private $graph;

    /** @var array */
    private $op;

    /** @var string */
    private $logFile;

    /**
     * @param PDO       $pdo
     * @param GraphMail $graph
     * @param array     $opciones {
     *     @var int  max_mensajes       Máximo de mensajes por corrida.
     *     @var int  max_por_remitente  Respuestas por remitente por día.
     *     @var int  antiguedad_horas   No contestar mensajes más viejos que esto.
     *     @var bool simulacro          Procesa y reporta, pero no toca el buzón ni la base.
     *     @var string url_publica      Página de tipo de cambio.
     * }
     */
    public function __construct(PDO $pdo, GraphMail $graph, array $opciones = [])
    {
        $this->pdo   = $pdo;
        $this->graph = $graph;

        $this->op = array_merge([
            'max_mensajes'      => 25,
            'max_por_remitente' => 3,
            'antiguedad_horas'  => 12,
            'simulacro'         => false,
            'url_publica'       => 'https://atlantic-agency.example.com/tipo-de-cambio',
        ], $opciones);

        $this->logFile = dirname(__DIR__, 2) . '/src/data/logs/app.log';
    }

    /**
     * Log técnico → app.log, mismo patrón que EmailService y database.php.
     *
     * @param  string $msg
     * @return void
     */
    private function log(string $msg): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        @error_log('[' . date('Y-m-d H:i:s') . '] TcMailBot: ' . $msg . PHP_EOL, 3, $this->logFile);
    }

    // ── Corrida ────────────────────────────────────────────────

    /**
     * Procesa la bandeja de entrada.
     *
     * @return array Resumen de la corrida.
     */
    public function correr(): array
    {
        $resumen = [
            'ok'          => true,
            'simulacro'   => (bool)$this->op['simulacro'],
            'revisados'   => 0,
            'respondidos' => 0,
            'omitidos'    => 0,
            'errores'     => 0,
            'estado_tc'   => null,
            'detalle'     => [],
        ];

        if (!$this->graph->estaConfigurado()) {
            $this->log('Configuración incompleta — faltan variables TC_MAIL_* en el .env.');
            return array_merge($resumen, ['ok' => false, 'error' => 'Bot no configurado.']);
        }

        // El estado se resuelve UNA vez por corrida: todos los mensajes de la
        // misma tanda tienen que recibir la misma respuesta.
        $estado = tcEstadoActual($this->pdo);
        $resumen['estado_tc'] = $estado['status'];

        if ($estado['status'] === 'error') {
            // Sin base no hay respuesta posible. Los mensajes quedan sin leer
            // y se contestan en la corrida siguiente.
            $this->log('Base de datos no disponible — no se responde ningún mensaje en esta corrida.');
            return array_merge($resumen, ['ok' => false, 'error' => 'Tipo de cambio no resoluble.']);
        }

        $mensajes = $this->graph->mensajesNoLeidos((int)$this->op['max_mensajes']);
        if ($mensajes === null) {
            $this->log('No se pudo leer el buzón: ' . ($this->graph->ultimoError() ?? 'sin detalle'));
            return array_merge($resumen, ['ok' => false, 'error' => 'No se pudo leer el buzón.']);
        }

        foreach ($mensajes as $msg) {
            $resumen['revisados']++;
            $r = $this->procesar($msg, $estado);

            $resumen['detalle'][] = $r;
            if ($r['accion'] === 'respondido') {
                $resumen['respondidos']++;
            } elseif ($r['accion'] === 'error') {
                $resumen['errores']++;
            } else {
                $resumen['omitidos']++;
            }
        }

        $this->log(sprintf(
            'Corrida%s: %d revisados, %d respondidos, %d omitidos, %d errores (estado: %s).',
            $this->op['simulacro'] ? ' (SIMULACRO)' : '',
            $resumen['revisados'], $resumen['respondidos'],
            $resumen['omitidos'], $resumen['errores'], $estado['status']
        ));

        return $resumen;
    }

    /**
     * Decide y ejecuta la acción sobre un mensaje.
     *
     * @param  array $msg    Mensaje de Graph.
     * @param  array $estado Salida de tcEstadoActual().
     * @return array{accion: string, motivo: string, remitente: string, asunto: string}
     */
    private function procesar(array $msg, array $estado): array
    {
        $remitente = strtolower(trim(
            $msg['from']['emailAddress']['address']
            ?? $msg['sender']['emailAddress']['address']
            ?? ''
        ));
        $asunto   = (string)($msg['subject'] ?? '');
        $clave    = $this->claveMensaje($msg);
        $convId   = (string)($msg['conversationId'] ?? '');

        $salida = function (string $accion, string $motivo) use ($remitente, $asunto) {
            return ['accion' => $accion, 'motivo' => $motivo, 'remitente' => $remitente, 'asunto' => $asunto];
        };

        // ── Filtros que no necesitan ni base ni encabezados ────

        if ($remitente === '') {
            return $salida('omitido', 'remitente_vacio');
        }
        if ($remitente === strtolower($this->graph->buzon())) {
            return $salida('omitido', 'mensaje_propio');
        }
        if (!empty($msg['isDraft'])) {
            return $salida('omitido', 'borrador');
        }

        $local = substr($remitente, 0, strpos($remitente, '@') ?: strlen($remitente));
        if (preg_match(self::REGEX_LOCAL_PROHIBIDO, $local)) {
            return $salida('omitido', 'remitente_automatico');
        }

        $asuntoNorm = mb_strtolower($asunto, 'UTF-8');
        foreach (self::ASUNTOS_AUTOMATICOS as $patron) {
            if (strpos($asuntoNorm, $patron) !== false) {
                return $salida('omitido', 'asunto_automatico');
            }
        }

        // Backlog: al encender el bot (o tras una caída larga) la bandeja
        // puede tener cientos de mensajes viejos. Contestarlos todos de golpe
        // es peor que no contestarlos.
        $recibido = strtotime((string)($msg['receivedDateTime'] ?? '')) ?: 0;
        $limite   = time() - ((int)$this->op['antiguedad_horas'] * 3600);
        if ($recibido > 0 && $recibido < $limite) {
            return $salida('omitido', 'mensaje_antiguo');
        }

        // ── Estado en la bitácora ──────────────────────────────

        if ($this->yaProcesado($clave)) {
            return $salida('omitido', 'ya_procesado');
        }
        if ($convId !== '' && $this->hiloYaRespondido($convId)) {
            return $salida('omitido', 'hilo_ya_respondido');
        }
        if ($this->superaLimiteDiario($remitente)) {
            return $salida('omitido', 'limite_remitente');
        }

        // ── Encabezados: la prueba dura de un correo automático ─

        $headers = $this->graph->encabezados($msg['id'] ?? '');
        $motivoAuto = $this->motivoAutomatico($headers);
        if ($motivoAuto !== null) {
            return $salida('omitido', $motivoAuto);
        }

        // ── Respuesta ──────────────────────────────────────────

        if ($this->op['simulacro']) {
            // Sin escribir en la base: una fila acá consumiría la clave única
            // y el mensaje ya no se contestaría en la corrida real.
            return $salida('simulacro', 'responderia_' . $estado['status']);
        }

        // Se reserva el mensaje ANTES de enviar. Si dos corridas del cron se
        // superponen, la segunda choca contra la clave única y no duplica.
        if (!$this->reservar($clave, $convId, $msg, $remitente, $asunto, $estado['status'])) {
            return $salida('omitido', 'ya_procesado');
        }

        $html = $this->construirRespuesta($estado, $msg);
        $enviado = $this->graph->responder((string)$msg['id'], $html);

        if (!$enviado) {
            $this->cerrar($clave, 'error', substr((string)$this->graph->ultimoError(), 0, 190));
            $this->log('Fallo al responder a ' . $remitente . ': ' . ($this->graph->ultimoError() ?? 'sin detalle'));
            return $salida('error', 'envio_fallido');
        }

        // Marcar como leído es lo último: si falla, el peor caso es un mensaje
        // contestado que sigue en negrita, no una respuesta duplicada.
        $this->graph->marcarLeido((string)$msg['id']);
        $this->cerrar($clave, 'respondido', null);

        return $salida('respondido', $estado['status']);
    }

    // ── Detección de correo automático ─────────────────────────

    /**
     * Revisa los encabezados SMTP en busca de señales de correo automático.
     *
     * @param  array $h Encabezados en minúscula.
     * @return string|null Motivo, o null si parece un mensaje humano.
     */
    private function motivoAutomatico(array $h): ?string
    {
        // RFC 3834: el estándar para no contestarle a un robot.
        $autoSubmitted = strtolower(trim($h['auto-submitted'] ?? ''));
        if ($autoSubmitted !== '' && strpos($autoSubmitted, 'no') !== 0) {
            return 'auto_submitted';
        }

        if (isset($h['x-ama-autoreply'])) {
            return 'respuesta_propia';
        }
        if (isset($h['x-autoreply']) || isset($h['x-autorespond']) || isset($h['x-auto-response-suppress'])) {
            return 'auto_reply_header';
        }
        if (isset($h['list-id']) || isset($h['list-unsubscribe'])) {
            return 'lista_distribucion';
        }

        $precedence = strtolower(trim($h['precedence'] ?? ''));
        if (in_array($precedence, ['bulk', 'list', 'junk'], true)) {
            return 'precedence_' . $precedence;
        }

        // Return-Path vacío = rebote (RFC 5321): jamás se contesta.
        if (isset($h['return-path']) && in_array(trim($h['return-path']), ['<>', ''], true)) {
            return 'rebote';
        }

        return null;
    }

    // ── Bitácora ───────────────────────────────────────────────

    /**
     * Identificador estable del mensaje. El Message-ID sobrevive a cambios
     * de carpeta; el id de Graph no siempre.
     *
     * @param  array $msg
     * @return string
     */
    private function claveMensaje(array $msg): string
    {
        $mid = trim((string)($msg['internetMessageId'] ?? ''));
        if ($mid !== '') {
            return substr($mid, 0, 255);
        }
        return 'graph-sha1:' . sha1((string)($msg['id'] ?? uniqid('', true)));
    }

    /**
     * @param  string $clave
     * @return bool
     */
    private function yaProcesado(string $clave): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM tc_mail_log WHERE internet_message_id = ? LIMIT 1");
        $stmt->execute([$clave]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * @param  string $convId
     * @return bool
     */
    private function hiloYaRespondido(string $convId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM tc_mail_log WHERE conversation_id = ? AND accion IN ('respondido', 'en_proceso') LIMIT 1"
        );
        $stmt->execute([$convId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * @param  string $remitente
     * @return bool
     */
    private function superaLimiteDiario(string $remitente): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM tc_mail_log
             WHERE remitente = ? AND accion IN ('respondido', 'en_proceso') AND DATE(creado_en) = CURDATE()"
        );
        $stmt->execute([$remitente]);
        return (int)$stmt->fetchColumn() >= (int)$this->op['max_por_remitente'];
    }

    /**
     * Reserva el mensaje. La clave única hace de cerrojo entre corridas.
     *
     * @return bool false si otra corrida se lo quedó primero.
     */
    private function reservar(
        string $clave,
        string $convId,
        array $msg,
        string $remitente,
        string $asunto,
        string $estadoTc
    ): bool {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO tc_mail_log
                 (internet_message_id, conversation_id, graph_message_id, remitente, asunto,
                  estado_tc, accion, recibido_en)
                 VALUES (?, ?, ?, ?, ?, ?, 'en_proceso', ?)"
            );
            $recibido = strtotime((string)($msg['receivedDateTime'] ?? ''));
            $stmt->execute([
                $clave,
                $convId !== '' ? substr($convId, 0, 255) : null,
                substr((string)($msg['id'] ?? ''), 0, 512),
                substr($remitente, 0, 320),
                $asunto !== '' ? mb_substr($asunto, 0, 255) : null,
                $estadoTc,
                $recibido ? date('Y-m-d H:i:s', $recibido) : null,
            ]);
            return true;
        } catch (\PDOException $e) {
            $this->log('Reservar falló para ' . $clave . ': ' . $e->getMessage());
            // Violación de clave única: otra corrida ya lo tomó. No es un error.
            return false;
        }
    }

    /**
     * Cierra la fila reservada con el desenlace real.
     *
     * @return void
     */
    private function cerrar(string $clave, string $accion, ?string $motivo): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE tc_mail_log SET accion = ?, motivo = ? WHERE internet_message_id = ?"
            );
            $stmt->execute([$accion, $motivo, $clave]);
        } catch (\PDOException $e) {
            $this->log('No se pudo cerrar la bitácora de ' . $clave . ': ' . $e->getMessage());
        }
    }

    // ── Cuerpo de la respuesta ─────────────────────────────────

    /**
     * Arma el HTML de la respuesta según el estado del tipo de cambio.
     *
     * @param  array $estado Salida de tcEstadoActual().
     * @param  array $msg    Mensaje original (para citar la consulta).
     * @return string
     */
    private function construirRespuesta(array $estado, array $msg): string
    {
        $svc  = new EmailService();
        $url  = htmlspecialchars($this->op['url_publica'], ENT_QUOTES, 'UTF-8');
        $hoy  = date('d/m/Y');
        $hdesde = $estado['horario']['desde'];
        $hhasta = $estado['horario']['hasta'];

        if ($estado['status'] === 'ok') {
            $titulo = 'Tipo de cambio del ' . $hoy;

            $filas = '<p class="rate-row"><span class="rate-label">Dólar (USD):</span> '
                . '<span class="rate-value">$ ' . number_format((float)$estado['dolar_valor'], 2, ',', '.') . '</span></p>';

            if ($estado['euro_valor'] !== null && $estado['euro_valor'] !== '') {
                $filas .= '<p class="rate-row"><span class="rate-label">Euro (EUR):</span> '
                    . '<span class="rate-value">$ ' . number_format((float)$estado['euro_valor'], 2, ',', '.') . '</span></p>';
            }

            $actualizado = $estado['ultima_actualizacion']
                ? date('d/m/Y H:i', strtotime($estado['ultima_actualizacion'])) . ' hs'
                : $hoy;

            $contenido = '<p>Hola, te compartimos la cotización vigente:</p>'
                . '<div class="rate-box">' . $filas
                . '<p class="rate-meta">Cotización en pesos argentinos (ARS). '
                . 'Última actualización: ' . $actualizado . '.</p></div>'
                . '<div class="notice"><strong>Vigencia:</strong> esta cotización es válida '
                . 'exclusivamente para operaciones del día de la fecha y hasta las ' . $hhasta . ' hs. '
                . 'Pasado ese horario, aplica la cotización del próximo día hábil.</div>';

        } elseif ($estado['status'] === 'no_disponible') {
            $titulo = 'Tipo de cambio del ' . $hoy . ' — aún no disponible';

            $contenido = '<p>Hola, recibimos tu consulta.</p>'
                . '<div class="notice">La cotización del día <strong>todavía no fue cargada</strong> '
                . 'por nuestro departamento de Caja. Se publica habitualmente entre las '
                . $hdesde . ' y las 10:00 hs.</div>'
                . '<p>Podés consultarla apenas esté disponible en '
                . '<a href="' . $url . '">' . $url . '</a>, o escribirnos de nuevo más tarde.</p>';

        } else { // cerrado
            $titulo = 'Tipo de cambio — fuera de horario de atención';

            $contenido = '<p>Hola, recibimos tu consulta fuera del horario de atención.</p>'
                . '<div class="notice">El servicio de consulta de tipo de cambio opera de '
                . '<strong>lunes a viernes de ' . $hdesde . ' a ' . $hhasta . ' hs</strong>. '
                . 'Las operaciones que cierres ahora se liquidan con la cotización del próximo día hábil.</div>'
                . '<p>La cotización vigente se publica en '
                . '<a href="' . $url . '">' . $url . '</a> dentro de ese horario.</p>';
        }

        // Datos bancarios: no vencen a las 17:00 y son justo lo que necesita
        // quien escribe de noche para preparar una transferencia. Se tratan
        // como texto plano, igual que en la web (el frontend usa textContent).
        $banco = trim((string)$estado['datos_bancarios']);
        if ($banco !== '') {
            $contenido .= '<div class="bank"><h3>Datos bancarios</h3>'
                . nl2br(htmlspecialchars($banco, ENT_QUOTES, 'UTF-8')) . '</div>';
        }

        $cita = trim((string)($msg['bodyPreview'] ?? ''));
        if ($cita !== '') {
            $contenido .= '<div class="quote">Tu consulta: “'
                . htmlspecialchars(mb_substr($cita, 0, 200), ENT_QUOTES, 'UTF-8')
                . (mb_strlen($cita) > 200 ? '…' : '') . '”</div>';
        }

        return $svc->renderTemplate('tipo_cambio', [
            'title'   => htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'),
            'content' => $contenido,
        ]);
    }
}
