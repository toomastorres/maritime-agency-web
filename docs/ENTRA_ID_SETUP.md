# Microsoft Entra ID — Configuración y gestión de accesos

**Versión:** 1.0 (Septiembre 2026)
**Alcance:** Portal interno `/gestion-interna-agencia/`. El sitio público no cambia.
**Dirigido a:** Administrador de Microsoft 365 de Atlantic Maritime Agency.

---

## 0. Cómo funciona, en dos frases

Los empleados ingresan al portal interno con su cuenta de Microsoft 365. **Quién puede
entrar y con qué rol se define enteramente en Entra ID**: el portal lee el rol del token que
Microsoft emite y no toma decisiones propias de permisos.

Consecuencia práctica: para dar, cambiar o quitar un acceso **no se toca el portal ni la base
de datos**. Todo se hace desde el portal de Entra ID, y el cambio impacta en el siguiente login.

---

## 1. Puesta en marcha (una sola vez)

### Paso 1 — Registrar la aplicación

1. Entrar a <https://entra.microsoft.com> con una cuenta con rol **Application Administrator**
   o **Global Administrator**.
2. **Identity → Applications → App registrations → New registration**.
3. Completar:

   | Campo | Valor |
   |---|---|
   | **Name** | `AMA Portal — Backoffice` |
   | **Supported account types** | **Accounts in this organizational directory only (Single tenant)** |
   | **Redirect URI** | Plataforma **Web** → `https://atlantic-agency.example.com/gestion-interna-agencia/auth/callback.php` |

4. **Register**.

> ⚠️ La Redirect URI debe coincidir **carácter por carácter**, incluido el `.php` final.
> Es el error de configuración más habitual y produce `AADSTS50011`.

### Paso 2 — Anotar los identificadores

En la pestaña **Overview** de la aplicación recién creada, copiar:

- **Application (client) ID**
- **Directory (tenant) ID**

### Paso 3 — Crear el secreto

1. **Certificates & secrets → Client secrets → New client secret**.
2. Descripción: `Portal backoffice`. Vencimiento: **24 meses** (el máximo).
3. Copiar el valor de la columna **Value** (no el Secret ID).

> 🔴 El secreto se muestra **una sola vez**. Si se pierde hay que generar otro.
> **Anotar la fecha de vencimiento en el calendario con un recordatorio a 60 días**:
> cuando expira, nadie puede entrar al portal hasta reemplazarlo.

### Paso 4 — Configurar el cierre de sesión

Este paso le dice a Microsoft a dónde mandar al empleado cuando cierra sesión, y apaga un
modo de autenticación viejo que el portal no usa. Son dos campos en la misma pantalla.

**Cómo llegar:** en el App Registration creado en el Paso 1, menú izquierdo → **Manage** →
**Authentication**.

> **Si ves "Authentication (Preview)"** — la experiencia nueva del portal — la pantalla está
> partida en tres pestañas: *Configuración de URI de redirección*, *Supported accounts* y
> *Configuración*. Los dos campos de este paso están en la tercera, **Configuración**, no en
> la que se abre por defecto. El propio portal lo avisa en un recuadro azul.
> El resto de las instrucciones es igual.

**4.a — Front-channel logout URL**

Es un campo de texto suelto, más abajo de la lista de Redirect URIs (no es un botón de
"Add URI", es una caja aparte con ese título). Pegar exactamente:

```
https://atlantic-agency.example.com/gestion-interna-agencia/index.php
```

Sirve para que, al hacer "Cerrar sesión" en el portal, Microsoft también cierre la sesión
de su lado y devuelva a la persona a la pantalla de login. Sin esto, cerrar sesión en el
portal deja la sesión de Microsoft viva y el siguiente login entra solo, sin pedir nada.

**4.b — Apagar implicit grant**

En la misma pantalla, bajar hasta la sección **Implicit grant and hybrid flows**. Hay dos
casillas: `Access tokens` e `ID tokens`. **Las dos tienen que quedar destildadas.**

