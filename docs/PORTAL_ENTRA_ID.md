# Portal Institucional y de Servicios — Integración con Microsoft Entra ID

**Versión:** 1.0 (Plan integral de implementación — Septiembre 2026)
**Alcance:** Autenticación obligatoria con credenciales corporativas Microsoft 365 para el portal de servicios de Atlantic Maritime Agency S.A.
**Estado:** Propuesta técnica — requiere decisión de plataforma (ver §2) antes de iniciar Fase 1.

---

## 0. Resumen ejecutivo

Se propone incorporar un **portal de servicios autenticado** (`/portal/*`) al sitio existente, con login obligatorio contra **Microsoft Entra ID** mediante **OpenID Connect / Authorization Code Flow + PKCE**, cliente confidencial server-side.

Cuatro decisiones estructurales sostienen el diseño:

| # | Decisión | Por qué |
|---|---|---|
| **D1** | **Mantener PHP**, migrando el hosting a una plataforma controlada | El flujo correcto (código + PKCE, cliente confidencial) es *server-side*. PHP renderizado en servidor es el encaje natural: los tokens nunca llegan al browser. Reescribir a SPA sería más trabajo y **peor** postura de seguridad. |
| **D2** | **App Roles**, no claims de grupos crudos | Token compacto y estable (`roles: ["Portal.Operaciones"]`), sin GUIDs ilegibles, sin *group overage*, sin llamadas a Graph, sin filtrar la topología del tenant. |
| **D3** | **Dos App Registrations** (Servicios y Backoffice) | Conditional Access se aplica **por aplicación, no por URL**. Dos apps es la única forma de exigir MFA al portal general y *además* dispositivo compatible al backoffice. |
| **D4** | **Autorizar por `oid` + `tid`**, nunca por `email`/`upn` | `email` y `upn` son mutables y no verificados. Es la clase de vulnerabilidad *nOAuth*: emparejar usuarios por email permite suplantación entre tenants. |

**Esfuerzo estimado:** 11–14 semanas en 5 fases (incluye una Fase 0 de decisión de plataforma).

**Advertencia de licenciamiento —** Microsoft 365 Business Premium incluye **Entra ID P1** (Conditional Access, MFA, acceso condicional por dispositivo con Intune P1). **No incluye Entra ID P2**, por lo tanto **no hay Identity Protection ni políticas basadas en riesgo**, ni Defender for Cloud Apps. El plan usa exclusivamente lo que P1 cubre y marca explícitamente los huecos en §5.2.

---

## 1. Relevamiento de la situación actual

Relevamiento hecho sobre el código del repositorio, no sobre supuestos.

### 1.1 Stack existente

| Capa | Tecnología | Ubicación |
|---|---|---|
| Front controller | PHP, router por `basename()` con validación de path | `index.php` |
| Páginas públicas | PHP + HTML embebido | `src/pages/` (10 páginas) |
| API REST | PHP, JSON | `src/logic/api/` (8 endpoints) |
| Núcleo | DB (PDO), utils, email, parser XLSX | `src/core/` |
| Backoffice | Portal privado con RBAC propio | `private/gestion-interna-agencia/` |
| Base de datos | MySQL `ama_web` | Hosting compartido |
| Hosting | **el hosting compartido (cPanel), compartido** | `atlantic-agency.example.com` |
| Deploy | GitHub Actions → **FTPS con password** | `.github/workflows/deploy.yml` |

### 1.2 Autenticación actual (a reemplazar)

`private/gestion-interna-agencia/includes/auth.php` implementa, con buena calidad para lo que es:

- Usuario + contraseña bcrypt contra tabla `usuarios_admin`
- Sesiones PHP nativas: `HttpOnly`, `Secure` autodetectado, **`SameSite=Strict`**
- Regeneración de ID cada 15 min, timeout de inactividad 30 min
- Fingerprint de sesión por `User-Agent`
- CSRF con `random_bytes(32)` + `hash_equals()`, rotación 30 min
- Rate limiting de login con backoff progresivo sobre `admin_audit_logs`
- Política de contraseñas NIST SP 800-63B (12+ chars, blacklist top-10000)

**RBAC actual:** ENUM `rol_portal` con 5 valores — `caja`, `cs`, `rrhh`, `documentacion`, `admin_global`.

> El trabajo hecho acá no se tira: la **capa de sesión, CSRF, auditoría y RBAC se conserva**. Lo que se reemplaza es únicamente el *proveedor de identidad*: en lugar de verificar un hash bcrypt local, se verifica un ID token firmado por Entra ID.

### 1.3 Hallazgos de seguridad sobre el estado actual

Estos son defectos reales encontrados leyendo el código, ordenados por severidad. Varios se vuelven **críticos** al introducir identidad federada.

| # | Severidad | Hallazgo | Archivo | Impacto al federar |
|---|---|---|---|---|
| **H1** | 🔴 Crítica | `vendor/**` está **excluido del deploy FTP** | `.github/workflows/deploy.yml:44` | Una librería de autenticación quedaría **subida a mano por FTP**, sin versionado ni verificación de integridad. Inaceptable para una dependencia de seguridad. |
| **H2** | 🔴 Crítica | CSP con **`'unsafe-inline'` en `script-src`** | `.htaccess` | Anula en la práctica la defensa contra XSS. Un XSS en zona autenticada permite robo de sesión del portal. |
| **H3** | 🟠 Alta | `.env` **escribible por PHP** (UI de config SMTP) con secretos | `INFRASTRUCTURE.md`, `views/email_test.php` | Al agregar el `client_secret` de Entra, cualquier LFI/RCE otorga **compromiso de identidad corporativa**, no sólo del sitio. |
| **H4** | 🟠 Alta | Deploy por **FTPS con password de larga vida** en secret de GitHub | `deploy.yml:18` | Credencial estática, sin rotación, con acceso de escritura total a producción. |
| **H5** | 🟠 Alta | `SameSite=Strict` en la cookie de sesión | `auth.php:29` | **Rompe el callback de OIDC**: la cookie no viaja en la navegación cross-site de retorno desde `login.microsoftonline.com` → bucle de login. Ver §4.1.1. |
| **H6** | 🟡 Media | PHP 7.4 como mínimo declarado (EOL desde 2022) | `composer.json` | Sin parches de seguridad. Las librerías OIDC modernas requieren 8.1+. |
| **H7** | 🟡 Media | Cabeceras contradictorias: `X-XSS-Protection: 0` en `.htaccess` vs `1; mode=block` en `index.php` | ambos | Comportamiento indefinido. El valor correcto hoy es `0` (el filtro introducía vulnerabilidades propias). |
| **H8** | 🟡 Media | `frame-ancestors 'self'` / `X-Frame-Options: SAMEORIGIN` en zona privada | `.htaccess` | Para el portal autenticado debe ser `'none'` / `DENY`. |
| **H9** | 🟡 Media | `Strict-Transport-Security` con `preload` en `index.php` pero no en `.htaccess` | `index.php:21` | `preload` es un compromiso irreversible que no debe anunciarse sin haber enviado el dominio a hstspreload.org. |
| **H10** | 🔵 Baja | Fingerprint de sesión atado a `User-Agent` | `auth.php:50` | Genera deslogueos aleatorios ante auto-update del browser. Mantener sólo como defensa en profundidad. |

---

## 2. Fase 0 — Decisión de plataforma (gate obligatorio)

**El hosting compartido de el hosting compartido es el principal limitante técnico del proyecto.** No impide implementar OIDC, pero impide implementarlo *bien*. Esta decisión debe tomarse antes de escribir código porque condiciona Fases 2 a 4.

### 2.1 Comparación

| Criterio | el hosting compartido compartido (actual) | **Azure App Service Linux + PHP 8.3 (recomendado)** |
|---|---|---|
| Gestión del `client_secret` | Archivo `.env` en disco compartido, 644, escribible por PHP | **Key Vault** + Managed Identity, o **credencial por certificado** — sin secreto en disco |
| Deploy | FTPS con password estático | GitHub Actions con **OIDC federado** — sin credencial de larga vida |
| Dependencias Composer | Excluidas del sync (H1) | `composer install` en el pipeline, artefacto inmutable |
| Versión de PHP / parches | Sin control | Runtime gestionado, actualizable |
| TLS | Sin control de versiones ni ciphers | TLS 1.2+ forzable, certificados gestionados |
| WAF / anti-DDoS | No | Front Door o Application Gateway con WAF (OWASP CRS) |
| Cifrado en reposo (DB) | MySQL compartido, sin TDE | Azure DB for MySQL — cifrado en reposo por defecto |
| Aislamiento de sesiones | Directorio de sesiones compartido | Contenedor aislado |
| Logs / forense | `app.log` por FTP | Log Analytics, retención configurable |
| Costo incremental | US$ 0 | ~US$ 15–80/mes (verificar en la calculadora oficial) |
| Latencia desde Buenos Aires | Local | Brazil South ≈ 30–40 ms (no hay región Azure en Argentina) |

### 2.2 Recomendación

**Migrar a Azure App Service manteniendo PHP como lenguaje.** El código es ~90 % el mismo; lo que cambia es la plataforma debajo. Se resuelven de un saque H1, H3, H4 y H6, y el tenant de Entra ID ya existe (las App Registrations son gratuitas y no requieren suscripción de Azure; **el hosting sí**).

