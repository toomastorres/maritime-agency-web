# Admin Portal Architecture — AMA Web Project

**Versión:** 3.1 (PHPMailer + SMTP config UI + Audit expandido — Abril 2026)

---

## Acceso

- **URL:** `/gestion-interna-agencia/` (oculta intencionalmente — no es `/admin` ni `/login`)
- **Redirecciones:** `.htaccess` redirige `/admin`, `/login`, `/wp-admin`, `/wp-login.php` a esta URL.
- **Protección de directorio:** Acceso directo a `/private/` retorna 403 Forbidden.

---

## Autenticación (`includes/auth.php`)

### Login & Identidad
- **Método:** Username + contraseña (hash bcrypt vía `password_hash()` / `password_verify()`).
- **Tabla:** `usuarios_admin` (campos: `id`, `nombre`, `username`, `email`, `password_hash`, `rol_portal`, `is_active`).
- **Cuentas Compartidas:** Múltiples usuarios pueden compartir el mismo `email` (ej: finance@...) pero cada uno posee un `username` y clave única. Esto garantiza trazabilidad en la auditoría.
- **Timing-safe:** Siempre ejecuta `password_verify()`, incluso si el usuario no existe (contra enumeración).

### Gestión de Accesos (Soft-Delete)
- Nunca se elimina un usuario físicamente para no romper el historial de acciones.
- Se utiliza el flag `is_active = 0` (Bloqueado). Si un usuario bloqueado intenta ingresar, el sistema corta la sesión inmediatamente.

### Recuperación de Contraseña (Self-Service)
- URL: `forgot_password.php`
- Flujo: El usuario ingresa su `username`. El sistema busca el correo asociado y le envía un enlace temporal.
- El token temporal es criptográficamente seguro (32 bytes randomizados) e invalida automáticamente a la hora (`reset_expires`).

### Sesiones
- Cookies: `HttpOnly`, `Secure` (auto-detectado), `SameSite=Strict`.
- Regeneración de ID cada 15 minutos.
- Timeout de inactividad: 30 minutos.
- Fingerprinting de sesión (`User-Agent` + `Accept-Language`) — destruye sesión si cambia.

### CSRF
- Token generado con `random_bytes(32)`, rotación cada 30 minutos.
- Verificación timing-safe con `hash_equals()`.

### Rate Limiting (Login)
- Basado en tabla `admin_audit_logs` (IP + acción `login_fallido`).
- 3 intentos fallidos → delay progresivo (2s, 4s, 8s).
- 5 intentos → bloqueo 30 minutos.
- 10+ intentos → bloqueo con mensaje "Contacte al administrador".

---

## Control de Acceso por Rol (RBAC)

| Rol | Secciones Permitidas | Vista por Defecto |
|-----|---------------------|-------------------|
| `caja` | Tipo de Cambio | `caja` |
| `cs` | Schedules + Novedades | `cs` |
| `rrhh` | RRHH (dashboard, empleados, vacantes, postulantes) | `rrhh` |
| `documentacion` | Documentación oficial | `docs` |
| `admin_global` | **Todas las secciones** | `caja` |

**Implementación:** `dashboard.php` define el array `$permissions` con las secciones permitidas por rol. La función `require_role()` en `auth.php` valida acceso en cada vista.

---

## Estructura de Archivos

