-- ═══════════════════════════════════════════════════════════
-- Migración: Bot de respuestas del buzón de tipo de cambio
-- Bitácora de los mensajes que el bot procesó en tc@atlantic-agency.example.com.
-- No es solo auditoría: la clave única sobre internet_message_id es lo
-- que garantiza que un mismo mail no se conteste dos veces aunque la
-- corrida se corte a la mitad o el cron se superponga.
-- Idempotente: se puede correr varias veces sin efecto adverso.
-- ═══════════════════════════════════════════════════════════

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS tc_mail_log (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Message-ID del correo entrante. Es el identificador estable que
    -- sobrevive a movimientos de carpeta, a diferencia del id de Graph.
    internet_message_id VARCHAR(255) NOT NULL,

    -- Hilo de la conversación: el bot contesta una sola vez por hilo.
    -- Si el cliente vuelve a escribir sobre el mismo hilo, queda sin leer
    -- para que lo atienda una persona.
    conversation_id VARCHAR(255) NULL,

    graph_message_id VARCHAR(512) NULL,
    remitente VARCHAR(320) NOT NULL,
    asunto VARCHAR(255) NULL,

    -- Estado del tipo de cambio al momento de responder.
    estado_tc ENUM('ok', 'no_disponible', 'cerrado', 'error') NULL,

    -- en_proceso se escribe ANTES de enviar y se cierra después: si la
    -- corrida muere entre medio, el mensaje queda marcado y no se
    -- reintenta solo. Mejor un mail sin contestar que uno duplicado.
    accion ENUM('en_proceso', 'respondido', 'omitido', 'error') NOT NULL DEFAULT 'en_proceso',

    motivo VARCHAR(190) NULL,
    recibido_en DATETIME NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_internet_message_id (internet_message_id),
    INDEX idx_conversacion (conversation_id),
    INDEX idx_remitente_fecha (remitente, creado_en),
    INDEX idx_creado (creado_en DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
