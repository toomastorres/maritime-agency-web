-- ═══════════════════════════════════════════════════════════
-- Migración: Autenticación con Microsoft Entra ID
-- Agrega identidad federada a usuarios_admin y marca las
-- cuentas break-glass que conservan login local.
-- Idempotente: se puede correr varias veces sin efecto adverso.
-- ═══════════════════════════════════════════════════════════

-- ── 1. COLUMNAS DE IDENTIDAD FEDERADA ─────────────────────

DELIMITER $$
DROP PROCEDURE IF EXISTS __ama_migrate_entra$$
CREATE PROCEDURE __ama_migrate_entra()
BEGIN
    -- entra_oid: Object ID del usuario en Entra ID. Junto con entra_tid
    -- es el ÚNICO identificador inmutable. Nunca autorizar por email.
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin' AND COLUMN_NAME = 'entra_oid') THEN
        ALTER TABLE usuarios_admin ADD COLUMN entra_oid CHAR(36) NULL AFTER email;
        ALTER TABLE usuarios_admin ADD UNIQUE INDEX idx_entra_oid (entra_oid);
    END IF;

    -- entra_tid: Tenant ID. Se valida contra el tenant propio en cada login.
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin' AND COLUMN_NAME = 'entra_tid') THEN
        ALTER TABLE usuarios_admin ADD COLUMN entra_tid CHAR(36) NULL AFTER entra_oid;
    END IF;

    -- auth_provider: cómo ingresa esta cuenta.
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin' AND COLUMN_NAME = 'auth_provider') THEN
        ALTER TABLE usuarios_admin ADD COLUMN auth_provider ENUM('entra', 'local') NOT NULL DEFAULT 'entra' AFTER rol_portal;
    END IF;

    -- is_break_glass: única excepción autorizada a usar contraseña local.
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin' AND COLUMN_NAME = 'is_break_glass') THEN
        ALTER TABLE usuarios_admin ADD COLUMN is_break_glass TINYINT(1) NOT NULL DEFAULT 0 AFTER auth_provider;
    END IF;

    -- Las cuentas federadas no tienen contraseña: password_hash pasa a NULL.
    ALTER TABLE usuarios_admin MODIFY COLUMN password_hash VARCHAR(255) NULL;

    -- admin_audit_logs.detalles: los eventos OIDC guardan más contexto.
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_audit_logs' AND COLUMN_NAME = 'detalles' AND CHARACTER_MAXIMUM_LENGTH < 255) THEN
        ALTER TABLE admin_audit_logs MODIFY COLUMN detalles VARCHAR(255) NULL;
    END IF;
END$$
DELIMITER ;

CALL __ama_migrate_entra();
DROP PROCEDURE IF EXISTS __ama_migrate_entra;

-- ── 2. MARCAR LAS CUENTAS EXISTENTES COMO LOCALES ─────────
-- Antes de vincularlas a Entra siguen entrando con contraseña.
-- El administrador las va migrando una por una (ver docs).

UPDATE usuarios_admin
   SET auth_provider = 'local'
 WHERE entra_oid IS NULL
   AND password_hash IS NOT NULL;

-- ── 3. CUENTA BREAK-GLASS ─────────────────────────────────
-- Ejecutar A MANO reemplazando el username por el de la cuenta de
-- emergencia elegida. Debe ser admin_global y tener contraseña propia.
--
--   UPDATE usuarios_admin
--      SET is_break_glass = 1,
--          auth_provider  = 'local'
--    WHERE username = 'REEMPLAZAR_USERNAME'
--      AND rol_portal = 'admin_global';
--
-- Verificación (debe devolver exactamente 1 fila):
--
--   SELECT id, username, rol_portal, is_break_glass
--     FROM usuarios_admin
--    WHERE is_break_glass = 1;