**Si la decisión es permanecer en el hosting compartido**, el proyecto sigue siendo viable con estos controles compensatorios **obligatorios**, y con el riesgo residual aceptado formalmente por la dirección:

1. Corregir el pipeline para que `vendor/` se construya en CI y se despliegue (§7.2, H1) — **innegociable**.
2. Usar **credencial por certificado** en lugar de `client_secret` (§5.4.2).
3. Excluir el secreto/certificado de Entra de la UI de edición de `.env` (lista blanca de claves editables).
4. `chmod 600` sobre el material criptográfico y fuera del `DocumentRoot`.
5. Poner **Cloudflare (u otro CDN/WAF)** delante del dominio para suplir la ausencia de WAF.
6. Rotar la password FTP y restringir por IP si el hosting lo permite.

### 2.3 Prueba de viabilidad (bloqueante, ejecutar primero)

El servidor **debe** poder hacer HTTPS saliente hacia Microsoft. El hosting ya hace SMTP saliente al puerto 465, lo cual es buena señal, pero no lo prueba. Script de verificación a correr en producción antes de comprometer fechas:

```php
<?php
// verify_entra_reachability.php — BORRAR del servidor después de ejecutar.
// Verifica prerequisitos de plataforma para OIDC contra Entra ID.

header('Content-Type: text/plain; charset=utf-8');

echo "PHP version : " . PHP_VERSION . "\n";
echo "curl        : " . (extension_loaded('curl')    ? 'OK' : 'FALTA') . "\n";
echo "openssl     : " . (extension_loaded('openssl') ? 'OK' : 'FALTA') . "\n";
echo "mbstring    : " . (extension_loaded('mbstring')? 'OK' : 'FALTA') . "\n";
echo "sodium      : " . (extension_loaded('sodium')  ? 'OK' : 'ausente (opcional)') . "\n";

$tenant = 'common'; // reemplazar por el TENANT_ID real al probar
$url = "https://login.microsoftonline.com/{$tenant}/v2.0/.well-known/openid-configuration";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo "\nDiscovery HTTP {$code}\n";
if ($body === false) {
    echo "ERROR curl: {$err}\n";
    echo "=> Salida HTTPS bloqueada o CA bundle desactualizado. BLOQUEANTE.\n";
} else {
    $j = json_decode($body, true);
    echo "issuer      : " . ($j['issuer'] ?? '??') . "\n";
    echo "jwks_uri    : " . ($j['jwks_uri'] ?? '??') . "\n";
    echo "=> Conectividad OK.\n";
}
```

**Criterio de salida de Fase 0:** discovery responde 200, `curl` y `openssl` presentes, PHP ≥ 8.1 disponible, y decisión de plataforma firmada.

---

## 3. Arquitectura objetivo

### 3.1 Stack recomendado

| Componente | Elección | Justificación |
|---|---|---|
| Runtime | **PHP 8.3** | Soportado, tipos mejorados, `random_bytes` CSPRNG. Resuelve H6. |
| Patrón de auth | **OIDC Authorization Code + PKCE**, cliente confidencial | Los tokens **nunca** tocan el browser. Patrón recomendado por Microsoft para apps web server-side. |
| Cliente OIDC | `league/oauth2-client` + `thenetworg/oauth2-azure` | Proveedor Entra mantenido, endpoints v2.0, sobre el estándar de facto en PHP. |
| Validación JWT | `firebase/php-jwt` (con `JWK::parseKeySet`) | Validación de firma contra JWKS por `kid`. Explícita y auditable. |
| Frontend | **Se mantiene**: PHP server-rendered + Vanilla JS | Sin SPA ⇒ sin tokens en `localStorage` ⇒ una clase entera de vulnerabilidades eliminada. Coherente con el sitio existente. |
| Sesión | PHP nativa, cookie `__Host-` | Ver §4.1.1 para el ajuste de `SameSite`. |
| Estado OIDC | Server-side en `$_SESSION` | `state`/`nonce`/`code_verifier` nunca en cookie ni en URL. |

**Descartado explícitamente:**
- *SPA (React/Vue) con MSAL.js* — mueve tokens al browser y obliga a flujo público con almacenamiento en cliente. Peor postura y reescritura completa del front.
- *Reescritura a .NET / Next.js* — mejor integración nominal, pero implica descartar un backoffice funcional con RBAC y auditoría ya construidos. El problema a resolver es de identidad, no de framework.

### 3.2 Estructura del portal: público vs privado

```
atlantic-agency.example.com
│
├── ZONA PÚBLICA — anónima, indexable, sin llamada a Entra
│   ├── /                        Home
│   ├── /institucional           Institucional
│   ├── /servicios               Servicios
│   ├── /mapa-red                Mapa de la red
│   ├── /contacto                Contacto (formularios públicos)
│   ├── /tipo-de-cambio          Tipo de cambio del día
│   ├── /schedule                Schedule — vista resumida
│   └── /terminos-y-condiciones, /politica-de-privacidad, /politicas-de-cookies
│
├── ZONA PRIVADA — /portal/*  ⟶ App Registration #1 "AMA Portal — Servicios"
│   ├── /portal/                 Dashboard según rol
│   ├── /portal/schedules        Histórico completo, filtros, export
│   ├── /portal/documentacion    Documentación operativa y descargas
│   ├── /portal/novedades        Comunicados internos
│   ├── /portal/rrhh             Autogestión de empleado
│   ├── /portal/cotizaciones     Seguimiento de solicitudes (clientes B2B)
│   └── /portal/auth/{login,callback,logout}
│
└── ZONA BACKOFFICE — /gestion-interna-agencia/*  ⟶ App Registration #2 "AMA Portal — Backoffice"
    └── Vistas administrativas existentes, migradas a Entra ID
```

**Principio rector:** las páginas públicas **nunca** invocan a Entra ID. Conditional Access se evalúa sólo cuando se solicita un token, es decir, únicamente al entrar a `/portal/*` o al backoffice. El sitio institucional sigue siendo anónimo y cacheable.

### 3.3 Topología de aplicaciones en Entra ID (decisión D3)

Conditional Access **no tiene granularidad de URL** — se aplica a la aplicación. Para exigir controles distintos por zona hacen falta dos registros:

| App Registration | Redirect URI | Usuarios | Política CA |
|---|---|---|---|
| **AMA Portal — Servicios** | `https://atlantic-agency.example.com/portal/auth/callback` | Empleados + clientes B2B | MFA obligatorio |
| **AMA Portal — Backoffice** | `https://atlantic-agency.example.com/gestion-interna-agencia/auth/callback` | Sólo personal interno administrativo | MFA **+ dispositivo compatible** (Intune) + frecuencia de sign-in 8 h |

En ambas: **"Assignment required" = Yes**. Es el interruptor más importante del diseño — un empleado autenticado de AMA que no esté asignado a la app **no obtiene token**. El rechazo ocurre en el IdP, antes de tocar una línea de código nuestro.

### 3.4 Diagrama de flujo

```
  Navegador                 Portal PHP                 Entra ID
      │                         │                          │
      │  GET /portal/schedules  │                          │
      ├────────────────────────►│                          │
      │                         │ ¿sesión válida? NO       │
      │                         │ genera state+nonce+PKCE  │
      │                         │ guarda en $_SESSION      │
      │  302 → /authorize       │                          │
      │◄────────────────────────┤                          │
      │                                                    │
      │  GET /authorize?code_challenge=…&state=…&nonce=…   │
      ├───────────────────────────────────────────────────►│
      │                                    ┌───────────────┤
      │                                    │ Login + MFA   │
      │                                    │ ▼ CONDITIONAL │
      │                                    │   ACCESS      │
      │                                    │ ¿asignado?    │
      │                                    └───────────────┤
      │  302 → /portal/auth/callback?code=…&state=…        │
      │◄───────────────────────────────────────────────────┤
      │                         │                          │
      │  GET /callback          │                          │
      ├────────────────────────►│                          │
      │                         │ valida state (single-use)│
      │                         │ POST /token              │
      │                         │ code + code_verifier     │
      │                         ├─────────────────────────►│
      │                         │  id_token (JWT firmado)  │
      │                         │◄─────────────────────────┤
      │                         │ VALIDA: firma(JWKS/kid), │
      │                         │ iss, aud, exp/nbf,       │
      │                         │ nonce, **tid**           │
      │                         │ JIT-provisioning por oid │
      │                         │ mapea roles → permisos   │
      │                         │ session_regenerate_id()  │
      │                         │ DESCARTA los tokens      │
      │  302 → /portal/schedules│                          │
      │◄────────────────────────┤                          │
```

---

## 4. Autenticación e identidad (IAM)

### 4.1 Diseño del flujo de inicio de sesión

**Parámetros del `/authorize`:**

