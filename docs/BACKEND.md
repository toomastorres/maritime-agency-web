# Backend Architecture — AMA Web Project

**Version:** 3.0 (Auditoría Completa — Abril 2026)

## Core Infrastructure (`src/core/`)

| File | Purpose |
|------|---------|
| `database.php` | **Centralized PDO connection**. Reads credentials from `.env` via `loadEnv()`. Singleton guard prevents duplicate connections. Error logging to `src/data/logs/app.log`. PDO con `ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false` (prepared statements reales) y `FETCH_ASSOC` por defecto. |
| `email.php` | Centralized Email Service (`EmailService`). **PHPMailer 6.9.3 + SMTP autenticado** sobre `smtp.example.com:465` (SMTPS). Motor de templates HTML con placeholders `{{key}}` en `src/email-templates/`. Channel-based routing (`NOTIFY_RRHH`, `NOTIFY_CAJA`, `NOTIFY_DOCS`, `NOTIFY_CS`, `NOTIFY_COMERCIAL_*`). Audit estructurado JSONL en `src/data/logs/email_audit.log` con timestamp/channel/recipient/subject/status/error. Carga condicional de `vendor/autoload.php` (graceful degradation si `vendor/` no fue desplegado). Método `getAuditTail(int)` para panel admin. Método `getDiagnostics()` expone estado de configuración sin filtrar credenciales. |
| `utils.php` | Shared utilities: `loadEnv()` para parseo de `.env` (con fallback a `$_SERVER` vía `$systemVars`), `jsonResponse()` para respuestas API unificadas, `tail_file()` para lectura eficiente de logs grandes. |
| `logger.php` | File-based logging system: `logInfo()`, `logWarning()`, `logError()`. Auto-creates log directory. |
| `SimpleXLSX.php` | PHP library for reading `.xlsx` Excel files (used by Schedules module). |
| `SimpleXLS.php` | PHP library for reading `.xls` Excel files. |

## Configuration (`src/config/`)

| File | Purpose |
|------|---------|
| `config.php` | Defines `ROOT_DIR`, `BASE_URL`, `CSS_URL`, `JS_URL`, `ASSETS_URL`. Auto-detects protocol and host. Provides `assetUrl()` function for cache-busting. Define constantes SMTP (`SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`, `SMTP_FROM_NAME`, `SMTP_REPLY_TO`) y de notificación (`NOTIFY_TO`, `NOTIFY_RRHH`, `NOTIFY_CAJA`, `NOTIFY_DOCS`, `NOTIFY_CS`, `NOTIFY_COMERCIAL_AUTOS`, `NOTIFY_COMERCIAL_PROYECTO`, `NOTIFY_COMERCIAL_CONTAINERS`) a partir de variables de `.env`. |

## Email Templates (`src/email-templates/`)

Motor minimalista de templates: archivos `.html` estáticos con placeholders `{{clave}}` reemplazados en runtime con `str_replace()`. No requiere Twig/Blade.

| Template | Uso |
|----------|-----|
| `notification.html` | Template genérico AMA-branded con header azul, acento dorado y footer legal. Variables: `{{title}}`, `{{content}}`, `{{year}}` (auto-inyectada). Lo usa `EmailService::buildHtmlTemplate()` para los 13 callers existentes sin que tengan que migrar. |

Para agregar un template nuevo:

```php
$svc  = new EmailService();
$html = $svc->renderTemplate('<nombre>', ['k1' => $v1, 'k2' => $v2]);
$svc->sendNotification('<canal>', 'Asunto', $html);
```

Variables siempre disponibles: `{{year}}`. Si el archivo `.html` no existe, `renderTemplate()` lanza `RuntimeException`.

## API Endpoints (`src/logic/api/`)

All endpoints use `src/core/database.php` for DB access and return JSON via `jsonResponse()`.

| Endpoint | Route | Logic |
|----------|-------|-------|
| `tipo_cambio.php` | `GET /api/tipo_cambio` | Time-gated (L-V 09-17hs). Returns USD/EUR rates from `exchange_rates` table. |
| `contactos.php` | `GET /api/contactos` | Returns employee directory from `employees` table, grouped by department. |
| `vacantes.php` | `GET /api/vacantes` | Returns active vacancies (`estado = 1`) from `vacancies` table. |
| `documentos.php` | `GET /api/documentos` | Returns official documents from `public_documents` table, grouped by category. |
| `schedules.php` | `GET /api/schedules` | Returns vessel schedule data (Excel-processed) for current week. |
| `novedades.php` | `GET /api/novedades` | Returns news/announcements with category and date. |
| `cotizacion.php` | `POST /api/cotizacion` | Receives quotation requests from the services page modal (nombre, empresa, email, etc.). |
| `postulantes.php` | `POST /api/postulantes` | Receives job applications with CV upload from the institutional page. |

## Bridge API (`src/infrastructure/`)

| File | Purpose |
|------|---------|
| `bridge.php` | READ-ONLY database explorer for Antigravity agents. Protected by `X-Bridge-Token` header. Endpoints: `GET /schema`, `GET /data/{table}`. |

## Database