Si están tildadas, destildarlas. Si ya están destildadas, no tocar nada: así es como debe
quedar. El portal usa Authorization Code + PKCE, que es el flujo moderno; implicit es el
esquema viejo, que expone el token en la URL y no hace falta acá.

**Guardar** con el botón **Save** de arriba.

> Si el campo **Front-channel logout URL** no aparece, es porque estás en la pantalla del
> Enterprise Application en vez de la del App Registration. Volvé a
> **Identity → Applications → App registrations → (tu app) → Authentication**.

### Paso 5 — Crear los roles del portal

**App registrations → (la app) → App roles → Create app role.** Crear los cinco, uno por uno.
El campo **Value** debe escribirse **exactamente** así, respetando mayúsculas:

| Display name | Value | Description | Allowed member types |
|---|---|---|---|
| Administrador Global | `Portal.AdminGlobal` | Acceso total al portal y gestión de usuarios | Users/Groups |
| Recursos Humanos | `Portal.RRHH` | Empleados, vacantes y postulantes | Users/Groups |
| Customer Service | `Portal.CS` | Schedules y novedades | Users/Groups |
| Caja | `Portal.Caja` | Tipo de cambio y datos bancarios | Users/Groups |
| Documentación | `Portal.Documentacion` | Documentación oficial | Users/Groups |

En todos: **Do you want to enable this app role?** → tildado.

> Si un Value se escribe mal, la persona se autentica pero el portal le dice que no tiene rol
> asignado. El evento queda registrado como `oidc_sin_rol` con el nombre del rol no reconocido,
> así que el registro de actividad indica exactamente qué corregir.

### Paso 6 — Exigir asignación explícita

**Identity → Applications → Enterprise applications → `AMA Portal — Backoffice` →
Properties**:

- **Assignment required?** → **Yes**
- **Visible to users?** → **No**

**Guardar.**

> Este es el interruptor más importante de toda la configuración. Con **Yes**, un empleado de
> AMA que no esté asignado a la aplicación **no obtiene token**: Microsoft lo rechaza
> antes de que llegue al portal. Con **No**, cualquier persona del tenant alcanzaría el portal.

### Paso 7 — Cargar la configuración en el servidor

Editar `/public_html/.env` por FTP y agregar:

```env
ENTRA_TENANT_ID=<Directory (tenant) ID del paso 2>
ENTRA_CLIENT_ID=<Application (client) ID del paso 2>
ENTRA_CLIENT_SECRET=<el Value del paso 3>
ENTRA_REDIRECT_URI=https://atlantic-agency.example.com/gestion-interna-agencia/auth/callback.php
ENTRA_POST_LOGOUT_URI=https://atlantic-agency.example.com/gestion-interna-agencia/index.php
```

Recordar las reglas del `.env` de este hosting: **sin comillas**, fin de línea **LF**, FTP en
modo **binario**, permisos **644**.

> Mientras estas variables estén vacías, el portal sigue funcionando exactamente como antes,
> con usuario y contraseña. Se puede cargar la configuración cuando se quiera: el botón de
> Microsoft aparece solo.

### Paso 8 — Ejecutar la migración de base de datos

En cPanel → phpMyAdmin → base `ama_web` → pestaña **SQL**, pegar y ejecutar el contenido de
`database/migrations/2026_09_entra_id.sql`.

Agrega las columnas de identidad federada y marca las cuentas existentes como locales. Es
idempotente: se puede correr dos veces sin problema.

### Paso 9 — Designar la cuenta de emergencia (break-glass)

Elegir **una** cuenta `admin_global` que conserve el ingreso por contraseña, para poder entrar
si Entra falla, el secreto vence o hay un incidente en Microsoft.

```sql
UPDATE usuarios_admin
   SET is_break_glass = 1,
       auth_provider  = 'local'
 WHERE username = 'REEMPLAZAR_POR_EL_USERNAME'
   AND rol_portal = 'admin_global';
```

Verificar que devuelva **exactamente una fila**:

```sql
SELECT id, username, rol_portal, is_break_glass
  FROM usuarios_admin
 WHERE is_break_glass = 1;
```

