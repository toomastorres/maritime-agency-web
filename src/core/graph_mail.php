<?php
/**
 * AMA — graph_mail.php
 * Cliente de Microsoft Graph para el buzón de tipo de cambio.
 *
 * Flujo client_credentials (app-only): el bot no actúa en nombre de una
 * persona, actúa como aplicación sobre un único buzón.
 *
 * Los permisos que necesita son Mail.ReadWrite y Mail.Send: ReadWrite y no
 * Read porque crear el borrador de la respuesta y marcar el mensaje como
 * leído son escrituras.
 *
 * Esos permisos NO se otorgan en Entra, se otorgan en Exchange Online con
 * RBAC for Applications, que es el único lugar donde se pueden acotar a un
 * buzón. Otorgados en Entra alcanzan TODAS las casillas del tenant y no hay
 * forma de limitarlos. Los dos caminos se suman, así que hacer los dos deja
 * el acceso sin límite. Ver docs/TIPO_CAMBIO_MAILBOT.md.
 *
 * Implementación nativa (curl + json), sin dependencias de Composer,
 * siguiendo el criterio del proyecto de no depender de vendor/ en runtime.
 *
 * Requiere PHP 7.4+ con las extensiones curl y json.
 */

// Defensa en profundidad: archivo solo para inclusión, nunca acceso web directo.
if (isset($_SERVER['SCRIPT_FILENAME']) && @realpath($_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/utils.php';

class GraphMail
{
    /** Timeout de las llamadas salientes a Microsoft, en segundos. */
    const HTTP_TIMEOUT = 20;

    /** Raíz de la API. */
    const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';

    /** @var array Configuración leída del .env */
    private $config;

    /** @var string|null Access token de la corrida actual (nunca se persiste en disco) */
    private $token = null;

    /** @var string|null Último error técnico */
    private $lastError = null;

    public function __construct(?array $env = null)
    {
        $env = $env ?? loadEnv(dirname(__DIR__, 2) . '/.env');

        $this->config = [
            // El tenant es el mismo que ya usa el portal; la app es otra, para
            // poder rotar su secreto sin tocar el login del personal.
            'tenant_id'     => trim($env['TC_MAIL_TENANT_ID'] ?? $env['ENTRA_TENANT_ID'] ?? ''),
            'client_id'     => trim($env['TC_MAIL_CLIENT_ID'] ?? ''),
            'client_secret' => trim($env['TC_MAIL_CLIENT_SECRET'] ?? ''),
            'buzon'         => trim($env['TC_MAIL_BUZON'] ?? ''),
        ];
    }

    /**
     * Indica si el bot tiene todo lo que necesita para operar.
     *
     * @return bool
     */
    public function estaConfigurado(): bool
    {
        return $this->config['tenant_id'] !== ''
            && $this->config['client_id'] !== ''
            && $this->config['client_secret'] !== ''
            && $this->config['buzon'] !== '';
    }

    /**
     * Dirección del buzón sobre el que opera el bot.
     *
     * @return string
     */
    public function buzon(): string
    {
        return $this->config['buzon'];
    }

    /**
     * Último error técnico registrado (para logs, nunca para el cliente).
     *
     * @return string|null
     */
    public function ultimoError(): ?string
    {
        return $this->lastError;
    }

    // ── Autenticación ──────────────────────────────────────────

    /**
     * Obtiene un access token app-only. Se reusa durante la corrida y no se
     * escribe a disco: en hosting compartido un token en un archivo es una
     * credencial más para custodiar, y la corrida dura segundos.
     *
     * @return string|null
     */
    private function token(): ?string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $url = 'https://login.microsoftonline.com/' . rawurlencode($this->config['tenant_id'])
            . '/oauth2/v2.0/token';

        $body = http_build_query([
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'scope'         => 'https://graph.microsoft.com/.default',
            'grant_type'    => 'client_credentials',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            $this->lastError = 'No se pudo contactar a Microsoft: ' . $cerr;
            return null;
        }

        $data = json_decode($raw, true);
        if ($http !== 200 || empty($data['access_token'])) {
            // El error de Microsoft se guarda para el log, nunca se propaga.
            $this->lastError = 'Token rechazado (HTTP ' . $http . '): '
                . ($data['error_description'] ?? $data['error'] ?? 'sin detalle');
            return null;
        }

        $this->token = $data['access_token'];
        return $this->token;
    }

    // ── Transporte ─────────────────────────────────────────────

    /**
     * Llamada autenticada a Graph.
     *
     * @param  string     $metodo GET | POST | PATCH
     * @param  string     $ruta   Ruta relativa al buzón (ej: "/messages/123")
     * @param  array|null $body   Payload JSON, si aplica.
     * @return array{http: int, data: array}|null null si falló el transporte.
     */
    private function request(string $metodo, string $ruta, ?array $body = null): ?array
    {
        $token = $this->token();
        if ($token === null) {
            return null;
        }

        $url = self::GRAPH_BASE . '/users/' . rawurlencode($this->config['buzon']) . $ruta;

        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST  => $metodo,
        ];

        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json; charset=utf-8';
        }

        $opts[CURLOPT_HTTPHEADER] = $headers;

        $ch = curl_init($url);
        curl_setopt_array($ch, $opts);
        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            $this->lastError = $metodo . ' ' . $ruta . ' — transporte: ' . $cerr;
            return null;
        }

        $data = ($raw === '') ? [] : (json_decode($raw, true) ?: []);

        if ($http >= 400) {
            $this->lastError = $metodo . ' ' . $ruta . ' — HTTP ' . $http . ': '
                . ($data['error']['message'] ?? 'sin detalle');
        }

        return ['http' => $http, 'data' => $data];
    }

    // ── Lectura ────────────────────────────────────────────────

    /**
     * Mensajes sin leer de la bandeja de entrada, del más reciente al más viejo.
     *
     * No se usa $orderby junto con $filter: Graph rechaza esa combinación en
     * mensajes con "InefficientFilter". El orden por defecto ya es por fecha
     * de recepción descendente.
     *
     * NOTA DE DISEÑO — paginación: el bot trae como máximo $top mensajes por
     * corrida. Si el buzón acumula más no-leídos que ese límite entre crons
     * (ej. 30 en 5 minutos), los más antiguos quedan postergados a la
     * siguiente corrida, no se pierden. Si el volumen crece sostenidamente,
     * implementar paginación con @odata.nextLink.
     *
     * @param  int $top Máximo de mensajes a traer.
     * @return array|null Lista de mensajes, o null si falló.
     */
    public function mensajesNoLeidos(int $top = 25): ?array
    {
        $select = 'id,conversationId,internetMessageId,subject,from,sender,'
            . 'toRecipients,ccRecipients,receivedDateTime,bodyPreview,isDraft';

        $ruta = '/mailFolders/inbox/messages'
            . '?$select=' . rawurlencode($select)
            . '&$filter=' . rawurlencode('isRead eq false')
            . '&$top=' . max(1, min(50, $top));

        $res = $this->request('GET', $ruta);
        if ($res === null || $res['http'] !== 200) {
            return null;
        }

        return $res['data']['value'] ?? [];
    }

    /**
     * Encabezados SMTP de un mensaje. Se piden aparte porque Graph no los
     * devuelve en el listado, y son la única forma confiable de reconocer
     * respuestas automáticas, rebotes y listas de distribución.
     *
     * @param  string $mensajeId
     * @return array Mapa nombre-en-minúscula => valor. Vacío si no se pudieron leer.
     */
    public function encabezados(string $mensajeId): array
    {
        $ruta = '/messages/' . rawurlencode($mensajeId) . '?$select=internetMessageHeaders';
        $res  = $this->request('GET', $ruta);

        if ($res === null || $res['http'] !== 200) {
            return [];
        }

        $mapa = [];
        foreach ($res['data']['internetMessageHeaders'] ?? [] as $h) {
            if (!isset($h['name'])) {
                continue;
            }
            $nombre = strtolower($h['name']);
            // Un mismo encabezado puede repetirse (Received, etc.): se concatena.
            $mapa[$nombre] = isset($mapa[$nombre])
                ? $mapa[$nombre] . ' | ' . ($h['value'] ?? '')
                : ($h['value'] ?? '');
        }

        return $mapa;
    }

    // ── Escritura ──────────────────────────────────────────────

    /**
     * Responde un mensaje en su mismo hilo.
     *
     * Se hace en tres pasos (createReply → PATCH → send) en vez de usar la
     * acción /reply directa porque es la única manera de controlar el cuerpo
     * completo y de marcar el correo como respuesta automática, que es lo
     * que evita el ping-pong con el autoresponder del otro lado.
     *
     * @param  string $mensajeId ID Graph del mensaje original.
     * @param  string $htmlBody  Cuerpo HTML de la respuesta.
     * @return bool
     */
    public function responder(string $mensajeId, string $htmlBody): bool
    {
        // 1. Borrador de respuesta (conserva asunto RE: y conversationId).
        $res = $this->request('POST', '/messages/' . rawurlencode($mensajeId) . '/createReply');
        if ($res === null || $res['http'] >= 300 || empty($res['data']['id'])) {
            return false;
        }
        $borradorId = $res['data']['id'];

        // 2. Cuerpo propio en HTML.
        // Nota: Microsoft Graph rechaza 'internetMessageHeaders' en llamadas PATCH
        // con 'ErrorInvalidPropertySet: Set action is invalid for property'.
        // El control anti-bucle se realiza a nivel aplicación en tc_mail_log y tc_mailbot.
        $res = $this->request('PATCH', '/messages/' . rawurlencode($borradorId), [
            'body' => [
                'contentType' => 'HTML',
                'content'     => $htmlBody,
            ],
        ]);
        if ($res === null || $res['http'] >= 300) {
            return false;
        }

        // 3. Envío.
        $res = $this->request('POST', '/messages/' . rawurlencode($borradorId) . '/send');

        return $res !== null && $res['http'] < 300;
    }

    /**
     * Marca un mensaje como leído.
     *
     * @param  string $mensajeId
     * @return bool
     */
    public function marcarLeido(string $mensajeId): bool
    {
        $res = $this->request('PATCH', '/messages/' . rawurlencode($mensajeId), ['isRead' => true]);

        return $res !== null && $res['http'] < 300;
    }

    /**
     * Prueba de conectividad para la vista de diagnóstico: pide el buzón y
     * devuelve un diagnóstico legible sin exponer credenciales.
     *
     * @return array{ok: bool, detalle: string}
     */
    public function diagnostico(): array
    {
        if (!$this->estaConfigurado()) {
            return ['ok' => false, 'detalle' => 'Faltan variables TC_MAIL_* en el .env.'];
        }

        $res = $this->request('GET', '?$select=mail,displayName');

        if ($res === null) {
            return ['ok' => false, 'detalle' => $this->lastError ?? 'Sin respuesta de Microsoft.'];
        }
        if ($res['http'] !== 200) {
            return ['ok' => false, 'detalle' => $this->lastError ?? ('HTTP ' . $res['http'])];
        }

        return ['ok' => true, 'detalle' => 'Buzón accesible: ' . ($res['data']['mail'] ?? $this->config['buzon'])];
    }
}
