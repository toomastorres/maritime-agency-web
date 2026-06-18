-- ═══════════════════════════════════════════════════════════
-- AMA — install.sql
-- Schema completo de la base de datos ama_web
-- ═══════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ──
-- 1. AUTENTICACIÓN — Usuarios del Portal Administrativo
-- ──

CREATE TABLE IF NOT EXISTS usuarios_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol_portal ENUM('caja', 'cs', 'rrhh', 'documentacion', 'admin_global') NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    reset_token VARCHAR(64) NULL UNIQUE,
    reset_expires TIMESTAMP NULL,
    password_updated_at TIMESTAMP NULL
);

-- Migración: agregar columnas nuevas si la tabla ya existía (idempotente)
-- Estas migraciones se aplican de forma segura: si la columna/indice ya existe, se ignora el error.

DELIMITER $$
DROP PROCEDURE IF EXISTS __AMA_migrate$$
CREATE PROCEDURE __AMA_migrate()
BEGIN
    -- usuarios_admin: reset_token
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin' AND COLUMN_NAME = 'reset_token') THEN
        ALTER TABLE usuarios_admin ADD COLUMN reset_token VARCHAR(64) NULL UNIQUE;
    END IF;
    -- usuarios_admin: reset_expires
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin' AND COLUMN_NAME = 'reset_expires') THEN
        ALTER TABLE usuarios_admin ADD COLUMN reset_expires TIMESTAMP NULL;
    END IF;
    -- usuarios_admin: password_updated_at
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin' AND COLUMN_NAME = 'password_updated_at') THEN
        ALTER TABLE usuarios_admin ADD COLUMN password_updated_at TIMESTAMP NULL;
    END IF;
    -- usuarios_admin: username
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin' AND COLUMN_NAME = 'username') THEN
        ALTER TABLE usuarios_admin ADD COLUMN username VARCHAR(60) NULL AFTER nombre;
        UPDATE usuarios_admin SET username = SUBSTRING_INDEX(email, '@', 1) WHERE username IS NULL;
        ALTER TABLE usuarios_admin MODIFY COLUMN username VARCHAR(60) NOT NULL;
        ALTER TABLE usuarios_admin ADD UNIQUE INDEX idx_username (username);
    END IF;

    -- applicants: vacancy_id nullable
    ALTER TABLE applicants MODIFY vacancy_id INT NULL DEFAULT NULL;
    -- applicants: telefono
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'applicants' AND COLUMN_NAME = 'telefono') THEN
        ALTER TABLE applicants ADD COLUMN telefono VARCHAR(50) NULL AFTER email;
    END IF;
    -- applicants: area
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'applicants' AND COLUMN_NAME = 'area') THEN
        ALTER TABLE applicants ADD COLUMN area VARCHAR(100) NULL AFTER telefono;
    END IF;

    -- public_documents: doc_key
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'public_documents' AND COLUMN_NAME = 'doc_key') THEN
        ALTER TABLE public_documents ADD COLUMN doc_key VARCHAR(50) NOT NULL DEFAULT '' AFTER id;
        ALTER TABLE public_documents ADD UNIQUE INDEX idx_doc_key (doc_key);
    END IF;

    -- vacancies: activo
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vacancies' AND COLUMN_NAME = 'activo') THEN
        ALTER TABLE vacancies ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER requisitos;
    END IF;

    -- Indexes (idempotentes: solo agregar si no existen)
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_audit_logs' AND INDEX_NAME = 'idx_accion_creado') THEN
        ALTER TABLE admin_audit_logs ADD INDEX idx_accion_creado (accion, creado_en);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_audit_logs' AND INDEX_NAME = 'idx_ip_address') THEN
        ALTER TABLE admin_audit_logs ADD INDEX idx_ip_address (ip_address);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'exchange_rates' AND INDEX_NAME = 'idx_fecha') THEN
        ALTER TABLE exchange_rates ADD INDEX idx_fecha (fecha_cotizacion DESC);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schedule_port_calls' AND INDEX_NAME = 'idx_import_order') THEN
        ALTER TABLE schedule_port_calls ADD INDEX idx_import_order (import_id, port_order, vessel_order);
    END IF;