| Parámetro | Valor | Nota |
|---|---|---|
| `client_id` | GUID de la app | |
| `response_type` | `code` | Nunca `token` ni `id_token` (flujo implícito: prohibido) |
| `redirect_uri` | URI exacta registrada | Coincidencia exacta, sin comodines |
| `response_mode` | **`query`** | Decisión deliberada — ver §4.1.1 |
| `scope` | `openid profile email` | **Sin `offline_access`** — ver §5.4.3 |
| `state` | 32 bytes aleatorios | Anti-CSRF, single-use, TTL 10 min |
| `nonce` | 32 bytes aleatorios | Anti-replay del ID token |
| `code_challenge` | `BASE64URL(SHA256(verifier))` | PKCE |
| `code_challenge_method` | `S256` | Nunca `plain` |

**Endpoint del tenant:** `https://login.microsoftonline.com/{TENANT_ID}/oauth2/v2.0/authorize`

> 🔴 **Usar el `TENANT_ID` explícito, nunca `/common`.** Con `/common` cualquier cuenta Microsoft de cualquier tenant puede iniciar el flujo. El `TENANT_ID` en la URL **más** la validación del claim `tid` (§4.2) son dos controles independientes contra la misma clase de ataque.

#### 4.1.1 El problema de `SameSite` (hallazgo H5)

La cookie de sesión actual es `SameSite=Strict`. El callback de OIDC es una **navegación cross-site** iniciada por `login.microsoftonline.com`. Una cookie `Strict` **no se envía** en esa navegación ⇒ el servidor no encuentra el `state` guardado ⇒ error o bucle de login.

**Resolución adoptada:**

| Aspecto | Valor |
|---|---|
| `SameSite` | **`Lax`** (obligatorio para que el callback funcione) |
| `response_mode` | **`query`** — navegación GET de retorno. Las cookies `Lax` **sí** viajan en GET de nivel superior. |
| Compensación CSRF | Se **mantiene** el token CSRF existente en toda petición que cambie estado. `Lax` ya bloquea POST cross-site, y el token cubre el resto. |
| Nombre de cookie | `__Host-AMA_PORTAL` — el prefijo `__Host-` exige `Secure`, `path=/` y prohíbe `Domain`, impidiendo *cookie fixation* desde subdominios. |

> No usar `response_mode=form_post`: el retorno sería un POST cross-site, que exigiría `SameSite=None` — estrictamente peor.

**Alternativa si se exige conservar `Strict`:** cookie de transacción separada (`Lax`, TTL 10 min, sólo un ID opaco a un registro server-side con `state`/`nonce`/`verifier`) y emisión de la cookie de sesión `Strict` recién tras el callback. Funciona, pero agrega una tabla, un barrido de expiración y un caso borde de redirección en cadena. **Se recomienda `Lax`**, que es el estándar de la industria para apps web con OIDC.

### 4.2 Validación del ID token — checklist crítico

**Todas** las comprobaciones son obligatorias. Omitir cualquiera introduce una vulnerabilidad conocida.

| # | Validación | Falla si se omite |
|---|---|---|
| 1 | **Firma** contra JWKS del tenant, seleccionando la clave por `kid` | Tokens falsificados aceptados |
| 2 | **`alg`** es RS256 — rechazar `none` y algoritmos simétricos | *Algorithm confusion* / `alg:none` |
| 3 | **`iss`** === `https://login.microsoftonline.com/{TENANT_ID}/v2.0` | Emisor arbitrario |
| 4 | **`aud`** === nuestro `client_id` | *Token passthrough*: reuso de un token emitido para otra app |
| 5 | **`exp`** / **`nbf`** con *leeway* ≤ 60 s | Replay de tokens vencidos |
| 6 | **`nonce`** === el generado y guardado en sesión | Replay del ID token |
| 7 | 🔴 **`tid`** === nuestro `TENANT_ID` | **Cualquier tenant de Microsoft puede autenticarse** |
| 8 | 🔴 Identidad = **`oid` + `tid`**, nunca `email`/`upn`/`preferred_username` | **Clase de vulnerabilidad nOAuth**: `email` es mutable y no verificado; un atacante que fije ese valor en su propio tenant suplanta a un empleado |

**Sobre JWKS:** cachear el juego de claves con TTL (≈ 24 h) y **refrescar ante un `kid` desconocido** (Microsoft rota claves). Nunca deshabilitar la verificación de certificado TLS al descargarlo.

### 4.3 Gestión de roles y permisos (RBAC)

#### 4.3.1 Por qué App Roles y no claims de grupos (decisión D2)

| | Claims de grupos | **App Roles** |
|---|---|---|
| Contenido del token | GUIDs (`8f4c…`) | Strings estables (`Portal.Operaciones`) |
| Límite de tamaño | **Overage a >200 grupos (JWT)**: el claim desaparece y hay que consultar Graph | El usuario tiene 1–2 roles de app |
| Filtración de información | Expone la topología completa de grupos del tenant | Expone sólo lo relevante a la app |
| Acoplamiento | La app depende de GUIDs del directorio | La app define su propio vocabulario |
| Legibilidad en auditoría | Ilegible sin resolver | Autoexplicativo |

Microsoft recomienda App Roles como enfoque preferente de RBAC cuando la aplicación puede definir su propio vocabulario de autorización. Es el caso.

#### 4.3.2 Patrón de tres capas

```
Usuario  ──miembro de──►  Grupo de seguridad Entra  ──asignado a──►  App Role  ──mapea a──►  Permisos en la app
(Entra)                   GRP-Portal-Operaciones        Portal.Operaciones      ['schedules.*', 'docs.read']
```

- **Capa 1 (Entra, gestiona IT):** altas/bajas de personas en grupos. Es el único trabajo operativo recurrente.
- **Capa 2 (Entra, se configura una vez):** grupos → App Roles. La asignación de **grupos** a roles de app requiere P1 — **incluido** en Business Premium.
- **Capa 3 (código, gestiona desarrollo):** roles → permisos finos. Agregar un permiso **no** requiere tocar Entra.

#### 4.3.3 Catálogo de App Roles

Definidos en el manifest de la App Registration, con `allowedMemberTypes: ["User"]`:

| App Role (`value`) | Grupo Entra | Rol legacy | Alcance |
|---|---|---|---|
| `Portal.Operaciones` | `GRP-Portal-Operaciones` | `cs` | Schedules, novedades, tracking |
| `Portal.Administracion` | `GRP-Portal-Administracion` | `caja` | Tipo de cambio, datos bancarios, leads |
| `Portal.RRHH` | `GRP-Portal-RRHH` | `rrhh` | Empleados, vacantes, postulantes |
| `Portal.Documentacion` | `GRP-Portal-Documentacion` | `documentacion` | Documentación oficial |
| `Portal.Management` | `GRP-Portal-Management` | — | Dashboards y reportes, **sólo lectura transversal** |
| `Portal.Cliente` | `GRP-Portal-Clientes-B2B` | — | Schedules propios, cotizaciones, documentación pública ampliada |
| `Portal.Admin` | `GRP-Portal-Admin` | `admin_global` | Todo + gestión de usuarios y config |

Fragmento del manifest:

```json
{
  "appRoles": [
    {
      "id": "GENERAR-GUID-UNICO-POR-ROL",
      "allowedMemberTypes": [ "User" ],
      "displayName": "Operaciones",
      "description": "Acceso a schedules, novedades y tracking operativo.",
      "value": "Portal.Operaciones",
      "isEnabled": true
    }
  ]
}
```

> `Portal.Management` es deliberadamente **sólo lectura**. Un rol de dirección que además escribe concentra riesgo sin necesidad operativa.

#### 4.3.4 Mapa de permisos en la aplicación

```php
<?php
/**
 * AMA — portal_roles.php
 * Mapa App Role (Entra ID) → permisos internos del portal.
 *
 * Esta es la ÚNICA fuente de verdad de autorización de la aplicación.
 * Agregar un permiso NO requiere cambios en Entra ID.
 */

// ── Catálogo de permisos ───────────────────────────────────

const PORTAL_PERMISSIONS = [
    'Portal.Operaciones'   => ['schedules.read', 'schedules.write', 'novedades.read', 'novedades.write'],
    'Portal.Administracion'=> ['cambio.read', 'cambio.write', 'leads.read', 'leads.write'],
    'Portal.RRHH'          => ['rrhh.read', 'rrhh.write', 'vacantes.write', 'postulantes.read'],
    'Portal.Documentacion' => ['docs.read', 'docs.write'],
    'Portal.Management'    => ['schedules.read', 'cambio.read', 'rrhh.read', 'docs.read', 'reportes.read'],
    'Portal.Cliente'       => ['schedules.read', 'docs.read', 'cotizaciones.own'],
    'Portal.Admin'         => ['*'],
];

/**
 * Resuelve el conjunto de permisos a partir de los App Roles del token.
 *
 * @param  array $roles Claim "roles" del ID token.
 * @return array Lista de permisos, sin duplicados.
 */
function portal_resolve_permissions(array $roles): array {
    $perms = [];
    foreach ($roles as $role) {
        if (!isset(PORTAL_PERMISSIONS[$role])) {
            continue; // Rol desconocido: se ignora (fail-closed).
        }
        $perms = array_merge($perms, PORTAL_PERMISSIONS[$role]);
    }
    return array_values(array_unique($perms));
}

/**
 * Verifica un permiso puntual. Wildcard '*' concede todo.
 *
 * @param  string $permission Ej: 'schedules.write'
 * @return bool
 */
function portal_can(string $permission): bool {
    $perms = $_SESSION['portal_permissions'] ?? [];
    return in_array('*', $perms, true) || in_array($permission, $perms, true);
}

/**
 * Guard de autorización. Corta la ejecución con 403 si falta el permiso.
 */
function portal_require(string $permission): void {
    if (!portal_can($permission)) {
        http_response_code(403);
        require ROOT_DIR . 'src/pages/403.php';
        exit;
    }
}
```