> Guardar la contraseña de esa cuenta en un lugar físico seguro. Es la única que puede entrar
> sin Microsoft, y cada ingreso suyo queda auditado como `CUENTA BREAK-GLASS (login local)`.

---

## 2. Otorgar roles — la tarea de todos los días

**Esto es lo que hay que hacer cada vez que alguien necesita acceso.**

### Opción recomendada: por grupos

Conviene crear un grupo de seguridad por rol y asignar el **grupo** al rol de la aplicación.
Después, dar o quitar acceso es simplemente agregar o sacar a la persona del grupo.

**Una sola vez, crear los grupos** en **Identity → Groups → All groups → New group**
(tipo *Security*, membresía *Assigned*):

`GRP-Portal-AdminGlobal`, `GRP-Portal-RRHH`, `GRP-Portal-CS`, `GRP-Portal-Caja`,
`GRP-Portal-Documentacion`.

**Una sola vez, vincular cada grupo a su rol:**

1. **Identity → Applications → Enterprise applications → `AMA Portal — Backoffice`**
2. **Users and groups → Add user/group**
3. En **Users and groups** elegir el grupo; en **Select a role** elegir el App Role correspondiente.
4. **Assign.** Repetir para los cinco.

**A partir de ahí, dar acceso a una persona es:**

> **Identity → Groups →** el grupo del área **→ Members → Add members →** elegir a la persona.

Y quitarlo es sacarla del grupo. Nada más.

### Opción directa: por persona

Si se prefiere no usar grupos:

1. **Enterprise applications → `AMA Portal — Backoffice` → Users and groups**
2. **Add user/group** → elegir la persona → elegir el rol → **Assign**

Para cambiar el rol de alguien: en esa misma lista, quitar la asignación existente y crear una
nueva con el rol nuevo.

### Cuándo impacta el cambio

| Acción en Entra | Cuándo se ve en el portal |
|---|---|
| Asignar un rol a alguien nuevo | En su próximo login |
| Cambiar el rol de una persona | En su próximo login (tiene que cerrar sesión y volver a entrar) |
| Quitar la asignación | En su próximo login. Para cortar **ya**, ver §4 |

> El portal no consulta Entra en cada click: confía en el rol del token hasta que la sesión
> caduca (30 minutos de inactividad). Para un corte inmediato, usar el procedimiento de §4.

---

## 2.bis — El portal ya no emite credenciales

Con Entra ID configurado, **el panel no puede crear usuarios ni asignar contraseñas**.
No es sólo que la interfaz lo oculte: el servidor rechaza el intento.

| Antes | Ahora |
|---|---|
| **Usuarios Admin → Invitar Usuario** creaba una cuenta y mandaba un mail para que la persona eligiera contraseña | El botón desaparece. La cuenta se crea sola en el primer ingreso con Microsoft |
| El admin podía escribir una contraseña manual y dictarla | El campo no existe, salvo para la cuenta break-glass |
| El rol se elegía en un desplegable del panel | El desplegable queda bloqueado: el rol lo define el App Role de Entra |
| **Mi Perfil → Cambiar Contraseña** | Para cuentas de Microsoft muestra un enlace a `myaccount.microsoft.com`. Sólo la break-glass conserva el formulario |
| La pantalla de login pedía usuario y contraseña | Sólo el botón de Microsoft. La break-glass entra por `?emergencia=1` |

En **Usuarios Admin** cada cuenta muestra ahora cómo ingresa:

| Etiqueta | Significa |
|---|---|
| **Microsoft** | Entra con su cuenta de Microsoft 365. No tiene contraseña |
| **Emergencia** | La cuenta break-glass. Es la única con contraseña local |
| **Contraseña** | Cuenta preexistente que todavía no ingresó con Microsoft. Se convierte sola en su próximo ingreso |

> Si ves cuentas en **Contraseña** que ya no se usan, bloquealas con el botón 🚫.
> Mientras tengan contraseña y no sean break-glass, igual **no pueden entrar**:
> el login local las rechaza y lo registra como `login_local_rechazado`.