END$$
DELIMITER ;

-- La llamada a __AMA_migrate() se ejecuta al final del archivo,
-- después de todos los CREATE TABLE IF NOT EXISTS.

-- ──
-- 2. AUDITORÍA — Registro de acciones administrativas
-- ──

CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    accion VARCHAR(100) NOT NULL,
    detalles TEXT NULL,
    ip_address VARCHAR(45) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL
);

-- ──
-- 3. MÓDULO FINANZAS (CAJA)
-- ──

CREATE TABLE IF NOT EXISTS exchange_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    moneda ENUM('USD', 'EUR') NOT NULL,
    valor DECIMAL(10, 4) NOT NULL,
    fecha_cotizacion DATE NOT NULL,
    usuario_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_admin(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS bank_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contenido_html TEXT NOT NULL,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    actualizado_por INT NOT NULL,
    FOREIGN KEY (actualizado_por) REFERENCES usuarios_admin(id) ON DELETE RESTRICT
);

-- ──
-- 4. MÓDULO RRHH
-- ──

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    puesto VARCHAR(150) NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(50) NULL,
    foto_path VARCHAR(255) NULL,
    jerarquia INT DEFAULT 10,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vacancies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    requisitos TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    estado BOOLEAN DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vacancy_id INT NULL DEFAULT NULL,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(50) NULL,
    area VARCHAR(100) NULL,
    cv_path VARCHAR(255) NOT NULL,
    mensaje TEXT NULL,
    fecha_postulacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('nuevo', 'revision', 'descartado', 'contratado') DEFAULT 'nuevo',
    FOREIGN KEY (vacancy_id) REFERENCES vacancies(id) ON DELETE SET NULL
);

-- ──
-- 5. MÓDULO CUSTOMER SERVICE (SCHEDULES)
-- ──

CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buque VARCHAR(150) NOT NULL,
    viaje VARCHAR(50) NOT NULL,
    eta DATE NULL,
    etd DATE NULL,
    puerto_origen VARCHAR(100) NULL,
    puerto_destino VARCHAR(100) NULL,
    datos_extra JSON NULL,
    hash_importacion VARCHAR(64) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ──
-- 5b. MÓDULO CS — Schedules estructurados (nuevo sistema columnar)
-- ──

CREATE TABLE IF NOT EXISTS schedule_imports (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    service_name     VARCHAR(200)  NOT NULL,
    week_number      SMALLINT      NOT NULL,
    hash             VARCHAR(64)   NOT NULL UNIQUE,
    archivo_original VARCHAR(255)  NULL,
    subido_por       INT           NULL,
    creado_en        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subido_por) REFERENCES usuarios_admin(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS schedule_port_calls (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    import_id    INT           NOT NULL,
    vessel_name  VARCHAR(150)  NOT NULL,
    voyage_code  VARCHAR(50)   NOT NULL,
    port_name    VARCHAR(100)  NOT NULL,
    eta          DATE          NULL,
    ets          DATE          NULL,
    is_feeder    TINYINT(1)    NOT NULL DEFAULT 0,
    feeder_code  VARCHAR(50)   NULL,
    port_order   SMALLINT      NOT NULL DEFAULT 0,
    vessel_order SMALLINT      NOT NULL DEFAULT 0,
    block_number SMALLINT      NOT NULL DEFAULT 0,
    leg          ENUM('outbound','return') NOT NULL DEFAULT 'outbound',
    FOREIGN KEY (import_id) REFERENCES schedule_imports(id) ON DELETE CASCADE
);

-- ──
-- 6. MÓDULO DOCUMENTACIÓN
-- ──

CREATE TABLE IF NOT EXISTS public_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_key VARCHAR(50) NOT NULL UNIQUE,
    nombre_documento VARCHAR(200) NOT NULL,
    categoria ENUM('importacion', 'exportacion', 'formularios', 'legal') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    peso_bytes INT NULL,
    fecha_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    subido_por INT NOT NULL,
    FOREIGN KEY (subido_por) REFERENCES usuarios_admin(id) ON DELETE RESTRICT
);