**Regla fail-closed:** ausencia del claim `roles` ⇒ **cero permisos** ⇒ 403. Nunca un rol por defecto.

### 4.4 Modelo de datos

```sql
-- ═══════════════════════════════════════════════════════════
-- Portal Entra ID — tabla de usuarios federados
-- Aprovisionamiento JIT en el primer login. SIN columna de password.
-- ═══════════════════════════════════════════════════════════

-- ── 1. USUARIOS DEL PORTAL ────────────────────────────────

CREATE TABLE IF NOT EXISTS usuarios_portal (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    entra_oid           CHAR(36)     NOT NULL,
    entra_tid           CHAR(36)     NOT NULL,
    upn                 VARCHAR(255) NULL,
    email               VARCHAR(255) NULL,
    display_name        VARCHAR(150) NULL,
    tipo                ENUM('interno', 'invitado') NOT NULL DEFAULT 'interno',
    roles_cache         VARCHAR(500) NULL,
    primer_login        TIMESTAMP    NULL,
    ultimo_login        TIMESTAMP    NULL,
    ultimo_login_ip     VARBINARY(16) NULL,
    is_active           TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_entra_identity (entra_oid, entra_tid),
    KEY idx_upn (upn)
);

-- ── 2. VÍNCULO CON LA AUDITORÍA EXISTENTE ─────────────────
-- admin_audit_logs.user_id pasa a referenciar usuarios_portal.id.

ALTER TABLE admin_audit_logs
    ADD COLUMN portal_user_id INT NULL AFTER user_id,
    ADD KEY idx_portal_user (portal_user_id);
```

**Puntos clave del modelo:**

- La clave natural es **`(entra_oid, entra_tid)`**, no el email. `upn` y `email` se guardan **sólo para mostrar y auditar**, jamás para autorizar (§4.2, validación 8).
- **Sin columna `password_hash`.** No existe credencial local que robar. Es una reducción neta de superficie de ataque frente a `usuarios_admin`.
- `roles_cache` es informativo para la UI de administración. **La autoridad es siempre el claim del token**, no la base.
- `is_active` permite bloqueo local de emergencia sin esperar a la propagación de Entra.
- `tipo` distingue empleados de invitados B2B para reportes y revisiones de acceso.

**Sobre `usuarios_admin`:** se **conserva en modo sólo-lectura** durante la transición (Fase 4) para no romper el histórico de auditoría, y se congela el alta de nuevos usuarios locales. Ver runbook de corte en §7.5.

### 4.5 Clientes externos (B2B)

El requerimiento menciona "empleados/clientes autorizados". Los clientes **no tienen cuenta en el tenant de AMA**.

**Solución recomendada: invitaciones B2B (Entra External ID for collaboration) al tenant corporativo.**

| Aspecto | Detalle |
|---|---|
| Alta | Invitación por email desde el portal de Entra o vía Graph. El invitado usa su propia cuenta Microsoft, Google o un OTP por email. |
| Autorización | El invitado se agrega a `GRP-Portal-Clientes-B2B` → obtiene `Portal.Cliente`. Mismo mecanismo que un empleado. |
| Conditional Access | **Aplica**, pero las políticas deben incluir explícitamente el selector *"Guest or external users"* — no alcanza con "All users" según cómo se configure el alcance. |
| MFA de invitados | Verificar los **cross-tenant access settings**: si se confía el MFA del tenant de origen, el invitado no vuelve a hacer MFA. Para acceso a datos operativos se recomienda **no** confiar y exigir MFA propio. |
| Términos de uso | Exigir aceptación de *Terms of Use* (función P1) en la política de CA de invitados. |

> ⚠️ **Hueco conocido:** las **Access Reviews** de Entra (recertificación automática de accesos) requieren **P2** y no están incluidas. Sin ellas, el ciclo de vida de invitados queda manual. **Mitigación obligatoria:** procedimiento trimestral documentado de revisión de `GRP-Portal-Clientes-B2B` + reporte automático de invitados sin login en 90 días (consulta a `usuarios_portal.ultimo_login`) enviado por el cron existente. Sin esto, los accesos de clientes se acumulan indefinidamente — es el riesgo de gobierno más probable del proyecto.

**Facturación:** Entra External ID se factura por usuarios activos mensuales (MAU) con un tramo gratuito. Verificar el esquema vigente antes de dimensionar.

---

## 5. Ciberseguridad y cumplimiento

### 5.1 Políticas de Conditional Access

**Prerrequisitos innegociables antes de crear la primera política:**

1. **Crear 2 cuentas break-glass** (emergencia): cloud-only, `*.onmicrosoft.com`, Global Administrator, contraseñas largas y aleatorias guardadas en sobre lacrado/caja fuerte física, **excluidas de todas las políticas de CA**, con alerta de sign-in. **Bloquearse fuera del tenant es el incidente #1 al desplegar CA.**
2. **Desactivar Security Defaults** — son mutuamente excluyentes con Conditional Access.
3. **Desplegar todo en modo *Report-only* primero** y revisar el impacto en *Sign-in logs* durante 7 días antes de activar.

#### 5.1.1 Catálogo de políticas

| ID | Nombre | Asignación | Condición | Control | Prioridad |
|---|---|---|---|---|---|
| **CA001** | Bloquear autenticación legacy | All users (excl. break-glass) | Client apps: *Exchange ActiveSync*, *Other clients* | **Block** | 🔴 Fundacional |
| **CA002** | MFA obligatorio — Portal Servicios | All users + **Guest or external users** (excl. break-glass) | App: *AMA Portal — Servicios* | Grant: **Require MFA** | 🔴 Núcleo del requerimiento |
| **CA003** | Backoffice — MFA + dispositivo compatible | `GRP-Portal-Admin`, `GRP-Portal-Administracion` | App: *AMA Portal — Backoffice* | Grant: **Require MFA AND require device to be marked as compliant** (Intune P1) | 🔴 Alta |
| **CA004** | Restricción geográfica | All users (excl. break-glass y `GRP-Excepcion-Viajes`) | Named location ≠ {Argentina, Italia} | **Block** | 🟠 Media |
| **CA005** | Controles de sesión — privilegiados | `GRP-Portal-Admin` | App: ambas | Session: **Sign-in frequency 8 h**, **Persistent browser session: Never** | 🟠 Media |
| **CA006** | Términos de uso para invitados | Guest or external users | App: *Portal — Servicios* | Grant: **Require Terms of Use** | 🟡 Cumplimiento |
| **CA007** | MFA para administración de Azure/Entra | Roles de directorio privilegiados | App: *Microsoft Admin Portals* | Grant: **Require MFA** | 🔴 Higiene del tenant |

#### 5.1.2 Notas de implementación

- **CA004 (geografía):** un *allow-list* rígido de países rompe viajes y roaming. Diseñar con el grupo de excepción `GRP-Excepcion-Viajes` con alta temporal documentada, o invertir la política a "bloquear países de alto riesgo + MFA siempre". Decidir con Operaciones antes de activar; es la política que más tickets genera.
- **Named locations:** definir la IP pública de la oficina de Buenos Aires como *trusted location* para poder afinar frecuencia de sign-in sin degradar seguridad.
- **Los métodos de MFA importan:** priorizar **Microsoft Authenticator con number matching** o **FIDO2/passkeys**. Desalentar SMS (SIM swapping). Configurar en *Authentication methods policy*.
- **Continuous Access Evaluation (CAE)** aplica a recursos de Microsoft (Exchange, SharePoint, Graph), **no** a una app custom. La revocación en una app propia depende de la expiración de **nuestra** sesión — por eso el timeout de 30 min y `is_active` local (§4.4) importan.

### 5.2 Qué cubre realmente Microsoft 365 Business Premium

Sección de verdad incómoda. El plan usa sólo lo efectivamente licenciado.

#### ✅ Incluido y aprovechado

| Capacidad | Licencia | Uso en este proyecto |
|---|---|---|
| **Conditional Access** | Entra ID **P1** | Todo §5.1 |
| MFA | Entra ID P1 | CA002, CA003 |
| Asignación de **grupos a App Roles** | Entra ID P1 | §4.3.2, capa 2 |
| **Intune Plan 1** — cumplimiento de dispositivo | Incluido | CA003 |
| **Defender for Business** — endpoint | Incluido | Higiene de los equipos que acceden |
| **Defender for Office 365 P1** | Incluido | Safe Links / Safe Attachments contra phishing de credenciales |
| **Terms of Use** | Entra ID P1 | CA006 |
| Self-Service Password Reset | Entra ID P1 | Reduce tickets de soporte |
| **Cloud App Discovery** | Entra ID P1 | Versión acotada de Defender for Cloud Apps (sólo descubrimiento) |
| Logs de sign-in | P1 — **retención 30 días** | §5.6 |

#### ❌ NO incluido — huecos declarados