---

## 3. Primer ingreso de una persona

1. Entrar a `https://atlantic-agency.example.com/gestion-interna-agencia/`
2. Botón **Ingresar con Microsoft**
3. Autenticarse con la cuenta corporativa (y MFA si está activo)
4. El portal crea la cuenta local automáticamente con el rol que indica Entra

No hay que crear nada a mano en el portal. Si la persona ya tenía usuario, el sistema **vincula
la cuenta existente por email** y conserva todo su historial de auditoría.

---

## 4. Situaciones frecuentes

### Cortar el acceso de alguien de inmediato

1. **Entra ID → Users →** la persona **→ Revoke sessions**
2. Quitarla del grupo (o de *Users and groups* de la aplicación)
3. Para cortar la sesión abierta en el portal, en phpMyAdmin:

```sql
UPDATE usuarios_admin SET is_active = 0 WHERE email = 'persona@atlantic-agency.example.com';
```

El bloqueo local (`is_active = 0`) corta el acceso **aunque Microsoft la autentique**.

### "Mi cuenta de Microsoft no tiene acceso asignado a este portal"

La persona no está asignada a la aplicación. Agregarla al grupo correspondiente (§2).
En el registro de actividad figura como `oidc_sin_asignacion`.

### "Te autenticaste pero no tenés un rol asignado"

Está asignada a la aplicación pero sin App Role, o con un rol cuyo *Value* está mal escrito.
Revisar el paso 5 y el evento `oidc_sin_rol` en **Registro de Actividad**, que indica qué rol
llegó sin reconocerse.

### "Servicio temporalmente no disponible" después de editar el .env

Esa pantalla **no es de Entra**: la emite `src/core/database.php` cuando el portal no puede
conectarse a MySQL. Si aparece justo después del Paso 7, el `.env` quedó dañado en la edición.

Causas, de más a menos frecuente:

1. **El archivo se reemplazó en vez de ampliarse.** El editor del FTP o del File Manager abrió
   el `.env`, se pegaron las cinco líneas `ENTRA_*` y se guardó perdiendo el resto. Sin
   `DB_USER` y `DB_PASS` no hay conexión posible.
2. **Se subió a la carpeta equivocada.** El `.env` va en la raíz de `public_html/`, al mismo
   nivel que `index.php`, no dentro de `gestion-interna-agencia/`.
3. **Permisos.** Debe quedar en **644**. Con 600 y otro dueño, PHP no lo lee y todas las
   variables salen vacías.
4. **Se subió en modo ASCII con un editor de Windows** y quedó un BOM al principio. El BOM se
   pega a la primera clave, que deja de reconocerse.

**Diagnóstico exacto:** abrir por cPanel → File Manager el archivo
`public_html/src/data/logs/app.log` y mirar la última línea. Dice el error real de MySQL y,
si faltan variables, las nombra:

```
[2026-09-15 14:22:01] DB Connection Error: SQLSTATE[HY000] [1045] Access denied ... | Missing env: DB_USER,DB_NAME
```

| Lo que dice el log | Qué significa |
|---|---|
| `Missing env: ...` | El `.env` se perdió o no se está leyendo. Restaurarlo completo. |
| `[1045] Access denied` | Usuario o contraseña de MySQL incorrectos. |
| `[1049] Unknown database` | `DB_NAME` mal escrito. |
| `[2002] Connection refused` | `DB_HOST` mal escrito (debe ser `localhost`). |

**Solución:** restaurar el `.env` completo tomando `.env.example` como plantilla, con los
valores reales de base de datos **más** las cinco líneas `ENTRA_*`. El bloque de base de datos
debe quedar así:

```env
DB_HOST=localhost
DB_USER=ama_web
DB_PASS=<la contraseña real de MySQL>
DB_NAME=ama_web
```

Los valores exactos están en cPanel → **MySQL® Databases**. Si se perdió la contraseña, desde
esa misma pantalla se le puede asignar una nueva al usuario y reflejarla en el `.env`.

