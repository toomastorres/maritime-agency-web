-- ═══════════════════════════════════════════════════════════
-- AMA — reset_data.sql   ⚠️  DESTRUCTIVO — OPCIONAL
-- ═══════════════════════════════════════════════════════════
--
-- Borra TODO el contenido de las tablas (no la estructura) para
-- arrancar de un estado limpio y conocido. Útil si la base trae
-- datos viejos/inconsistentes.
--
-- NO se ejecuta solo. Importalo a mano (phpMyAdmin / consola) SOLO
-- si estás seguro. Hacé un backup antes.
--
-- Después de correr esto, para volver a operar:
--   1. Visitar /gestion-interna-agencia/setup.php?token=<SETUP_TOKEN>  → crear admin
--   2. Crear el resto de los usuarios desde el panel (clave manual)
-- ═══════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE admin_audit_logs;
TRUNCATE TABLE exchange_rates;
TRUNCATE TABLE bank_details;
TRUNCATE TABLE tc_mail_log;
TRUNCATE TABLE applicants;
TRUNCATE TABLE vacancies;
TRUNCATE TABLE schedules;
TRUNCATE TABLE schedule_port_calls;
TRUNCATE TABLE schedule_imports;
TRUNCATE TABLE novedad_documentos;
TRUNCATE TABLE novedades;
TRUNCATE TABLE public_documents;
TRUNCATE TABLE cotizacion_leads;

-- Usuarios del portal: vaciar para poder re-bootstrappear con setup.php.
-- Comentá esta línea si querés CONSERVAR los usuarios existentes.
TRUNCATE TABLE usuarios_admin;

SET FOREIGN_KEY_CHECKS = 1;