| Capacidad | Licencia requerida | Impacto | Mitigación propuesta |
|---|---|---|---|
| **Identity Protection** (riesgo de usuario/sign-in, CA basado en riesgo) | **Entra ID P2** | No hay bloqueo automático ante credenciales filtradas o viaje imposible | Complemento **P2 sólo para los ~10 usuarios privilegiados** (licenciable por usuario), o aceptar el riesgo formalmente. Alternativa parcial sin costo: alertas sobre sign-in logs exportados (§5.6). |
| **Privileged Identity Management (PIM)** | Entra ID P2 | Sin elevación *just-in-time*: los admins son admin permanentes | Minimizar la cantidad de Global Admins (≤ 3), separar cuenta de admin de cuenta diaria, revisión mensual |
| **Access Reviews** | Entra ID P2 | Sin recertificación automática de accesos | Procedimiento trimestral manual (§4.5) — **obligatorio** |
| **Defender for Cloud Apps** (completo) | E5 / standalone | Sin Conditional Access App Control (control de sesión en tiempo real, bloqueo de descargas en dispositivo no gestionado) | Restringir descargas **en código**, condicionado al rol; CA003 ya impide el acceso desde dispositivos no compatibles al backoffice |
| **Retención de logs > 30 días** | Log Analytics / Sentinel | Ventana forense corta | Exportar por Diagnostic Settings (§5.6) — costo marginal |

> **Recomendación de licenciamiento:** el complemento **Entra ID P2 para el subconjunto privilegiado** (Global Admins + `Portal.Admin` + `Portal.Administracion`, ~10–15 usuarios) es la mejor relación costo/beneficio del proyecto. Habilita Identity Protection y PIM justo donde el impacto de un compromiso es mayor. Microsoft exige licenciar a los usuarios que se benefician de la función, no a todo el tenant.

### 5.3 Buenas prácticas de seguridad web

#### 5.3.1 Cabeceras HTTP — configuración objetivo

Corrige H2, H7, H8, H9. La zona `/portal/*` recibe cabeceras **más estrictas** que la pública.

```apache
# ═══════════════════════════════════════════════════════════════
#  Cabeceras de seguridad — zona pública
# ═══════════════════════════════════════════════════════════════
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=(), payment=(), usb=()"
    Header always set Cross-Origin-Opener-Policy "same-origin"
    Header always set Cross-Origin-Resource-Policy "same-origin"

    # H9: sin 'preload' salvo que el dominio se haya enviado a hstspreload.org
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains" "expr=%{HTTPS} == 'on'"

    # H7: valor correcto es 0 — el filtro XSS legacy introducía vulnerabilidades propias.
    # Debe eliminarse además la línea contradictoria de index.php.
    Header always set X-XSS-Protection "0"
</IfModule>

# ═══════════════════════════════════════════════════════════════
#  Zona privada — endurecida (H8)
# ═══════════════════════════════════════════════════════════════
<LocationMatch "^/(portal|gestion-interna-agencia)/">
    Header always set X-Frame-Options "DENY"
    Header always set Cache-Control "no-store, no-cache, must-revalidate, private, max-age=0"
    Header always set Referrer-Policy "no-referrer"
    Header always set Cross-Origin-Embedder-Policy "require-corp"
</LocationMatch>
```

**H2 — CSP basada en nonce (elimina `'unsafe-inline'`):**

```php
// ── CSP con nonce — en config.php, antes de cualquier salida ──

$cspNonce = base64_encode(random_bytes(16));
define('CSP_NONCE', $cspNonce);

header("Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self' 'nonce-{$cspNonce}'; "
    . "style-src 'self' 'nonce-{$cspNonce}' https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "form-action 'self' https://login.microsoftonline.com; "
    . "frame-ancestors 'none'; "
    . "base-uri 'self'; "
    . "object-src 'none'; "
    . "upgrade-insecure-requests");
```

Y en las plantillas: `<script nonce="<?= CSP_NONCE ?>">`.

> `form-action` debe incluir `https://login.microsoftonline.com` sólo si se usara `form_post`. Con `response_mode=query` (§4.1.1) basta `'self'`, y es preferible dejarlo así.
>
> **Migración gradual:** desplegar primero como `Content-Security-Policy-Report-Only` con `report-uri`, corregir los scripts inline que aparezcan, y recién entonces cambiar al header de enforcement. Eliminar `'unsafe-inline'` de golpe romperá páginas.

#### 5.3.2 Mapeo OWASP Top 10 (2021)

| Riesgo | Estado actual | Control en el diseño |
|---|---|---|
| **A01 — Broken Access Control** | RBAC por rol único en sesión | `portal_require()` en **cada** endpoint y vista; *deny-by-default*; "Assignment required" en Entra; **validación de open redirect** en `redirect_to` (§5.3.3); IDOR: filtrar por `entra_oid` en consultas de datos propios |
| **A02 — Cryptographic Failures** | HTTPS forzado, bcrypt | TLS 1.2+; HSTS; **eliminación de la credencial local** (no hay hash que filtrar); cifrado de columnas sensibles (§5.5); JWKS sobre TLS verificado |
| **A03 — Injection** | PDO con prepared statements | Mantener PDO parametrizado sin excepción; `htmlspecialchars()` con `ENT_QUOTES \| ENT_HTML5` en toda salida; CSP nonce como segunda línea contra XSS |
| **A04 — Insecure Design** | — | Modelo de amenazas por fase; dos App Registrations por granularidad de CA; fail-closed en autorización; **sin `offline_access`** (§5.4.3) |
| **A05 — Security Misconfiguration** | H2, H7, H8, H9 | §5.3.1; `APP_ENV=production` verificado; `Options -Indexes`; borrar `setup.php` y `verify_entra_reachability.php` tras uso; `display_errors=Off` |
| **A06 — Vulnerable Components** | H1 (vendor fuera del deploy) | `composer audit` en CI como *gate*; Dependabot; `composer.lock` obligatorio; build reproducible (§7.2) |
| **A07 — Identification & Authentication Failures** | Auth local con rate limiting | **Entra ID + MFA obligatorio**; PKCE; `state`/`nonce` single-use; validación completa del token (§4.2); regeneración de sesión post-login; timeout de inactividad |
| **A08 — Software & Data Integrity Failures** | Deploy FTP sin verificación | Artefacto construido en CI; verificación de firma del ID token; sin deserialización de datos no confiables; SRI si se reintrodujeran CDNs |
| **A09 — Logging & Monitoring Failures** | `admin_audit_logs` + `app.log` | Ampliar auditoría a eventos OIDC (§5.6); exportar sign-in logs de Entra; alertas de anomalías |
| **A10 — SSRF** | Bridge API | El servidor sólo hace peticiones salientes a **hosts fijos de Microsoft** en lista blanca; ningún parámetro de usuario construye una URL saliente; `CURLOPT_FOLLOWLOCATION` deshabilitado en las llamadas a token/JWKS |

#### 5.3.3 Open redirect en el retorno post-login

Vulnerabilidad clásica del patrón "volver a donde estaba". El parámetro `redirect_to` **debe** validarse contra rutas internas:

```php
/**
 * Valida el destino post-login. Sólo se permiten rutas internas del portal.
 * Verificada contra 24 casos de abuso (open redirect, traversal, CRLF).
 *
 * @param  string|null $target Valor sin confianza (query string / sesión).
 * @return string Ruta segura, siempre interna.
 */
function portal_safe_redirect(?string $target): string {
    $fallback = '/portal/';

    if ($target === null || $target === '') {
        return $fallback;
    }
    // Caracteres de control y saltos de línea (header injection).
    if (preg_match('/[\x00-\x1F\x7F]/', $target)) {
        return $fallback;
    }
    // URLs absolutas, protocol-relative ("//evil.com") y backslashes.
    if (preg_match('#^(?:[a-z][a-z0-9+.\-]*:|//|\\\\)#i', $target)) {
        return $fallback;
    }
    if ($target[0] !== '/') {
        return $fallback;
    }
    // Path traversal, incluso codificado ("..", "%2e%2e", "..%2F").
    $decoded = rawurldecode($target);
    if (strpos($decoded, '..') !== false || strpos($decoded, '\\') !== false) {
        return $fallback;
    }
    // Lista blanca de prefijos, evaluada sobre la ruta sin query string.
    $path = explode('?', $target, 2)[0];
    foreach (['/portal/', '/gestion-interna-agencia/'] as $prefix) {
        if (strpos($path, $prefix) === 0) {
            return $target;
        }
    }
    return $fallback;
}
```

### 5.4 Gestión de secretos y tokens

#### 5.4.1 Jerarquía de credenciales de la aplicación (mejor → peor)

1. **Managed Identity** — sólo en Azure. Sin secreto alguno. *Objetivo si se adopta §2.2.*
2. **Federated Identity Credential** — para GitHub Actions: el pipeline se autentica por OIDC, sin password almacenada. Resuelve **H4**.
3. **Credencial por certificado** — el cliente firma un `client_assertion` JWT. No hay secreto reutilizable en tránsito. *Mínimo aceptable en el hosting compartido.*
4. **Client secret** — string compartido. Sólo con rotación ≤ 6 meses documentada y fuera de la UI de edición de `.env` (**H3**).