-- ──
-- 7. MÓDULO NOVEDADES (nuevo — antes usaba archivo JSON)
-- ──

CREATE TABLE IF NOT EXISTS novedades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    bajada TEXT NOT NULL,
    cuerpo TEXT NULL,
    categoria VARCHAR(100) NOT NULL DEFAULT 'Servicios',
    autor VARCHAR(100) NOT NULL DEFAULT 'Comunicaciones AMA',
    destacada TINYINT(1) DEFAULT 0,
    etiquetas JSON NULL,
    imagen_url VARCHAR(500) NULL,
    fecha_iso DATE NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    publicado_por INT NULL,
    FOREIGN KEY (publicado_por) REFERENCES usuarios_admin(id) ON DELETE SET NULL
);

-- ──
-- 8. DOCUMENTOS ADJUNTOS DE NOVEDADES
-- ──

CREATE TABLE IF NOT EXISTS novedad_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    novedad_id INT NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    archivo_path VARCHAR(500) NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    tamano_bytes INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (novedad_id) REFERENCES novedades(id) ON DELETE CASCADE
);

-- ──
-- 9. LEADS / SOLICITUDES DE COTIZACIÓN
-- ──

CREATE TABLE IF NOT EXISTS cotizacion_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio ENUM('roro','proyecto','contenedores') NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    empresa VARCHAR(200) NULL,
    email VARCHAR(255) NOT NULL,
    telefono VARCHAR(50) NULL,
    mensaje TEXT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leido TINYINT(1) DEFAULT 0
);

-- =================================================================================
-- DATOS INICIALES
-- =================================================================================

-- ──
-- Usuarios administrativos
-- ──
-- SEGURIDAD: NO se siembran usuarios aquí. El seed anterior usaba una
-- contraseña compartida y conocida ("Temporal2026!") versionada en este
-- archivo, lo que era un riesgo crítico. El primer administrador se crea
-- de forma segura, una sola vez, con el script protegido:
--
--     /gestion-interna-agencia/setup.php?token=<SETUP_TOKEN del .env>
--
-- Ese script valida la contraseña (política NIST), crea el admin_global
-- inicial con clave manual y luego se auto-bloquea. El resto de los
-- usuarios (caja, cs, rrhh, documentacion) se dan de alta desde el panel:
--     Usuarios Admin → Invitar Usuario → "Establecer Contraseña Manualmente"
--
-- La fila base de bank_details también la crea setup.php (depende de un
-- usuario válido por la FK actualizado_por).
-- ──

-- ──
-- Migración de empleados desde tabla legacy wp_empleados
-- Solo ejecutar si la tabla wp_empleados existe en esta misma BD.
-- Si la BD es nueva, omitir este bloque o importar wp_empleados.sql primero.
-- ──

-- INSERT IGNORE INTO employees (nombre, apellido, puesto, departamento, email, telefono, jerarquia)
-- SELECT nombre, apellido, posicion, departamento, COALESCE(email, ''), telefono, COALESCE(jerarquia, 10)
-- FROM wp_empleados;

-- =================================================================================
-- Nota: Para instalar empleados del directorio legacy, hay dos opciones:
--   A) Descomentar el INSERT IGNORE de arriba (si wp_empleados existe en esta BD)
--   B) Importar database/wp_empleados.sql primero y luego ejecutar la línea de arriba
--   C) Cargar los empleados manualmente desde el portal admin > RRHH > Empleados
-- =================================================================================

-- ══════════════════════════════════════════════════════════════
-- MIGRACIONES IDEMPOTENTES
-- Se ejecutan DESPUÉS de todos los CREATE TABLE IF NOT EXISTS.
-- En BD nueva: no hacen nada (las columnas ya existen en el CREATE).
-- En BD existente: agregan columnas/indices faltantes sin error.
-- ══════════════════════════════════════════════════════════════

CALL __AMA_migrate();
DROP PROCEDURE IF EXISTS __AMA_migrate;

SET FOREIGN_KEY_CHECKS = 1;