- **Engine:** MySQL
- **Name:** `ama_web`
- **Connection:** Via `.env` file (never hardcoded)
- **Schema & Migrations:** `database/install.sql` (always reflects the current schema constraint map).
- **Seed Data:** `database/seed_employees.sql`
- **Migraciones idempotentes:** cada alteración nueva va envuelta en una stored procedure que consulta `information_schema.COLUMNS` antes de ejecutar. Esto permite ejecutar `install.sql` repetidamente sin error ("columna ya existe"). Columnas agregadas por esta vía: `usuarios_admin.username`, `usuarios_admin.is_active`, `usuarios_admin.ultimo_login`, `usuarios_admin.password_updated_at`, `usuarios_admin.reset_token`, `usuarios_admin.reset_expires`.

> **Note on Core Identity:** The `usuarios_admin` table recently migrated from `email`-based UNIQUE authentication to `username`-based `UNIQUE` authentication. This satisfies the business rule to allow multiple users to share a departmental inbox (e.g., `finance@ama-demo.example`) without sharing actual portal access.

### Tabla `admin_audit_logs` (auditoría completa del portal)

Cada acción sensible del portal administrativo deja traza en `admin_audit_logs` (`id`, `user_id`, `accion`, `detalles`, `ip_address`, `creado_en`). No se borra nunca: es append-only por diseño para cumplir trazabilidad regulatoria.

Catálogo de acciones emitidas por los módulos actuales:

| Módulo | Vista | Acciones `admin_audit_logs.accion` |
|--------|-------|------------------------------------|
| Autenticación | `auth.php`, `index.php` | `login_exitoso`, `login_fallido`, `logout` |
| Usuarios | `usuarios.php`, `usuarios_form.php` | `user_create`, `user_update`, `user_block`, `user_unblock`, `user_password_reset_sent`, `user_password_manual_set` |
| Perfil | `perfil.php` | `self_password_update` |
| Recuperación | `forgot_password.php`, `reset_password.php` | `password_reset_request`, `password_reset_success` |
| Tipo de cambio | `caja.php` | `exchange_rate_create`, `exchange_rate_update` |
| Schedules | `cs.php` | `schedule_import`, `schedule_delete` |
| Novedades | `cs.php` | `novedad_create`, `novedad_update`, `novedad_delete` |
| Documentación pública | `docs.php` | `doc_upload`, `doc_replace`, `doc_delete` |
| RRHH - Empleados | `rrhh_empleados.php`, `rrhh_empleados_form.php` | `empleado_create`, `empleado_update`, `empleado_delete` |
| RRHH - Vacantes | `rrhh_vacantes.php`, `rrhh_vacantes_form.php` | `vacante_create`, `vacante_update`, `vacante_delete`, `vacante_toggle` |
| RRHH - Postulantes | `rrhh_postulantes.php` | `postulante_status_update`, `postulante_delete` |
| Leads web | `leads.php` | `lead_toggle_leido`, `leads_mark_all_read` |
| Mantenimiento | `mantenimiento.php` | `clear_app_log`, `toggle_maintenance` |
| Email / SMTP | `email_test.php` | `smtp_config_update`, `email_test_send` |

Query canónica de auditoría (con JOIN para ver quién hizo qué):

```sql
SELECT a.creado_en, u.username, a.accion, a.detalles, a.ip_address
  FROM admin_audit_logs a
  LEFT JOIN usuarios_admin u ON u.id = a.user_id
 ORDER BY a.id DESC
 LIMIT 50;
```

## Error Handling

- **404:** `/404.php` — AMA branded
- **500:** `/500.php` — AMA branded
- **Logging técnico:** `src/core/logger.php` → `src/data/logs/app.log` (append mode 3)
- **Audit de admin:** tabla `admin_audit_logs` (ver sección Database)
- **Audit de email:** `src/data/logs/email_audit.log` (JSONL append-only)

## Logs del sistema

| Archivo | Contenido | Consumo |
|---------|-----------|---------|
| `src/data/logs/app.log` | Errores PHP, conexión a BD, trazas `EmailService:` y `database.php:` | Visor `mantenimiento.php` (tail últimas 200 líneas). Descargable por FTP. |
| `src/data/logs/email_audit.log` | JSONL — una línea JSON por envío de email con `timestamp`, `channel`, `recipient`, `subject`, `status` (success/error), `error` (nullable) | Visor `mantenimiento.php` + `email_test.php` vía `EmailService::getAuditTail()` |
| `src/data/logs/debug.log` | Traces ad-hoc del desarrollo | Diff local |
| PHP error log (ruta según `ini_get('error_log')`) | Errores críticos de PHP nivel plataforma | Visor `mantenimiento.php` (lectura protegida) |

Todos los `.log` están bloqueados a público vía `.htaccess` en `src/data/logs/`. Solo son accesibles para `admin_global` vía panel.

## Adding New Modules

1. Create your logic in `src/logic/api/` (e.g., `src/logic/api/new_endpoint.php`)
2. Include `require_once ROOT_DIR . 'src/core/database.php'` for DB access
3. Use `logInfo()`, `logError()` from logger
4. Route is automatic via `index.php` front controller: `GET /api/new_endpoint`
5. No core modifications needed — plug-and-play architecture