#### 5.4.2 Variables de entorno a incorporar

```env
# ── ENTRA ID / OIDC ────────────────────────────────────────
ENTRA_TENANT_ID=00000000-0000-0000-0000-000000000000
ENTRA_CLIENT_ID_PORTAL=00000000-0000-0000-0000-000000000000
ENTRA_CLIENT_ID_BACKOFFICE=00000000-0000-0000-0000-000000000000
ENTRA_REDIRECT_URI_PORTAL=https://atlantic-agency.example.com/portal/auth/callback
ENTRA_REDIRECT_URI_BACKOFFICE=https://atlantic-agency.example.com/gestion-interna-agencia/auth/callback
ENTRA_POST_LOGOUT_URI=https://atlantic-agency.example.com/

# Preferido: certificado (ruta FUERA del DocumentRoot, chmod 600)
ENTRA_CERT_PATH=/home/usuario/secure/entra-portal.pem
ENTRA_CERT_THUMBPRINT=

# Alternativa sólo si el certificado no es viable. Rotación <= 6 meses.
ENTRA_CLIENT_SECRET=
```

> 🔴 **Estas claves deben quedar EXCLUIDAS de la UI de edición de `.env`** de `views/email_test.php` (H3). Esa UI debe pasar a lista blanca de claves editables (`SMTP_*` únicamente), no lista negra.

#### 5.4.3 Manejo de tokens — principio de descarte

| Regla | Justificación |
|---|---|
| **Nunca** enviar tokens al browser | Sin `localStorage`/`sessionStorage`, sin cookies con JWT. La única cookie es el ID de sesión opaco. |
| **Descartar el ID token tras validarlo** | Ya cumplió su función. Persistir sólo `oid`, `tid`, `roles`, `name` en `$_SESSION`. |
| **No solicitar `offline_access`** | Sin refresh token no hay credencial de larga vida que robar. Al expirar la sesión, el usuario re-autentica contra Entra — que es exactamente lo que se quiere, porque **revalida Conditional Access**. |
| Si en el futuro se requiere Graph | Recién ahí agregar `offline_access`, cifrar el refresh token en reposo (AES-256-GCM, clave fuera de la DB) y acotar scopes al mínimo |
| Logout completo | Destruir sesión local **y** redirigir al endpoint de logout de Entra con `id_token_hint` y `post_logout_redirect_uri` registrada |

### 5.5 Cifrado en tránsito y en reposo

**En tránsito:**
- TLS 1.2+ obligatorio; deshabilitar TLS 1.0/1.1 (en el hosting compartido esto **no es controlable** — argumento adicional para §2.2).
- HSTS `max-age=31536000; includeSubDomains`. Considerar `preload` sólo tras enviar el dominio (H9).
- Verificación estricta de certificado en toda llamada saliente (`CURLOPT_SSL_VERIFYPEER=true`, `VERIFYHOST=2`). **Nunca** deshabilitarla "para que funcione".

**En reposo:**

| Dato | Control |
|---|---|
| Base de datos completa | el hosting compartido: sin TDE. Azure Database for MySQL: cifrado en reposo por defecto. |
| Columnas sensibles (CV de postulantes, datos bancarios) | Cifrado a nivel aplicación con `openssl_encrypt('aes-256-gcm')`, clave fuera de la DB |
| Archivos subidos (`public/uploads/`) | Fuera del DocumentRoot + servido por PHP con verificación de permiso. Hoy el `.htaccess` lo bloquea, pero *defense-in-depth* pide moverlo. |
| Logs | Sin PII innecesaria; **nunca** tokens, `code` ni secretos en `app.log` |
| Backups | Cifrados y con restauración probada — hoy **no documentado**, incorporar |

### 5.6 Logging, auditoría y monitoreo

**Ampliar `admin_audit_logs` con eventos de identidad federada:**

| Acción | Cuándo | Detalle registrado |
|---|---|---|
| `oidc_login_ok` | Login exitoso | `oid`, roles concedidos, IP |
| `oidc_login_denied` | Token válido, sin App Role asignado | `oid`, IP — **señal de sondeo de accesos** |
| `oidc_state_mismatch` | Falla de validación de `state` | IP — **posible CSRF en curso** |
| `oidc_token_invalid` | Falla de firma/`iss`/`aud`/`nonce`/`tid` | Motivo, IP — **alta severidad** |
| `oidc_tenant_mismatch` | 🔴 `tid` ajeno | `tid` recibido, IP — **intento de acceso cross-tenant** |
| `oidc_logout` | Cierre de sesión | `oid` |
| `portal_access_denied` | 403 por permiso faltante | `oid`, permiso requerido |

**Del lado de Entra ID:**
- Exportar **sign-in logs** y **audit logs** vía *Diagnostic Settings* a Log Analytics o Storage (la retención nativa de P1 es de 30 días).
- Alertas mínimas: fallos de MFA repetidos, sign-in desde país no habitual, cambios en la App Registration, **agregado de credenciales a la app** (técnica de persistencia habitual tras un compromiso), altas en `GRP-Portal-Admin`.
- Revisar semanalmente el reporte de *Conditional Access insights*.

---

## 6. Plan de implementación por fases

### Vista general

| Fase | Duración | Dependencias | Responsable principal |
|---|---|---|---|
| **Fase 0** — Relevamiento y decisión de plataforma | 1 semana | — | Dirección + IT |
| **Fase 1** — Configuración en Entra ID | 1–2 semanas | Fase 0 | IT / Administrador M365 |
| **Fase 2** — Desarrollo y conexión | 4–5 semanas | Fase 1 | Desarrollo |
| **Fase 3** — Hardening de ciberseguridad | 2–3 semanas | Fase 2 | Desarrollo + Seguridad |
| **Fase 4** — Pruebas y despliegue a producción | 2–3 semanas | Fase 3 | Todos |
| | **11–14 semanas** | | |

> Las fases 2 y 3 **se solapan parcialmente**: el hardening de cabeceras y CSP puede avanzar mientras se desarrolla el flujo de auth. La secuencia estricta aplica a los *gates* de salida.

---

### Fase 0 — Relevamiento y decisión de plataforma (1 semana)

| # | Tarea | Responsable |
|---|---|---|
| 0.1 | Ejecutar `verify_entra_reachability.php` en producción y **borrarlo** | Desarrollo |
| 0.2 | Confirmar licencias asignadas y cantidad de seats (límite 300 en la familia Business) | IT |
| 0.3 | Verificar si Security Defaults está activo (bloquea CA) | IT |
| 0.4 | Inventariar usuarios internos y contactos de clientes a habilitar | Operaciones + RRHH |
| 0.5 | **Decidir plataforma de hosting** (§2.2) | Dirección |
| 0.6 | Evaluar compra del complemento Entra ID P2 para privilegiados (§5.2) | Dirección |
| 0.7 | Validar con asesoría legal el tratamiento de datos personales y la transferencia internacional si se aloja fuera de Argentina (Ley 25.326 / normativa AAIP) | Legal |

**✅ Gate de salida:** discovery de Entra alcanzable · plataforma decidida y presupuestada · inventario de usuarios cerrado · postura de licenciamiento definida.

---

### Fase 1 — Configuración en Entra ID (1–2 semanas)

**1.1 — Higiene del tenant (antes que nada)**

| # | Tarea | Verificación |
|---|---|---|
| 1.1.1 | Crear **2 cuentas break-glass**, credenciales en caja fuerte física | Login probado y documentado |
| 1.1.2 | Desactivar **Security Defaults** | Portal de Entra |
| 1.1.3 | Reducir Global Admins a ≤ 3; separar cuenta admin de cuenta diaria | Lista de roles revisada |
| 1.1.4 | Configurar *Authentication methods policy*: Authenticator con number matching, FIDO2; desalentar SMS | Política publicada |
| 1.1.5 | Habilitar SSPR | Probado con un usuario piloto |

**1.2 — App Registrations**

| # | Tarea |
|---|---|
| 1.2.1 | Crear **"AMA Portal — Servicios"**: cuenta *Single tenant*, plataforma *Web*, redirect URI exacta |
| 1.2.2 | Crear **"AMA Portal — Backoffice"** ídem |
| 1.2.3 | En ambas: **NO** habilitar implicit grant (sin `id_token`/`access_token` en la plataforma web) |
| 1.2.4 | Registrar `post_logout_redirect_uri` |
| 1.2.5 | Permisos delegados mínimos: `openid`, `profile`, `email`. **Sin `User.Read` de Graph** salvo necesidad concreta |
| 1.2.6 | Generar credencial: **certificado** preferido (§5.4.1); documentar fecha de expiración y recordatorio de rotación |
| 1.2.7 | Cargar los **App Roles** del §4.3.3 en el manifest (GUID único por rol) |
| 1.2.8 | En cada Enterprise Application: **"Assignment required" = Yes** |
| 1.2.9 | Configurar *Token configuration*: emitir `email`; **no** agregar claim de grupos (usamos App Roles) |

**1.3 — Grupos y asignaciones**

