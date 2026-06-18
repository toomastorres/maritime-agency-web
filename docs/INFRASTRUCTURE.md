# Infrastructure & Deployment — AMA Web Project

**Versión:** 3.1 (SMTP config UI + Audit expandido — Abril 2026)

## Hosting

- **Provider:** el hosting (el hosting)
- **Type:** Shared hosting with PHP + MySQL
- **Domain:** `ama-demo.example`

## Database

- **Engine:** MySQL
- **Database:** `ama_web`
- **Management:** phpMyAdmin (hosting panel)
- **Connection:** Centralized in `src/core/database.php` via `.env`

## Environment Variables (`.env`)

```env
APP_ENV=production
DB_HOST=localhost
DB_USER=ama_web
DB_PASS=<secret>
DB_NAME=ama_web
BRIDGE_TOKEN=<secret>
HEALTH_TOKEN=<secret_random>
ADMIN_USER=admin
ADMIN_PASS_HASH=<bcrypt_hash>
```

> **IMPORTANT:** `.env` is blocked by `.htaccess` and `.gitignore`. Use `.env.example` as template.

### APP_ENV
Controla la pantalla de error de `src/core/database.php`:
- `development` → muestra la caja roja con pistas de debug (`.env`, `DB_HOST`, etc.). **Nunca usar en producción.**
- `production` (default seguro si no se define) → muestra solo "Servicio temporalmente no disponible".

## Primer deploy / Restauración del `.env`

El workflow de FTP (`.github/workflows/deploy.yml`) **excluye** `**/.env*` del sync. Eso es lo correcto: el `.env` nunca debe viajar por Git ni por la pipeline. Pero tiene una consecuencia importante: **después del primer deploy, el `.env` hay que subirlo manualmente por FTP a `/public_html/.env`**. Si ese archivo se borra, se corrompe, o nunca se subió, PHP recibe credenciales vacías y `PDO` falla con `SQLSTATE[HY000] [2002]` / connection refused / access denied, y el sitio deja de mostrar contenido dinámico.

### Checklist de verificación del `.env` en producción (via FTP)

- [ ] Existe exactamente en `/public_html/.env` (no `.env.txt`, no en subcarpeta).
- [ ] Contiene las 4 variables de BD: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.
- [ ] Contiene `APP_ENV=production`.
- [ ] Contiene `BRIDGE_TOKEN`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`, `SMTP_FROM_NAME`, `SMTP_REPLY_TO`, `NOTIFY_TO` y todos los `NOTIFY_*`.
- [ ] Contiene `HEALTH_TOKEN` (valor aleatorio largo).
- [ ] Permisos `644` (legible por el usuario PHP).
- [ ] Sin BOM UTF-8 al inicio del archivo.
- [ ] Valores **sin** comillas: `DB_PASS=faDA72zodo`, **no** `DB_PASS="faDA72zodo"`.
- [ ] Fin de línea LF (no CRLF). Si se sube desde Windows, usar FTP en modo **binario**.

### Diagnóstico rápido cuando el sitio no carga datos dinámicos

1. Descargar `/public_html/src/data/logs/app.log` por FTP y leer las últimas entradas. El log de Linux (el hosting) tiene mensajes fiables, a diferencia del log local de desarrollo.
2. Interpretar el mensaje:
   - `Connection refused` / `Can't connect to local MySQL server through socket` → `DB_HOST` incorrecto. Probar `127.0.0.1`.
   - `Access denied for user` → password o usuario equivocados en `.env`.
   - `Unknown database` → la BD fue renombrada o borrada.
   - `Missing env: DB_HOST,...` (añadido en el log por `database.php`) → `.env` no existe o no se está leyendo.
3. Llamar a `/api/health?token=<HEALTH_TOKEN>` para obtener un status estructurado sin exponer credenciales.

## Healthcheck (`/api/health`)

Endpoint permanente y protegido para monitoreo externo (UptimeRobot, cron, etc.).

- **Auth:** query `?token=XXX` **o** header `X-Health-Token: XXX` contra la variable `HEALTH_TOKEN` del `.env`.
- **Rate limit:** 10 req/min por IP.
- **Respuesta 200:** `{"status":"ok","db":"ok","env_loaded":true,"error_code":null,"timestamp":"..."}`.
- **Respuesta 503:** `{"status":"error","db":"fail","env_loaded":false|true,"error_code":"ENV_MISSING|08xxx|28xxx|...","timestamp":"..."}`.
- **Respuesta 401:** token inválido o ausente.
- **Nunca** expone password, host, usuario, ni mensajes de error completos. Solo el código `SQLSTATE` de 5 caracteres para clasificar la falla.

## .htaccess Security