```
private/gestion-interna-agencia/
├── index.php                   # Login (username + password, CSRF, rate limiting)
├── dashboard.php                # Router RBAC + sidebar + header
├── logout.php                   # Destrucción completa de sesión + cookie
├── forgot_password.php          # Flujo de recuperación paso 1 (envía email con token)
├── reset_password.php           # Flujo de recuperación paso 2 (setea nueva clave)
├── data/
│   └── common-passwords.txt     # Blacklist top-10000 (NIST SP 800-63B)
├── includes/
│   ├── auth.php                 # Sesiones, CSRF, RBAC, fingerprinting, rate limit
│   ├── db.php                   # Proxy → src/core/database.php
│   └── password_policy.php      # Validador NIST SP 800-63B (12+ chars, blacklist, etc.)
└── views/
    ├── caja.php                 # CRUD tipo de cambio + histórico paginado
    ├── cs.php                   # Upload Excel schedules + crear novedades
    ├── docs.php                 # Gestión de documentos (slots predefinidos)
    ├── email_test.php           # Panel SMTP: diagnóstico + configuración WP-like + test + audit viewer
    ├── leads.php                # Bandeja de consultas web (cotización/contacto), toggle leído
    ├── mantenimiento.php        # Visor app.log + email_audit.log + PHP error log + auditoría admin + toggle maintenance mode
    ├── perfil.php               # "Mi perfil": cambio de password propio (requiere password actual)
    ├── rrhh.php                 # Router de sub-vistas RRHH
    ├── rrhh_dashboard.php       # Dashboard con métricas RRHH
    ├── rrhh_empleados.php       # Listado de empleados + búsqueda
    ├── rrhh_empleados_form.php  # Alta/edición de empleados
    ├── rrhh_postulantes.php     # Gestión de postulaciones recibidas + descarga CV
    ├── rrhh_vacantes.php        # Listado de vacantes
    ├── rrhh_vacantes_form.php   # Alta/edición de vacantes
    ├── usuarios.php             # Grilla column-agnostic de usuarios admin (compat pre/post migración)
    └── usuarios_form.php        # Creación/Edición con auto-invitación mail o carga manual de clave
```

> **Vista legacy eliminada:** `novedades.php` (que usaba un JSON mock) fue retirada. Toda la publicación de novedades vive ahora en `cs.php` contra la tabla `novedades` de la BD.

---

## Detalle de Vistas

### `caja.php` — Tipo de Cambio
- **Formulario:** Carga USD (obligatorio) y EUR (opcional) + datos bancarios.
- **Almacenamiento:** Transaccional — inserta en `exchange_rates` y actualiza `bank_details`.
- **Histórico:** Tabla paginada (10 registros/página) con filtro por moneda. Muestra autor, hora de carga.

### `cs.php` — Customer Service
**Dos funcionalidades integradas:**

1. **Upload de Schedules:**
   - Acepta `.xlsx` y `.xls` (parseo con SimpleXLSX/SimpleXLS nativo, sin Composer).
   - Detección dinámica de bloques de buques (columnas B, E, H, K).
   - Extracción de: nombre servicio, semana, buques, voyage codes, puertos, ETA/ETS, feeders.
   - Hash SHA-256 anti-duplicación.
   - Storage: tablas `schedule_imports` + `schedule_port_calls` (transaccional).
   - Historial con botón de eliminación (ON DELETE CASCADE).

2. **Publicar Novedades:**
   - Formulario con título, bajada, cuerpo, categoría (whitelist), etiquetas, imagen y documentos adjuntos.
   - Validaciones: MIME type de imagen (JPG/PNG/WebP, máx. 2MB), docs (PDF/Word/Excel, máx. 10MB).
   - Upload a `/public/uploads/novedades/` y `/public/uploads/novedades/docs/`.
   - Storage: tablas `novedades` + `novedad_documentos`.

### `docs.php` — Documentación
- **Sistema de slots predefinidos:** 4 documentos oficiales con `doc_key` único.
- **Slots:** `declaracion_embarque`, `emision_bl`, `loi_telex_release`, `carta_garantia`.
- **Upsert:** Si el slot ya tiene archivo, reemplaza (elimina anterior del disco).
- **Validación:** MIME type + extensión + tamaño (máx. 10MB).
- **Path traversal protection:** `realpath()` + verificación de directorio permitido.

### `rrhh.php` — Recursos Humanos
Router de sub-vistas con navegación por tabs:

| Sub-vista | Archivo | Función |
|-----------|---------|---------|
| Dashboard | `rrhh_dashboard.php` | Métricas generales |
| Directorio | `rrhh_empleados.php` | Lista de empleados con búsqueda |
| Form Empleado | `rrhh_empleados_form.php` | Alta / Edición de empleados |
| Vacantes | `rrhh_vacantes.php` | Lista de vacantes activas/inactivas |
| Form Vacante | `rrhh_vacantes_form.php` | Alta / Edición de vacantes |
| Postulaciones | `rrhh_postulantes.php` | CVs recibidos con estado |