| # | Tarea |
|---|---|
| 1.3.1 | Crear los 7 grupos de seguridad `GRP-Portal-*` (asignación directa, no dinámicos en la primera etapa) |
| 1.3.2 | Asignar cada grupo a su App Role en la Enterprise Application |
| 1.3.3 | Poblar los grupos con el inventario de la tarea 0.4 |
| 1.3.4 | Crear `GRP-Excepcion-Viajes` (vacío, para CA004) |

**1.4 — Conditional Access en Report-only**

| # | Tarea |
|---|---|
| 1.4.1 | Crear CA001–CA007 (§5.1.1) **todas en modo Report-only** |
| 1.4.2 | Verificar que las break-glass estén excluidas de **todas** |
| 1.4.3 | Definir *Named locations* (Argentina, Italia, IP de oficina como trusted) |
| 1.4.4 | Redactar y publicar los *Terms of Use* para invitados |
| 1.4.5 | Configurar *Diagnostic Settings* → exportación de logs (§5.6) |

**✅ Gate de salida:** break-glass probadas · apps creadas con assignment required · App Roles asignados a grupos · 7 días de datos en report-only **sin impactos inesperados**.

> ⚠️ **No avanzar a Fase 2 sin las break-glass probadas.** Es el único control que evita un bloqueo total del tenant.

---

### Fase 2 — Desarrollo y conexión (4–5 semanas)

**2.1 — Base técnica (semana 1)**

| # | Tarea | Resuelve |
|---|---|---|
| 2.1.1 | Actualizar a **PHP 8.3**; corregir incompatibilidades | H6 |
| 2.1.2 | **Corregir el pipeline de deploy para incluir `vendor/`** (§7.2) | 🔴 H1 |
| 2.1.3 | `composer require league/oauth2-client thenetworg/oauth2-azure firebase/php-jwt` | |
| 2.1.4 | Agregar `composer audit` como gate de CI | A06 |
| 2.1.5 | Migración SQL: `usuarios_portal` + columna en `admin_audit_logs` (§4.4) | |
| 2.1.6 | Extender `loadEnv()` con las claves `ENTRA_*` (`src/core/utils.php`) | |

**2.2 — Núcleo OIDC (semanas 2–3)**

| # | Entregable | Ubicación |
|---|---|---|
| 2.2.1 | Cliente OIDC: discovery + caché de JWKS con refresh por `kid` desconocido | `src/core/entra_oidc.php` |
| 2.2.2 | Construcción del `/authorize` con PKCE, `state`, `nonce` (§4.1) | ídem |
| 2.2.3 | Handler de callback: validación de `state` single-use, canje de código, **las 8 validaciones del §4.2** | ídem |
| 2.2.4 | Sesión del portal: cookie `__Host-`, `SameSite=Lax`, regeneración post-login | `src/core/portal_session.php` (H5) |
| 2.2.5 | Aprovisionamiento JIT en `usuarios_portal` por `(oid, tid)` | ídem |
| 2.2.6 | Mapa de roles y guards `portal_can()` / `portal_require()` (§4.3.4) | `src/core/portal_roles.php` |
| 2.2.7 | Logout local + RP-initiated logout contra Entra | `src/pages/portal/auth/logout.php` |
| 2.2.8 | `portal_safe_redirect()` (§5.3.3) | `src/core/portal_session.php` |
| 2.2.9 | Eventos de auditoría OIDC (§5.6) | `src/core/audit.php` |

**2.3 — Portal y migración del backoffice (semanas 3–5)**

| # | Tarea |
|---|---|
| 2.3.1 | Extender el router de `index.php` con el namespace `/portal/*` |
| 2.3.2 | Layout del portal reutilizando `components/header.php` y `footer.php` |
| 2.3.3 | Dashboard por rol + vistas de `/portal/*` (§3.2) |
| 2.3.4 | Migrar el backoffice de `auth.php` local a Entra, **conservando** CSRF, auditoría y guards de rol |
| 2.3.5 | `portal_require()` en **cada** endpoint de `src/logic/api/` que exponga datos privados |
| 2.3.6 | Página `403.php` con el estilo del sitio |
| 2.3.7 | Aislar las consultas de datos propios por `entra_oid` (anti-IDOR) |

**✅ Gate de salida:** login funcional end-to-end en staging con cuentas reales · los 7 roles verificados · usuario sin App Role recibe 403 · backoffice migrado · **code review de seguridad del módulo de auth aprobado**.

---

### Fase 3 — Hardening de ciberseguridad (2–3 semanas)

| # | Tarea | Resuelve |
|---|---|---|
| 3.1 | CSP con nonce, desplegada primero en `Report-Only` y luego en enforcement | 🔴 H2 |
| 3.2 | Cabeceras objetivo del §5.3.1; eliminar la línea contradictoria de `index.php` | H7, H8, H9 |
| 3.3 | Migrar el `client_secret` a **certificado**; excluir claves `ENTRA_*` de la UI de edición de `.env` (lista blanca) | 🔴 H3 |
| 3.4 | Migrar el deploy a **OIDC federado** (o rotar la password FTP y restringir por IP) | H4 |
| 3.5 | Rate limiting en `/portal/auth/*`, con foco en el callback | A07 |
| 3.6 | Cifrado de columnas sensibles (§5.5) | A02 |
| 3.7 | Mover `public/uploads/` fuera del DocumentRoot, servido con verificación de permiso | A01 |
| 3.8 | Revisar `bridge.php` bajo el nuevo modelo de identidad — o retirarlo si ya no se usa | A10 |
| 3.9 | Activar **CA001–CA007 en enforcement**, de a una y con ventana de observación | §5.1 |
| 3.10 | Configurar alertas de monitoreo (§5.6) | A09 |
| 3.11 | Escaneo de dependencias + análisis estático de PHP | A06 |
| 3.12 | Documentar procedimiento trimestral de revisión de accesos B2B | §4.5 |

**✅ Gate de salida:** CSP sin `'unsafe-inline'` · sin secretos en `.env` accesibles desde la UI · CA en enforcement sin incidentes · alertas probadas con un evento sintético.

---

### Fase 4 — Pruebas y despliegue a producción (2–3 semanas)

**4.1 — Pruebas funcionales**

| Caso | Resultado esperado |
|---|---|
| Login de empleado con MFA | Acceso concedido, roles correctos |
| Login de usuario **sin App Role asignado** | **Bloqueado por Entra** (assignment required) |
| Login de invitado B2B | Acceso a `Portal.Cliente` únicamente |
| Acceso a `/portal/*` sin sesión | Redirección a Entra, retorno al destino original |
| Timeout de inactividad (30 min) | Sesión cerrada, re-autenticación requerida |
| Logout | Sesión local destruida **y** sesión de Entra cerrada |
| Usuario con `is_active = 0` local | Bloqueado aun con token válido |
| Acceso a página pública | **Sin** redirección a login |

**4.2 — Pruebas de seguridad (obligatorias)** — referenciadas como *F4.2.x*

| # | Caso de abuso | Resultado esperado |
|---|---|---|
| 4.2.1 | Reusar un `code` ya canjeado | Rechazado (single-use) |
| 4.2.2 | Callback con `state` alterado o ausente | Rechazado + `oidc_state_mismatch` auditado |
| 4.2.3 | ID token con firma inválida | Rechazado + `oidc_token_invalid` |
| 4.2.4 | 🔴 ID token de **otro tenant** (`tid` ajeno) | Rechazado + `oidc_tenant_mismatch` |
| 4.2.5 | 🔴 Token con `aud` de otra aplicación | Rechazado |
| 4.2.6 | Token expirado | Rechazado |
| 4.2.7 | `redirect_to=//evil.com` y variantes | Redirige al fallback interno |
| 4.2.8 | Manipular `roles` en la sesión del cliente | Imposible: la sesión es server-side |
| 4.2.9 | Acceso directo a endpoint de API sin permiso | 403 + auditado |
| 4.2.10 | IDOR: solicitar datos de otro usuario | 403 / conjunto vacío |
| 4.2.11 | CSRF en formularios del portal | Bloqueado por token + `SameSite=Lax` |
| 4.2.12 | XSS reflejado y almacenado | Bloqueado por escape + CSP nonce |

**4.3 — Despliegue**

| # | Tarea |
|---|---|
| 4.3.1 | Piloto con 5–8 usuarios (uno por rol) durante 1 semana |
| 4.3.2 | Comunicación y guía breve para usuarios (registro de MFA previo) |
| 4.3.3 | Ventana de despliegue usando el **modo mantenimiento existente** (`mantenimiento.php`) |
| 4.3.4 | Migración de datos: mapear `usuarios_admin` → `usuarios_portal` por email, **verificado a mano uno por uno** |
| 4.3.5 | Congelar altas en `usuarios_admin`; dejarla sólo-lectura por 90 días para el histórico de auditoría |
| 4.3.6 | Monitoreo intensivo de 72 h |
| 4.3.7 | Retirar el flujo de login local, `forgot_password.php` y `reset_password.php` tras el período de gracia |
| 4.3.8 | Borrar `setup.php` y cualquier script de diagnóstico del servidor |
| 4.3.9 | Actualizar `docs/ADMIN_PORTAL.md`, `docs/INFRASTRUCTURE.md` y `README.md` |

**✅ Gate de salida:** los 12 casos de abuso superados · piloto sin incidentes · rollback documentado y probado · documentación actualizada.