| Feature | Status |
|---------|--------|
| **HTTPS Enforcement** | ✅ 301 redirect |
| **GZIP Compression** | ✅ HTML, CSS, JS, JSON, XML, SVG |
| **Browser Caching** | ✅ Images: 1 month, CSS/JS: 1 week, Fonts: 1 month |
| **Directory Listing** | ✅ Disabled (`Options -Indexes`) |
| **Security Headers** | ✅ X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy |
| **Path Blocking** | ✅ `.env`, `src/`, `private/`, `legacy_source/`, `database/`, `.git/`, `docs/`, `composer.json`, `package.json` |
| **WP Redirect** | ✅ `/admin`, `/login`, `/wp-admin`, `/wp-login.php` → `/gestion-interna-agencia/` |

## Error Handling

| Error | Page | Style |
|-------|------|-------|
| 404 | `/404.php` | AMA branded, link to home |
| 500 | `/500.php` | AMA branded, link to home |

## Logging

- **File:** `src/data/logs/app.log`
- **Access:** Blocked by `.htaccess` in `src/data/logs/`
- **Escritura:** Manual via `error_log()` en `database.php` y endpoints críticos. Directorio auto-creado si no existe.

## Email (PHPMailer + SMTP autenticado)

- **Libreria:** PHPMailer 6.9.3 (Composer, PSR-4 autoload). Autoload vía `vendor/autoload.php` cargado **condicionalmente** en `src/core/email.php` (graceful si `vendor/` no está desplegado — no rompe el sitio, solo el subsistema de emails).
- **Transporte:** SMTP autenticado sobre SMTPS (port 465, `PHPMailer::ENCRYPTION_SMTPS`)
- **Servidor:** `smtp.example.com:465` (relay SMTP de el hosting/el hosting)
- **Casilla:** `no-reply@ama-demo.example`
- **From Name:** "AMA Sistemas"
- **Reply-To:** `info@ama-demo.example` (las respuestas van al server IBM Notes de AMA)
- **Código principal:** `src/core/email.php` — clase `EmailService`
- **Templates:** archivos `.html` en `src/email-templates/` con placeholders `{{key}}` reemplazados por `str_replace()`. Variables auto-inyectadas: `{{year}}`.
- **Template actual:** `notification.html` (genérico, usado por `buildHtmlTemplate()`). Crear uno nuevo es colocar el `.html` en el directorio y llamar `renderTemplate('<nombre>', [...])`.
- **Audit log:** `src/data/logs/email_audit.log` — formato JSONL, una línea por envío con timestamp/channel/recipient/subject/status/error. Append-only, lock-exclusive (`LOCK_EX`) para concurrencia.
- **Log técnico:** `src/data/logs/app.log` — error_log mode 3 (append a archivo)
- **Vista de diagnóstico + configuración:** `private/gestion-interna-agencia/views/email_test.php` — solo `admin_global`. Incluye **config UI estilo WordPress** para editar las 8 vars SMTP del `.env` sin FTP, test de envío por canal, y visor del audit (últimos 20).
- **Prohibido:** el uso de `mail()` nativo de PHP.
- **Graceful degradation:** si PHPMailer no está disponible (vendor/ faltante), `send()` retorna `false`, `getDiagnostics()` reporta `'mode' => 'NO DISPONIBLE (vendor/ faltante)'`, y el botón de test en `email_test.php` queda deshabilitado con mensaje explicativo. **El resto del sitio sigue funcionando** — solo las notificaciones por email quedan inertes.

### Variables `.env` de email

```env
SMTP_HOST=smtp.example.com
SMTP_PORT=465
SMTP_SECURE=ssl
SMTP_USER=no-reply@ama-demo.example
SMTP_PASS=<secret>
SMTP_FROM=no-reply@ama-demo.example
SMTP_FROM_NAME=AMA Sistemas
SMTP_REPLY_TO=info@ama-demo.example
```

### Enrutamiento de notificaciones por área

Cada módulo del admin llama a `$emailService->sendNotification($channel, $subject, $html)` y la clase resuelve el destinatario vía `NOTIFY_*`:

| Canal | Variable `.env` | Fallback |
|---|---|---|
| `rrhh` | `NOTIFY_RRHH` | `NOTIFY_TO` → `SMTP_FROM` |
| `caja` | `NOTIFY_CAJA` | `NOTIFY_TO` → `SMTP_FROM` |
| `docs` | `NOTIFY_DOCS` | `NOTIFY_TO` → `SMTP_FROM` |
| `cs` | `NOTIFY_CS` | `NOTIFY_TO` → `SMTP_FROM` |
| `comercial_autos` | `NOTIFY_COMERCIAL_AUTOS` | `NOTIFY_TO` → `SMTP_FROM` |
| `comercial_proyecto` | `NOTIFY_COMERCIAL_PROYECTO` | `NOTIFY_TO` → `SMTP_FROM` |
| `comercial_containers` | `NOTIFY_COMERCIAL_CONTAINERS` | `NOTIFY_TO` → `SMTP_FROM` |