> El portal vuelve solo apenas el `.env` queda bien: no hay que reiniciar nada.

### Perdí el valor del secreto (no lo copié)

**No se puede recuperar.** Microsoft guarda sólo un hash, nunca el valor. Hay que generar otro,
lo que toma un minuto y no afecta nada de lo ya configurado:

1. **Certificates & secrets → New client secret** (igual que el paso 3).
2. Copiar la columna **Value** y pegarla directo en `ENTRA_CLIENT_SECRET` del `.env`.
3. **Borrar el secreto viejo** con el ícono de basura.

El paso 3 no es opcional: un secreto que nadie tiene pero sigue activo es una credencial
huérfana. Si algún día aparece en un log o un portapapeles, sigue siendo válida.

El App Registration, el tenant ID, el client ID, la Redirect URI, los App Roles y el
*Assignment required* **no se tocan**: sólo se reemplaza el secreto.

> ⚠️ En esa pantalla hay dos columnas: **Secret ID** y **Value**. El *Secret ID* siempre está
> visible y **no sirve** para autenticar. El que va al `.env` es el **Value**, que se muestra
> una sola vez.
>
> Si esto pasa cuando el portal ya está funcionando, entrá con la cuenta **break-glass** para
> no quedarte afuera mientras lo reemplazás.

### El secreto venció

Síntoma: nadie puede entrar con Microsoft; el log muestra `oidc_token_error`.

1. Entrar con la cuenta **break-glass** (§1 paso 9)
2. Generar un secreto nuevo (§1 paso 3)
3. Actualizar `ENTRA_CLIENT_SECRET` en el `.env` por FTP
4. Probar el login con Microsoft y recién entonces borrar el secreto viejo en Entra

### Volver atrás

Vaciar `ENTRA_CLIENT_SECRET` en el `.env`. El portal vuelve al login por usuario y contraseña
como antes. Las cuentas que hayan quedado sin contraseña (creadas automáticamente) necesitarán
que un `admin_global` les fije una desde **Usuarios Admin**.

---

## 5. Seguridad recomendada (Conditional Access)

Microsoft 365 Business Premium incluye **Entra ID P1**, que habilita Acceso Condicional. No es
obligatorio para que el portal funcione, pero es donde se obtiene el mayor beneficio de haber
federado la identidad.

> **Antes de crear cualquier política:** crear **dos cuentas break-glass en Entra**
> (distintas de la del portal): sólo nube, dominio `.onmicrosoft.com`, Global Administrator,
> contraseñas largas guardadas en caja fuerte, y **excluidas de todas las políticas**.
> Quedarse afuera del propio tenant es el incidente más común al activar Acceso Condicional.
> Además, **empezar siempre en modo *Report-only*** durante una semana.

| Política | Qué hace |
|---|---|
| Bloquear autenticación legacy | Cierra los protocolos viejos que no soportan MFA |
| **MFA obligatorio para el portal** | Aplicada a la aplicación `AMA Portal — Backoffice` |
| Dispositivo compatible para `Portal.AdminGlobal` | Requiere equipo administrado por Intune (incluido en la licencia) |
| Frecuencia de sesión 8 h para administradores | Limita la ventana de una sesión robada |

Conviene también exportar los **Sign-in logs** a almacenamiento externo: la retención nativa de
P1 es de 30 días.

---

## 6. Auditoría

Todo queda registrado en **Portal → Registro de Actividad** (y en la tabla `admin_audit_logs`).

| Evento | Significado |
|---|---|
| `login_exitoso` | Ingreso correcto. El detalle indica si fue por Microsoft o local |
| `oidc_usuario_creado` | Primer ingreso de una persona: se creó su cuenta |
| `oidc_cuenta_vinculada` | Una cuenta preexistente se asoció a su identidad de Microsoft |
| `oidc_rol_actualizado` | Cambió el rol de alguien respecto del login anterior |
| `oidc_sin_rol` | Se autenticó pero no tiene App Role válido |
| `oidc_sin_asignacion` | No está asignado a la aplicación |
| `oidc_cuenta_bloqueada` | Bloqueada localmente (`is_active = 0`) |
| `login_local_rechazado` | Intentó entrar con contraseña una cuenta que debe usar Microsoft |
| **`oidc_tenant_mismatch`** | 🔴 Token de **otra organización**. Investigar |
| **`oidc_firma_invalida`** | 🔴 Token con firma inválida. Investigar |
| `oidc_state_mismatch` | Posible intento de CSRF sobre el login |