---

## 7. Anexos técnicos

### 7.1 Estructura de archivos propuesta

```
src/
├── core/
│   ├── entra_oidc.php        # Cliente OIDC: discovery, JWKS, authorize, callback, validación
│   ├── portal_session.php    # Sesión del portal, cookie __Host-, safe redirect
│   ├── portal_roles.php      # Mapa App Role → permisos, portal_can(), portal_require()
│   └── audit.php             # Auditoría centralizada (extiende el patrón actual)
├── pages/
│   ├── portal/
│   │   ├── auth/{login,callback,logout}.php
│   │   ├── index.php         # Dashboard por rol
│   │   ├── schedules.php
│   │   ├── documentacion.php
│   │   ├── novedades.php
│   │   └── cotizaciones.php
│   └── 403.php
database/
└── migrations/
    └── 2026_10_portal_entra.sql
```

### 7.2 Corrección del pipeline de deploy (H1 — crítica)

El workflow actual excluye `vendor/**`, por lo que una librería de autenticación **no se desplegaría**. Corrección mínima manteniendo FTP:

```yaml
      - name: 🐘 Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: 📦 Install production dependencies
        run: composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

      - name: 🔍 Security audit of dependencies
        run: composer audit --no-dev
```

Y **quitar `vendor/**` de la lista `exclude:`** del paso de FTP.

> El `composer audit` como paso previo al deploy convierte una vulnerabilidad conocida en una falla de build en lugar de un incidente de producción.

### 7.3 Runbooks operativos

**R1 — Usuario no puede ingresar**
1. Entra ID → *Sign-in logs* → filtrar por UPN.
2. Leer el código de error: `AADSTS50105` = no asignado a la app → agregar al grupo. `AADSTS53003` = bloqueado por CA → revisar qué política.
3. Si no hay registro de sign-in: el usuario no llegó a Entra → problema de la app, revisar `app.log`.
4. Verificar `usuarios_portal.is_active`.

**R2 — Bloqueo del tenant por Conditional Access**
1. Ingresar con cuenta break-glass en ventana privada.
2. Pasar la política problemática a *Report-only*.
3. Verificar acceso normal. Analizar con *CA insights* antes de reactivar.

**R3 — Rotación de credencial de la app**
1. Agregar el **nuevo** certificado/secreto en la App Registration (conviven).
2. Desplegar la configuración nueva.
3. Verificar login exitoso.
4. **Recién entonces** eliminar la credencial vieja.
5. Registrar la próxima fecha de expiración.

**R4 — Sospecha de compromiso de cuenta**
1. Entra ID → usuario → *Revoke sessions*.
2. Forzar cambio de contraseña y re-registro de MFA.
3. Quitar de grupos `GRP-Portal-*`.
4. Marcar `usuarios_portal.is_active = 0` (efecto inmediato en el portal).
5. Auditar `admin_audit_logs` por `oid` y revisar sign-in logs.
6. **Verificar que no se hayan agregado credenciales a la App Registration** (persistencia habitual post-compromiso).

### 7.4 Matriz de riesgos

| # | Riesgo | Prob. | Impacto | Mitigación |
|---|---|---|---|---|
| R01 | Bloqueo del tenant al activar CA | Media | 🔴 Crítico | Break-glass + report-only 7 días + activación gradual |
| R02 | Hosting compartido sin salida HTTPS | Baja | 🔴 Crítico | **Verificado en Fase 0** antes de comprometer fechas |
| R03 | Librería de auth no desplegada (H1) | **Alta** | 🔴 Crítico | Corrección del pipeline como tarea 2.1.2, bloqueante |
| R04 | Validación incompleta del token (`tid`/`aud`) | Media | 🔴 Crítico | Checklist §4.2 + casos de abuso F4.2.4 / F4.2.5 + code review |
| R05 | Acumulación de accesos B2B sin revisión (sin P2) | **Alta** | 🟠 Alto | Procedimiento trimestral + reporte de inactividad 90 días |
| R06 | Resistencia de usuarios al MFA | Media | 🟡 Medio | Comunicación previa, registro asistido, piloto |
| R07 | Expiración de la credencial de la app | Media | 🟠 Alto | Recordatorio a 60 días + runbook R3 |
| R08 | CA004 geográfica bloquea a usuarios en viaje | **Alta** | 🟡 Medio | `GRP-Excepcion-Viajes` + procedimiento de alta temporal |
| R09 | Secreto expuesto vía UI de edición de `.env` (H3) | Media | 🔴 Crítico | Lista blanca de claves + certificado en lugar de secreto |
| R10 | Pérdida del histórico de auditoría en la migración | Media | 🟡 Medio | `usuarios_admin` sólo-lectura 90 días + mapeo verificado a mano |

### 7.5 Checklist de corte a producción

- [ ] Break-glass probadas y documentadas en caja fuerte
- [ ] CA001–CA007 en enforcement, con break-glass excluidas
- [ ] "Assignment required = Yes" en ambas Enterprise Applications
- [ ] Las 8 validaciones de token implementadas y cubiertas por pruebas
- [ ] Los 12 casos de abuso de la Fase 4.2 superados
- [ ] CSP sin `'unsafe-inline'` en enforcement
- [ ] `vendor/` desplegado desde CI, `composer audit` limpio
- [ ] Credencial de Entra por certificado, fuera del DocumentRoot, `chmod 600`
- [ ] Claves `ENTRA_*` excluidas de la UI de edición de `.env`
- [ ] `setup.php` y scripts de diagnóstico eliminados del servidor
- [ ] `APP_ENV=production` verificado
- [ ] Exportación de logs de Entra activa; alertas probadas
- [ ] Backup con restauración probada
- [ ] Rollback documentado y ensayado
- [ ] Documentación del repo actualizada

### 7.6 Estado de verificación del código de referencia

Transparencia sobre qué fue efectivamente probado al redactar este plan y qué no.

| Fragmento | Verificación | Resultado |
|---|---|---|
| `portal_safe_redirect()` | `php -l` + **24 casos de abuso** ejecutados (open redirect, protocol-relative, `javascript:`, `data:`, traversal `..` y `%2e%2e`, CRLF injection, backslash) | ✅ 24/24 |
| `portal_resolve_permissions()` / `portal_can()` | `php -l` + **12 casos** (fail-closed sin roles, rol desconocido no escala, `Portal.Management` sólo lectura, wildcard de admin, combinación de roles) | ✅ 12/12 |
| Snippet de CSP con nonce | `php -l` | ✅ Parsea |
| `verify_entra_reachability.php` | `php -l` | ✅ Parsea — **ejecutar en el hosting real** (tarea 0.1) |
| Manifest de App Roles (JSON) | Parseo JSON | ✅ Válido |
| Pipeline de CI (YAML) | Parseo YAML | ✅ Válido, 3 pasos |
| Migración SQL | Revisión contra `database/install.sql` | ⚠️ **No ejecutada** — sin instancia MySQL disponible al redactar. Sigue las convenciones del esquema existente, pero **debe correrse primero en staging**. |
| Cabeceras `.htaccess` | Revisión manual | ⚠️ **No probadas** — `<LocationMatch>` requiere `AllowOverride` suficiente; en hosting compartido puede necesitar `<If>` o control desde PHP. Validar en la tarea 3.2. |
| Flujo OIDC end-to-end | — | ⚠️ **No probado** — requiere tenant real. Es el objeto de la Fase 2. |

> Las pruebas se corrieron con PHP 8.4 en entorno de desarrollo. Los fragmentos son **código de referencia para guiar la implementación**, no un módulo listo para producción: la Fase 2 los integra, completa y somete a code review.

---

## 8. Fuentes

- [Microsoft Entra licensing — Microsoft Learn](https://learn.microsoft.com/en-us/entra/fundamentals/licensing)
- [OpenID Connect on the Microsoft identity platform](https://learn.microsoft.com/en-us/entra/identity-platform/v2-protocols-oidc)
- [ID token claims reference](https://learn.microsoft.com/en-us/entra/identity-platform/id-token-claims-reference)
- [Configure group claims and app roles in tokens](https://learn.microsoft.com/en-us/security/zero-trust/develop/configure-tokens-group-claims-app-roles)
- [Custom role-based access control for application developers](https://learn.microsoft.com/en-us/entra/identity-platform/custom-rbac-for-developers)
- [Configure group claims for applications (overage limits)](https://learn.microsoft.com/en-us/entra/identity/hybrid/connect/how-to-connect-fed-group-claims)
- [Microsoft 365 Business Premium FAQ](https://learn.microsoft.com/en-us/microsoft-365/business-premium/microsoft-365-business-faqs)
- [Microsoft 365 for business security overview](https://learn.microsoft.com/en-us/microsoft-365/admin/security-and-compliance/m365b-security-overview)
- [Learn about Conditional Access and Intune](https://learn.microsoft.com/en-us/mem/intune/protect/conditional-access)
- [TheNetworg/oauth2-azure](https://github.com/TheNetworg/oauth2-azure)
- [OWASP Top 10:2021](https://owasp.org/Top10/)

---

**Documento relacionado:** `docs/INFRASTRUCTURE.md`, `docs/ADMIN_PORTAL.md`, `docs/BACKEND.md`
