<?php
/**
 * AMA — email.php
 * Core Email Service — PHPMailer + SMTP autenticado (el hosting compartido).
 *
 * Reemplaza la implementacion legacy basada en mail() nativo.
 * - Transport: SMTPS sobre smtp-legacy.hosting-ejemplo.example:465 (configurable via .env)
 * - Templates: archivos .html con placeholders {{key}} en src/email-templates/
 * - Logging tecnico: src/data/logs/app.log (mode 3)
 * - Audit estructurado: src/data/logs/email_audit.log (JSONL)
 *
 * El routing por area se gestiona con constantes NOTIFY_* en config.php.
 */

// Cargar PHPMailer via Composer autoloader (graceful si vendor/ no existe aún)
$__autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($__autoloadPath)) {
    require_once $__autoloadPath;
} else {
    // Log para diagnóstico — el EmailService funcionará en modo degradado (send siempre false)
    @error_log(
        '[' . date('Y-m-d H:i:s') . '] EmailService: vendor/autoload.php no encontrado. Ejecute "composer install" o suba vendor/ al servidor.' . PHP_EOL,
        3,
        dirname(__DIR__, 2) . '/src/data/logs/app.log'
    );
}
unset($__autoloadPath);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService {
    private string $from;
    private string $fromName;
    private string $replyTo;
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $secure;        // 'ssl' | 'tls'
    private string $logFile;
    private string $auditFile;
    private string $templateDir;

    /**
     * Mapa de canales → constantes NOTIFY_* definidas en config.php.
     * Cada modulo del admin usa un canal distinto para dirigir
     * la notificacion al buzon correcto del area.
     */
    private const CHANNEL_MAP = [
        'rrhh'                 => 'NOTIFY_RRHH',
        'caja'                 => 'NOTIFY_CAJA',
        'docs'                 => 'NOTIFY_DOCS',
        'cs'                   => 'NOTIFY_CS',
        'comercial_autos'      => 'NOTIFY_COMERCIAL_AUTOS',
        'comercial_proyecto'   => 'NOTIFY_COMERCIAL_PROYECTO',
        'comercial_containers' => 'NOTIFY_COMERCIAL_CONTAINERS',
    ];

    public function __construct() {
        $this->from        = defined('SMTP_FROM') ? SMTP_FROM : '';
        $this->fromName    = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'AMA Sistemas';
        $this->replyTo     = defined('SMTP_REPLY_TO') ? SMTP_REPLY_TO : (defined('NOTIFY_TO') ? NOTIFY_TO : '');
        $this->host        = defined('SMTP_HOST') ? SMTP_HOST : '';
        $this->port        = defined('SMTP_PORT') ? (int)SMTP_PORT : 465;
        $this->username    = defined('SMTP_USER') ? SMTP_USER : '';
        $this->password    = defined('SMTP_PASS') ? SMTP_PASS : '';
        $this->secure      = defined('SMTP_SECURE') ? SMTP_SECURE : 'ssl';

        $logsDir           = dirname(__DIR__, 2) . '/src/data/logs';
        $this->logFile     = $logsDir . '/app.log';
        $this->auditFile   = $logsDir . '/email_audit.log';
        $this->templateDir = dirname(__DIR__, 2) . '/src/email-templates';
    }

    /**
     * Logging tecnico → app.log (error_log mode 3, append a archivo).
     * Mismo patron que database.php para mantener consistencia.
     */
    private function logLine(string $msg): void {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        @error_log('[' . date('Y-m-d H:i:s') . '] EmailService: ' . $msg . PHP_EOL, 3, $this->logFile);
    }

    /**
     * Audit estructurado → email_audit.log (JSONL, una linea por envio).
     * Cada entrada incluye timestamp, channel, recipient, subject, status, error.
     */
    private function audit(array $entry): void {
        $dir = dirname($this->auditFile);
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        $entry['timestamp'] = date('c'); // ISO 8601
        @file_put_contents(
            $this->auditFile,
            json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Renderiza un template HTML con placeholders {{key}}.
     *
     * @param string $name  Nombre del template (sin .html). Ej: "notification"
     * @param array  $vars  Variables a reemplazar. Ej: ['title' => '...', 'content' => '...']
     * @return string       HTML resultante
     * @throws RuntimeException si el template no existe
     */
    public function renderTemplate(string $name, array $vars): string {
        $path = $this->templateDir . '/' . basename($name) . '.html';
        if (!is_file($path)) {
            throw new \RuntimeException("Email template not found: {$name}");
        }
        $html = file_get_contents($path);
        // Variables auto-inyectadas
        $vars['year'] = $vars['year'] ?? date('Y');
        // Reemplazo de placeholders
        foreach ($vars as $key => $value) {
            $html = str_replace('{{' . $key . '}}', (string)$value, $html);
        }
        return $html;
    }

    /**
     * Resuelve el destinatario de un canal de notificacion.
     */
    private function resolveChannel(string $channel): string {
        $constName = self::CHANNEL_MAP[$channel] ?? null;

        // 1. Buscar la constante especifica del canal
        if ($constName && defined($constName) && constant($constName) !== '') {
            return constant($constName);
        }

        // 2. Fallback: NOTIFY_TO (buzon general)
        if (defined('NOTIFY_TO') && NOTIFY_TO !== '') {
            return NOTIFY_TO;
        }

        // 3. Ultimo recurso: SMTP_FROM
        return $this->from;
    }

    /**
     * Envia una notificacion interna al area correspondiente.
     */
    public function sendNotification(string $channel, string $subject, string $htmlBody): bool {
        $to = $this->resolveChannel($channel);
        return $this->send($subject, $htmlBody, $to, $channel);
    }

    /**
     * Envia un email HTML a un destinatario especifico via SMTP autenticado.
     *
     * @param string      $subject   Asunto del correo
     * @param string      $htmlBody  Cuerpo HTML del correo
     * @param string      $to        Destinatario
     * @param string|null $channel   Canal de origen (solo para audit log)
     * @return bool                  true si el envio fue exitoso, false en caso contrario
     */
    public function send(string $subject, string $htmlBody, string $to, ?string $channel = null): bool {
        // Sanity checks de configuracion
        if ($this->from === '' || $this->host === '' || $this->username === '' || $this->password === '') {
            $this->logLine("Config incompleta — from/host/user/pass faltante en .env");
            $this->audit([
                'channel'   => $channel,
                'recipient' => $to,
                'subject'   => $subject,
                'status'    => 'error',
                'error'     => 'Config incompleta en .env',
            ]);
            return false;
        }
        if ($to === '') {
            $this->logLine("Destinatario vacio — subject: {$subject}");
            $this->audit([
                'channel'   => $channel,
                'recipient' => '(empty)',
                'subject'   => $subject,
                'status'    => 'error',
                'error'     => 'Destinatario vacio',
            ]);
            return false;
        }

        // Verificar que PHPMailer esté disponible (vendor/ desplegado)
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $this->logLine("PHPMailer no disponible — suba vendor/ al servidor o ejecute 'composer install'");
            $this->audit([
                'channel'   => $channel,
                'recipient' => $to,
                'subject'   => $subject,
                'status'    => 'error',
                'error'     => 'PHPMailer no instalado (vendor/ faltante)',
            ]);
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            // Server config
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = ($this->secure === 'tls')
                ? PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;

            // Sender
            $mail->setFrom($this->from, $this->fromName);
            if ($this->replyTo !== '') {
                $mail->addReplyTo($this->replyTo, 'AMA Argentina');
            }
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = trim(strip_tags($htmlBody));
            $mail->XMailer = 'AMA-System/2.0';

            $mail->send();

            $this->logLine("OK -> {$to} | subject: {$subject}");
            $this->audit([
                'channel'   => $channel,
                'recipient' => $to,
                'subject'   => $subject,
                'status'    => 'success',
                'error'     => null,
            ]);
            return true;

        } catch (PHPMailerException $e) {
            $errorInfo = $mail->ErrorInfo ?: $e->getMessage();
            $this->logLine("FAIL -> {$to} | subject: {$subject} | error: {$errorInfo}");
            $this->audit([
                'channel'   => $channel,
                'recipient' => $to,
                'subject'   => $subject,
                'status'    => 'error',
                'error'     => $errorInfo,
            ]);
            return false;
        }
    }

    /**
     * Devuelve info de diagnostico para la vista de test.
     */
    public function getDiagnostics(): array {
        $channels = [];
        foreach (self::CHANNEL_MAP as $ch => $const) {
            $channels[$ch] = [
                'constant' => $const,
                'value'    => defined($const) ? constant($const) : '(no definido)',
            ];
        }

        $phpmailerOk = class_exists('PHPMailer\\PHPMailer\\PHPMailer');

        return [
            'mode'       => $phpmailerOk ? 'PHPMailer + SMTP autenticado' : 'NO DISPONIBLE (vendor/ faltante)',
            'phpmailer'  => $phpmailerOk,
            'phpmailer_version' => $phpmailerOk ? PHPMailer::VERSION : '(no instalado)',
            'from'       => $this->from ?: '(no configurado)',
            'from_name'  => $this->fromName,
            'reply_to'   => $this->replyTo ?: '(no configurado)',
            'host'       => $this->host ?: '(no configurado)',
            'port'       => $this->port,
            'secure'     => $this->secure,
            'auth'       => $this->username !== '' ? 'configurado' : '(faltante)',
            'notify_to'  => defined('NOTIFY_TO') ? NOTIFY_TO : '(no configurado)',
            'channels'   => $channels,
        ];
    }

    /**
     * Devuelve la lista de canales disponibles (para referencia).
     */
    public static function getAvailableChannels(): array {
        return array_keys(self::CHANNEL_MAP);
    }

    /**
     * Backwards-compat: los 13 callers existentes siguen llamando a esto.
     * Internamente delega al motor de templates con el template generico.
     */
    public static function buildHtmlTemplate(string $title, string $content): string {
        $svc = new self();
        return $svc->renderTemplate('notification', [
            'title'   => $title,
            'content' => $content,
        ]);
    }

    /**
     * Lee las ultimas N entradas del audit log (para mostrar en email_test.php).
     * Devuelve array de entradas en orden inverso (mas reciente primero).
     */
    public function getAuditTail(int $lines = 20): array {
        if (!is_file($this->auditFile)) return [];
        $content = file($this->auditFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$content) return [];
        $tail = array_slice($content, -$lines);
        $entries = [];
        foreach (array_reverse($tail) as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) $entries[] = $decoded;
        }
        return $entries;
    }
}