### Configuración SMTP desde el panel admin (WP-like)

El admin `admin_global` puede editar las 8 variables SMTP directamente desde `email_test.php` sin FTP ni deploys. El endpoint usa `updateEnvKey($envPath, $key, $value)`:

- Persistencia regex-based sobre `/public_html/.env` con `LOCK_EX`
- Quote automático si el valor contiene espacios/caracteres especiales vía `addcslashes`
- **No toca** otras claves del `.env` (BD, BRIDGE_TOKEN, HEALTH_TOKEN, NOTIFY_*)
- **Preserva password:** el campo `SMTP_PASS` muestra `••••••••` si ya hay valor y se mantiene si el admin envía ese placeholder o vacío
- **Pre-check:** si `.env` no es escribible (`is_writable` → false), el form se bloquea con alert rojo
- **Post-save redirect** a `?saved=1` para que `config.php` releazca las constantes y el diagnóstico refleje los cambios
- **Auditoría:** `smtp_config_update` con `detalles` = clave modificada (el valor de `SMTP_PASS` nunca se registra)

Para que este UI funcione, el usuario PHP del hosting debe tener permisos de escritura sobre `/public_html/.env` (típicamente 664 con owner PHP o 644 si el dueño es PHP). En el hosting suele funcionar `chmod 644` con el owner por defecto.

### Cobertura del audit de email

El visor de `email_test.php` (últimos 20) y `mantenimiento.php` (últimos 30) lee `EmailService::getAuditTail($n)`. El archivo JSONL es append-only y crece ~200 bytes por envío — a 100 envíos/día son ~7 MB/año, despreciable para el hosting.

## Deployment Flow

1. Developer pushes to `develop` branch on GitHub
2. Human reviewer approves PR to `main`
3. Webhook/manual sync deploys `main` to el hosting hosting
4. `.env` file must exist on server (not in Git)

## Git Branches

| Branch | Purpose |
|--------|---------|
| `main` | Production — what's live on `ama-demo.example` |
| `develop` | Staging — AI/developer work before approval |

## Admin Portal

- **URL:** `/gestion-interna-agencia/` (hidden, not `/admin`)
- **Auth:** Username + bcrypt password (`PASSWORD_DEFAULT`) via `usuarios_admin` table
- **RBAC:** `caja`, `cs`, `rrhh`, `documentacion`, `admin_global`
- **CSRF:** Token-based protection con `hash_equals()` (timing-safe), rotación cada 30 min
- **Session:** PHP native sessions, HttpOnly + SameSite=Strict, fingerprint UA/lang, regeneración cada 15 min, timeout 30 min inactividad
- **Rate limit login:** 3 fallos → delay progresivo (2/4/8s); 5 → bloqueo 30 min; 10 → bloqueo prolongado. Estado en `admin_audit_logs`.

### Política de contraseñas (NIST SP 800-63B)

Implementada en `private/gestion-interna-agencia/includes/password_policy.php`. Aplica en los 3 puntos de cambio de password: `views/usuarios_form.php`, `views/perfil.php`, `reset_password.php`.

Reglas:
- **Mínimo 12 caracteres** (NIST recomienda 8+; elegimos 12 para portal admin).
- **Sin requisitos de complejidad obligatoria** (mayúscula/número/símbolo). NIST lo desaconseja porque empuja patrones predecibles.
- **Blacklist top-10000** passwords más comunes (`private/gestion-interna-agencia/data/common-passwords.txt`, fuente xato.net via SecLists).
- **No puede contener** el username ni el local-part del email (cuando tienen >= 4 caracteres).
- **Sin secuencias triviales** (`123456`, `qwerty`, `password`, `AMA`, etc.).
- **Sin caracteres repetidos** 5 o más veces seguidas.

> El blacklist está dentro de `private/` y `.htaccess` bloquea esa ruta — nunca se sirve al cliente.

### Recuperación de acceso admin (sin recordar contraseña)

Procedimiento puramente manual via phpMyAdmin para no dejar scripts residuales en el servidor. Tiempo estimado: 5 minutos.

**Paso 1 — Generar el par token plano + hash SHA-256** (en local, NUNCA en el server):

```bash
# En PowerShell, CMD o terminal Linux con PHP instalado:
php -r "$t = bin2hex(random_bytes(32)); echo \"PLANO: $t\nHASH: \" . hash('sha256', $t) . \"\n\";"
```

Guardá ambos valores. El **PLANO** va en la URL del browser; el **HASH** va en la BD.

