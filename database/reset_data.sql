-- ═══════════════════════════════════════════════════════════
-- AMA — reset_data.sql   ⚠️  DESTRUCTIVO — OPCIONAL
-- ═══════════════════════════════════════════════════════════
--
-- Borra TODO el contenido de las tablas (no la estructura) para
-- arrancar de un estado limpio y conocido. Útil si la base trae
-- datos viejos/inconsistentes.
--
-- NOTA MariaDB (el hosting): se usa DELETE y NO TRUNCATE. TRUNCATE sobre
-- una tabla referenciada por una FK (ej: usuarios_admin) falla con el
-- error 1701 aunque FOREIGN_KEY_CHECKS=0. DELETE sí respeta el flag.
-- Tras los DELETE se reinicia el AUTO_INCREMENT para que los IDs
-- vuelvan a empezar en 1.
--
-- NO se ejecuta solo. Importalo a mano (phpMyAdmin / consola) SOLO
-- si estás seguro. Hacé un backup antes. Las tablas deben existir
-- (corré database/install.sql primero si la base está vacía).
--
-- Después de correr esto, para volver a operar:
--   1. (opcional) Importar database/seed_employees.sql  → directorio de staff
--   2. Visitar /gestion-interna-agencia/setup.php?token=<SETUP_TOKEN>  → crear admin
--   3. Crear el resto de los usuarios desde el panel (clave manual)
-- ═══════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM admin_audit_logs;
DELETE FROM exchange_rates;
DELETE FROM bank_details;
DELETE FROM applicants;
DELETE FROM vacancies;
DELETE FROM employees;
DELETE FROM schedules;
DELETE FROM schedule_port_calls;
DELETE FROM schedule_imports;
DELETE FROM novedad_documentos;
DELETE FROM novedades;
DELETE FROM public_documents;
DELETE FROM cotizacion_leads;

-- Usuarios del portal: vaciar para poder re-bootstrappear con setup.php.
-- Comentá esta línea si querés CONSERVAR los usuarios existentes.
DELETE FROM usuarios_admin;

-- Reiniciar contadores AUTO_INCREMENT (IDs vuelven a 1)
ALTER TABLE admin_audit_logs     AUTO_INCREMENT = 1;
ALTER TABLE exchange_rates       AUTO_INCREMENT = 1;
ALTER TABLE bank_details         AUTO_INCREMENT = 1;
ALTER TABLE applicants           AUTO_INCREMENT = 1;
ALTER TABLE vacancies            AUTO_INCREMENT = 1;
ALTER TABLE employees            AUTO_INCREMENT = 1;
ALTER TABLE schedules            AUTO_INCREMENT = 1;
ALTER TABLE schedule_port_calls  AUTO_INCREMENT = 1;
ALTER TABLE schedule_imports     AUTO_INCREMENT = 1;
ALTER TABLE novedad_documentos   AUTO_INCREMENT = 1;
ALTER TABLE novedades            AUTO_INCREMENT = 1;
ALTER TABLE public_documents     AUTO_INCREMENT = 1;
ALTER TABLE cotizacion_leads     AUTO_INCREMENT = 1;
ALTER TABLE usuarios_admin       AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;