**Whitelist de acciones:** Solo se permiten: `dashboard`, `empleados`, `empleados_form`, `vacantes`, `vacantes_form`, `postulantes`.

### `usuarios.php` — Administración de Usuarios Admin
- **Column-agnostic:** detecta columnas opcionales presentes en `usuarios_admin` vía `SHOW COLUMNS` antes de armar el `SELECT`. Columnas opcionales reconocidas: `username`, `ultimo_login`, `is_active`, `password_updated_at`. Esto permite que la vista funcione aunque la BD no haya corrido todas las migraciones aún.
- **Toggle bloqueo:** soft-delete vía `is_active = 0/1`. El admin no puede bloquearse a sí mismo (check explícito contra `$_SESSION['user_id']`).
- **Auditoría obligatoria:** cada toggle inserta en `admin_audit_logs` con acción `user_block` / `user_unblock` y detalle "Cambió estado de ID X a N".
- **Graceful error handling:** si el query falla (p.ej. tabla no migrada), se registra en `error_log` y se muestra un alert no bloqueante en `$tableError` sin romper la página.

### `usuarios_form.php` — Alta / Edición de Usuarios
- **Dos modos de alta:**
  1. **Auto-invitación por email:** el sistema genera un `reset_token` (hash SHA-256 del plano), lo guarda con `reset_expires = +72h` y envía al usuario nuevo un correo con el link `reset_password.php?token=<PLANO>` para que elija su propia password. **Nunca** se transcribe ninguna contraseña.
  2. **Carga manual de clave:** el admin escribe la password en el form. Se valida contra la política NIST antes de hashear con `password_hash()`.
- **Edición:** separa los campos "Datos del usuario" y "Establecer Contraseña Manualmente" (opcional — si está vacío, no se toca).
- **Validación NIST:** delega a `password_policy.php` (12+ chars, blacklist top-10000, no contiene username/email, sin secuencias triviales, sin caracteres repetidos 5 o más veces).
- **Auditoría:** `user_create`, `user_update`, `user_password_manual_set`, `user_password_reset_sent`.

### `perfil.php` — Mi Perfil
- Cambio de password de la cuenta propia. Requiere password actual (re-autenticación).
- Valida la nueva contra política NIST.
- Actualiza `password_updated_at`.
- Auditoría: `self_password_update`.

### `leads.php` — Bandeja de Leads Web
- Lista de consultas entrantes de los formularios públicos (cotización desde `/servicios` y contacto desde `/contacto`).
- Filtro por estado leído/no leído, por tipo (cotización vs contacto) y por fecha.
- Toggle de "marcar como leído" individual o masivo.
- **Importante:** no borra leads. Solo cambia `leido = 0/1`.
- Auditoría: `lead_toggle_leido`, `leads_mark_all_read` (con contador de afectados en `detalles`).

### `email_test.php` — Panel Email / SMTP
Panel único para operar el subsistema de emails. Solo `admin_global`. Funcionalidades:

1. **Diagnóstico (grid visual):** muestra `mode`, `phpmailer` (si está disponible), versión de PHPMailer, `host`, `port`, `secure`, `from`, `from_name`, `reply_to`, `auth` (configurado/faltante), `notify_to`, más la tabla completa de canales (`rrhh`, `caja`, `docs`, `cs`, `comercial_*`) con su constante y valor resuelto.
2. **Configuración SMTP estilo WordPress (plugin-like):** sección colapsable que permite editar las 8 variables SMTP (`SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`, `SMTP_FROM_NAME`, `SMTP_REPLY_TO`) directamente contra `/public_html/.env`.
   - **Persistencia:** `updateEnvKey($envPath, $key, $value)` — regex-based, `LOCK_EX`, auto-quote cuando el valor contiene espacios usando `addcslashes`. No toca otras claves del `.env`.
   - **Preservación de secreto:** el campo password muestra `••••••••` si hay valor configurado. Si el admin envía ese placeholder o vacío, se mantiene la password existente. Para cambiarla, hay que escribir la nueva en claro.
   - **Validación:** `SMTP_HOST` y `SMTP_USER` no pueden quedar vacíos, `SMTP_PORT` debe ser numérico, `SMTP_SECURE` solo acepta `ssl`/`tls`.
   - **Chequeo de permisos:** si `/public_html/.env` no es escribible, se muestra un alert rojo y el form se bloquea.
   - **Post-save redirect:** `?saved=1` recarga la página para que `config.php` releazca las constantes y el grid de diagnóstico refleje los cambios al instante.
   - **Auditoría:** `smtp_config_update` con detalle de qué clave cambió (sin loggear nunca el valor de `SMTP_PASS`).