**Paso 2 — Identificar el usuario admin_global a recuperar.** En cPanel → phpMyAdmin → BD `ama_web` → pestaña SQL:

```sql
SELECT id, nombre, username, email, is_active
  FROM usuarios_admin
 WHERE rol_portal = 'admin_global';
```

Anotá el `id` del usuario que vas a recuperar.

**Paso 3 — Aplicar el reset_token** (reemplazando `ID_ELEGIDO` y `HASH_GENERADO`):

```sql
UPDATE usuarios_admin
   SET reset_token   = 'HASH_GENERADO',
       reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR),
       is_active     = 1
 WHERE id = ID_ELEGIDO;
```

**Paso 4 — Establecer la nueva contraseña.** Abrí en el browser:

```
https://ama-demo.example/gestion-interna-agencia/reset_password.php?token=PLANO_GENERADO
```

(El token PLANO, no el hash — el script lo hashea internamente.)

Elegí una contraseña que cumpla la política NIST (12+ chars, no contiene tu username/email, no está en el top-10000). El sistema mostrará un mensaje claro si no cumple.

**Paso 5 — Login** en `https://ama-demo.example/gestion-interna-agencia/` con el username del usuario recuperado y la nueva contraseña.

**Paso 6 — Limpieza inmediata.** Una vez dentro:
1. Ir a **Usuarios Admin** (sidebar) y bloquear (`is_active = 0`) todos los usuarios viejos que no se vayan a usar más.
2. Crear los usuarios reales de producción desde el formulario de **Invitar Usuario**. El sistema enviará un email con un link de reset (válido 3 días) — no necesitás conocer ni transcribir contraseñas.
3. Cambiar tu propia contraseña desde **Mi Perfil** si querés rotarla por una más fácil de recordar para vos.

> **Importante:** NUNCA ejecutes `UPDATE usuarios_admin SET password_hash = '...'` directamente en SQL. El hash bcrypt no es trivial de generar y, lo más importante, salteás la validación NIST de fortaleza. Usá siempre el flujo `reset_token`.

### Workflow de gestión de usuarios

| Acción | Quién puede | Cómo |
|---|---|---|
| Listar usuarios | `admin_global` | Sidebar → **Usuarios Admin** |
| Crear usuario | `admin_global` | Botón **Invitar Usuario**. Envía email con link de reset (3 días) o establece password manualmente |
| Editar datos | `admin_global` | Botón ✏ junto a cada usuario |
| Bloquear / Desbloquear | `admin_global` (no a sí mismo) | Toggle 🚫 / ✅. Soft-delete vía `is_active` |
| Cambiar password ajena | `admin_global` | Editar → campo "Establecer Contraseña Manualmente" |
| Cambiar password propia | Cualquier rol logueado | **Mi Perfil** → requiere password actual |
| Recuperar password olvidada | Cualquier rol | Login → "Olvidé mi contraseña" → email |

Todas las acciones quedan en `admin_audit_logs` con `user_id`, `accion`, `detalles`, `ip_address`, `creado_en`.

### Auditoría rápida

```sql
-- Ver últimas 50 acciones del portal
SELECT a.creado_en, u.username, a.accion, a.detalles, a.ip_address
  FROM admin_audit_logs a
  LEFT JOIN usuarios_admin u ON u.id = a.user_id
 ORDER BY a.id DESC
 LIMIT 50;
```

El mismo query está disponible visualmente en `mantenimiento.php` → sección "Auditoría admin" (con badges color-coded por tipo de acción).

### Cobertura de audit logs

Ver el catálogo completo de acciones (`login_*`, `user_*`, `exchange_rate_*`, `schedule_*`, `novedad_*`, `doc_*`, `empleado_*`, `vacante_*`, `postulante_*`, `lead_*`, `smtp_config_update`, `email_test_send`, `clear_app_log`, `toggle_maintenance`) en `docs/ADMIN_PORTAL.md` → sección "Auditoría — Cobertura Completa".

### Modo mantenimiento

Hay un switch en `mantenimiento.php` que escribe `src/data/maintenance.flag`. Mientras ese archivo exista, el front controller `index.php` devuelve una landing estática a todo el tráfico público, pero el portal admin `/gestion-interna-agencia/` sigue operativo. Útil para deploys con cambio de schema. Auditado como `toggle_maintenance` con `detalles = 'ON'` / `'OFF'`.

## Documentación relacionada

- `docs/BACKEND.md` — arquitectura PHP, endpoints, BD, tabla `admin_audit_logs` completa
- `docs/ADMIN_PORTAL.md` — detalle por vista del portal admin, políticas, recuperación
- `docs/FRONTEND.md` — estructura pública, sistema de estilos, i18n
- `docs/SYSTEMS_AUDIT.md` (si existe) — checklist de producción