Consulta rápida de los últimos ingresos:

```sql
SELECT a.creado_en, u.username, a.accion, a.detalles, a.ip_address
  FROM admin_audit_logs a
  LEFT JOIN usuarios_admin u ON u.id = a.user_id
 WHERE a.accion LIKE 'login%' OR a.accion LIKE 'oidc%'
 ORDER BY a.id DESC
 LIMIT 50;
```

---

## 7. Detalle técnico

- **Protocolo:** OpenID Connect, Authorization Code Flow + PKCE (S256), cliente confidencial.
- **Implementación:** nativa con `curl` + `openssl`, sin dependencias de Composer
  (`src/core/entra_oidc.php`). El deploy FTP excluye `vendor/`, por lo que no se depende de él.
- **Los tokens nunca llegan al navegador.** Se validan en el servidor y se descartan; en la
  sesión quedan sólo identidad y rol.
- **Sin `offline_access`:** no se piden ni almacenan refresh tokens, así que no hay credencial
  de larga vida que robar.
- **Validaciones del token, todas obligatorias:** firma RS256 contra el JWKS del tenant
  (clave elegida por `kid`), `iss`, `aud`, `exp`/`nbf` con 60 s de tolerancia, `nonce`, y
  `tid` contra el tenant propio.
- **La identidad es `oid` + `tid`**, nunca el email: `email` y `upn` son mutables y no
  verificados, y autorizar por ellos es la vulnerabilidad conocida como *nOAuth*.
- **Cookie de sesión `SameSite=Lax`** (antes `Strict`): el callback de OIDC es una navegación
  cross-site y una cookie `Strict` no se enviaría. Se compensa con el token CSRF existente.
- **`state`, `nonce` y `code_verifier`** se guardan del lado del servidor, son de un solo uso y
  vencen a los 10 minutos.

### Archivos

| Archivo | Rol |
|---|---|
| `src/core/entra_oidc.php` | Cliente OIDC: authorize, canje de código, validación del token |
| `src/core/entra_roles.php` | Mapa App Role → rol interno, y validación de redirecciones |
| `private/gestion-interna-agencia/auth/login.php` | Inicia el flujo |
| `private/gestion-interna-agencia/auth/callback.php` | Procesa el retorno y abre la sesión |
| `database/migrations/2026_09_entra_id.sql` | Migración de base de datos |

---

## 8. Checklist de puesta en marcha

- [ ] App registration creada, single tenant, Redirect URI exacta con `.php`
- [ ] Los cinco App Roles creados con el *Value* exacto
- [ ] **Assignment required = Yes** en la Enterprise Application
- [ ] Front-channel logout URL configurada
- [ ] Implicit grant **desactivado**
- [ ] Variables `ENTRA_*` cargadas en `/public_html/.env` (644, LF, sin comillas)
- [ ] Migración SQL ejecutada
- [ ] Cuenta break-glass designada y contraseña guardada en lugar seguro
- [ ] Grupos creados y vinculados a los App Roles
- [ ] Vencimiento del secreto agendado con recordatorio a 60 días
- [ ] Probado: ingreso con Microsoft de un usuario de cada rol
- [ ] Probado: un usuario **sin** asignación recibe el mensaje correcto
- [ ] Probado: la cuenta break-glass entra con contraseña
- [ ] Probado: cerrar sesión cierra también la sesión de Microsoft

---

**Documentos relacionados:** `docs/PORTAL_ENTRA_ID.md` (plan y fundamentos),
`docs/ADMIN_PORTAL.md` (portal interno), `docs/INFRASTRUCTURE.md` (hosting y `.env`).