3. **Disparar email de prueba:** dropdown de canales + botón "Enviar". Usa `EmailService::sendNotification($channel, 'Test — AMA Admin', <html>)`.
   - Si PHPMailer o la config no están disponibles, el botón se deshabilita y el motivo se muestra arriba.
   - Alert verde/rojo con el resultado + link a la entrada del audit log correspondiente.
   - Auditoría: `email_test_send`.
4. **Audit viewer (últimos 20 envíos):** tabla con Fecha, Canal, Destinatario, Asunto, Estado (verde/rojo), Error (truncado). Datos vienen de `EmailService::getAuditTail(20)` que lee `src/data/logs/email_audit.log` (JSONL).

### `mantenimiento.php` — Panel de Sistema
Visor consolidado para `admin_global`. Secciones:

1. **Tail de `app.log`:** últimas 200 líneas del log técnico con botón "Vaciar log" (acción auditada como `clear_app_log`).
2. **Audit de emails (JSONL):** últimas 30 entradas de `email_audit.log` renderizadas como tabla (reutiliza `getAuditTail(30)`).
3. **PHP error log:** lee la ruta devuelta por `ini_get('error_log')` y muestra sus últimas líneas, útil para diagnósticos de plataforma.
4. **Auditoría admin (últimas 50 acciones):** tabla con JOIN a `usuarios_admin` para mostrar el nombre del usuario. Badges color-coded por keyword en `accion`:
   - **Rojo:** contiene `delete` / `clear` / `fail` / `block`
   - **Dorado:** contiene `update` / `toggle`
   - **Azul:** contiene `create` / `upload` / `sent`
   - **Verde:** contiene `login_exitoso` / `unblock` / `success`
5. **Toggle modo mantenimiento:** switch on/off que escribe un flag en `src/data/maintenance.flag`. Si está activo, `index.php` (front controller) sirve una landing "volvemos pronto" a todo el tráfico público. El portal admin sigue funcionando normalmente. Auditoría: `toggle_maintenance` con `detalles = 'ON'` o `'OFF'`.

---

## Auditoría — Cobertura Completa

Cada acción sensible del portal deja traza en `admin_audit_logs` (`id`, `user_id`, `accion`, `detalles`, `ip_address`, `creado_en`). Append-only: nunca se borran registros.

### Acciones auditadas por módulo

| Módulo | Acciones |
|--------|----------|
| Auth | `login_exitoso`, `login_fallido`, `logout` |
| Usuarios Admin | `user_create`, `user_update`, `user_block`, `user_unblock`, `user_password_reset_sent`, `user_password_manual_set` |
| Perfil propio | `self_password_update` |
| Recuperación | `password_reset_request`, `password_reset_success` |
| Tipo de cambio | `exchange_rate_create`, `exchange_rate_update` |
| Schedules | `schedule_import`, `schedule_delete` |
| Novedades | `novedad_create`, `novedad_update`, `novedad_delete` |
| Documentación pública | `doc_upload`, `doc_replace`, `doc_delete` |
| Empleados | `empleado_create`, `empleado_update`, `empleado_delete` |
| Vacantes | `vacante_create`, `vacante_update`, `vacante_delete`, `vacante_toggle` |
| Postulantes | `postulante_status_update`, `postulante_delete` |
| Leads | `lead_toggle_leido`, `leads_mark_all_read` |
| Mantenimiento | `clear_app_log`, `toggle_maintenance` |
| Email / SMTP | `smtp_config_update`, `email_test_send` |

### Formato de `detalles`

Texto corto en español con contexto del objeto afectado. Ejemplos:

- `"Baja: Gomez, Juan (ID #47)"` para `empleado_delete`
- `"Puesto: Analista — Depto: RRHH"` para `empleado_create`
- `"Estado: 0 → 1 (activada)"` para `vacante_toggle`
- `"Marcados como leídos: 12"` para `leads_mark_all_read`
- `"Clave actualizada: SMTP_HOST"` para `smtp_config_update` — **nunca** se loggea el valor de `SMTP_PASS`.

### Visualización

- **`mantenimiento.php`** sección "Auditoría admin" → últimas 50 con JOIN a `usuarios_admin` (username visible).
- **SQL directo** (phpMyAdmin) para análisis o exportación — ver query canónica en `BACKEND.md`.

### Retención

No hay purga automática. El volumen esperado (~decenas de acciones/día) hace que la tabla crezca lento. Si en el futuro supera 500k filas, evaluar archivo mensual a `admin_audit_logs_archive` (fuera de alcance actual).

---

## Conexión a BD

Usa `src/core/database.php` (conexión PDO centralizada con credenciales de `.env`).
El archivo `includes/db.php` es un proxy de una línea que delega a la conexión centralizada.

---

## Sistema de Notificaciones Internas

El portal administra eventos críticos reportando a las distintas áreas internas a través de correos electrónicos.

### Enrutamiento (Channel Mapping)
En vez de utilizar un único correo destino, `EmailService::sendNotification($channel, $subject, $body)` despacha mensajes basado en un mapa de constantes configuradas en `.env`:
- **rrhh:** → `NOTIFY_RRHH` (ej: rrhh@ama-demo.example)
- **caja:** → `NOTIFY_CAJA` (ej: finance@...)
- **docs:** → `NOTIFY_DOCS` (ej: docs@...)
- **cs:** → `NOTIFY_CS` (ej: customerserv@...)
- Si la variable no está definida en el hosting/`.env`, se usa por defecto `NOTIFY_TO`.

### Mapeo de Eventos
1. **Tipo de Cambio:** Dispara a `caja` al actualizar la cotización USD/EUR.
2. **Schedules:** Dispara a `cs` al procesar el Excel.
3. **Novedades:** Dispara a `cs` al cargar una noticia oficial.
4. **Documentación:** Dispara a `docs` al subir, actualizar o borrar un archivo.
5. **Vacantes y Empleados:** Disparan a `rrhh` en operaciones del ciclo ABM.
6. **Postulaciones:** Disparan a `rrhh` al procesar un CV público vía API.

El motor se apoya sobre **PHPMailer 6.9.3 + SMTP autenticado** contra `smtp.example.com:465` (SMTPS), con casilla real `no-reply@ama-demo.example` del cPanel de el hosting. Esto reemplaza completamente al `mail()` nativo legacy: pasa SPF nativamente (el hosting está en el include del SPF del dominio), firma DKIM con la clave `mail._domainkey`, y es auditable. Prohibido reintroducir `mail()`.

### Motor de templates

Los cuerpos HTML dejaron de estar hardcodeados en PHP. Cada template vive como archivo `.html` en `src/email-templates/` con placeholders `{{key}}`. `EmailService::renderTemplate($name, $vars)` hace `str_replace` y auto-inyecta `{{year}}`. El facade `buildHtmlTemplate($title, $content)` sigue funcionando para los 13 callers existentes — internamente delega a `renderTemplate('notification', [...])`.

### Audit log de emails

Cada envío escribe una línea JSON en `src/data/logs/email_audit.log`:

```json
{"timestamp":"2026-04-14T10:32:11-03:00","channel":"caja","recipient":"finance@ama-demo.example","subject":"Nueva carga de cotización","status":"success","error":null}
```

Si `status === "error"`, `error` contiene el `ErrorInfo` de PHPMailer (mensaje del servidor SMTP). La clase **nunca** escribe la password al log.

### Gestión runtime desde el portal

La vista `email_test.php` expone toda la configuración SMTP como un plugin de WordPress: el admin puede cambiar host, puerto, usuario, password, from, reply-to sin tocar FTP. La persistencia es contra `/public_html/.env` vía `updateEnvKey()` con `LOCK_EX`. Para diagnóstico y test rápido, consultar la vista **Email / SMTP (`email_test.php`)**.
