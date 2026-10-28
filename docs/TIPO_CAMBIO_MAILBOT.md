# Bot del buzón de tipo de cambio

**Versión:** 1.0 (Septiembre 2026)

Responde solo las consultas que llegan a **tc@atlantic-agency.example.com** con la
cotización del día, con el aviso de que todavía no se cargó, o con el aviso de
que están fuera del horario de atención. Es el mismo criterio que aplica la web:
la regla vive en un único lugar (`src/core/tipo_cambio.php`) y la consumen los
dos canales.

---

## 0. Cómo funciona, en dos frases

Cada pocos minutos, una tarea programada abre la bandeja de tc@ vía Microsoft
Graph, mira los mensajes **sin leer**, consulta la base `exchange_rates` y
responde en el mismo hilo. Lo que contesta queda marcado como leído; **todo lo
que omite queda sin leer** para que lo atienda una persona.

### Qué contesta

| Situación | Respuesta |
|-----------|-----------|
| Lun-Vie 09:00-17:00 y Caja ya cargó el dólar de hoy | Cotización USD (y EUR si está cargado) + datos bancarios + aclaración de vigencia |
| Lun-Vie 09:00-17:00 y todavía no se cargó | "Aún no fue cargada, se publica entre las 09:00 y las 10:00" + datos bancarios |
| Fuera de horario o fin de semana | "El servicio opera L-V de 09:00 a 17:00" + datos bancarios |
| La base de datos no responde | **Nada.** El mensaje queda sin leer. Un silencio se recupera; una cotización inventada, no |

Los datos bancarios van en los tres casos: no vencen a las 17:00 y son justo lo
que necesita quien escribe de noche para preparar una transferencia.

### Qué NO contesta nunca

- Respuestas automáticas ajenas (`Auto-Submitted`, "Out of office", "Respuesta
  automática"), rebotes (`Return-Path: <>`, mailer-daemon) y listas de
  distribución (`List-Id`, `Precedence: bulk`). Es lo que evita el bucle infinito
  de dos robots contestándose.
- Remitentes tipo `no-reply@`, `postmaster@`, `bounce@`.
- Sus propias respuestas (las marca con el encabezado `x-ama-autoreply`).
- Un hilo que ya contestó. Si el cliente responde de vuelta, ese mensaje queda
  sin leer y lo atiende una persona.
- Más de 3 consultas del mismo remitente por día (configurable).
- Mensajes de más de 12 horas de antigüedad (configurable). Esto es lo que evita
  que al encender el bot conteste de golpe todo el backlog sin leer.

---

## 1. Puesta en marcha

### Antes de empezar

**Quién puede hacer esto.** Hacen falta dos permisos que no se piden solos:

- Para registrar la aplicación: **Administrador de aplicaciones en la nube** o
  **Administrador global** en Entra.
- Para otorgarle acceso al buzón: **Administrador de Exchange** y ser miembro
  del grupo de roles **Organization Management** en Exchange Online.

Si no tenés esos roles, los pasos 1 a 4 los tiene que hacer quien administre el
Microsoft 365 de la empresa. Los pasos 5 a 8 son del lado del hosting.

**Cómo se otorgan los permisos, en una frase.** No se hace desde "API
permissions" de Entra, que es donde uno esperaría. Los permisos de correo se
otorgan desde **Exchange Online por PowerShell**, porque es el único lugar donde
se pueden limitar a un buzón. En Entra solo se registra la app y se crea el
secreto.

> **Por qué importa.** `Mail.ReadWrite` y `Mail.Send` otorgados en Entra con
> "Grant admin consent" alcanzan **todos los buzones del tenant**: sueldos,
> dirección, legales, todo. No existe forma de limitarlos desde ahí. Otorgados
> desde Exchange Online con RBAC for Applications, se limitan a tc@ y nada más.
>
> **No se hacen las dos cosas.** Los permisos de Entra y los de Exchange se
> **suman**: si consentís `Mail.Send` en Entra *y además* lo acotás en Exchange,
> el resultado es acceso sin límite, y el acotado no sirvió para nada. En Entra
> este bot no lleva ningún permiso de correo.

### Paso 1 — Registrar la aplicación (Entra)

1. Entrar a [entra.microsoft.com](https://entra.microsoft.com) con una cuenta
   administradora.
2. Menú izquierdo → **Applications** → **App registrations**.
3. Botón **+ New registration**, arriba.
4. Completar:
   - **Name:** `AMA — Bot Tipo de Cambio`
   - **Supported account types:** *Accounts in this organizational directory only
     (Single tenant)*
   - **Redirect URI:** **dejar vacío**. El bot no tiene pantalla de login.
5. **Register**.

> **Registrar una app nueva, no reutilizar la del portal.** Así el secreto de
> este bot se rota sin desloguear al personal, y si se filtra, lo único expuesto
> es un buzón.

**No hay que tocar "API permissions".** Si la abrís, vas a ver la lista vacía (o
solo `User.Read`). Está bien así: los permisos de correo se otorgan en el Paso 4.

### Paso 2 — Crear el secreto

1. En la app recién creada: menú izquierdo → **Certificates & secrets**.
2. Pestaña **Client secrets** → **+ New client secret**.
3. **Description:** `Bot TC — produccion`. **Expires:** 24 meses.
4. **Add**.
5. Copiar **inmediatamente** la columna **Value** (no *Secret ID*). Se muestra
   **una sola vez**: si cerrás la pantalla, hay que generar otro.

→ Va al `.env` como `TC_MAIL_CLIENT_SECRET`.

**Anotar la fecha de vencimiento en el calendario del área.** El día que vence,
el bot deja de contestar y el único aviso es una línea en `app.log`.

### Paso 3 — Anotar los dos identificadores

El Paso 4 necesita dos IDs, y acá se tropieza todo el mundo: **hay que sacarlos
de _Enterprise applications_, no de _App registrations_**. Son pantallas
parecidas con valores distintos.

1. En Entra → **Applications** → **Enterprise applications**.
2. Buscar `AMA — Bot Tipo de Cambio` y abrirla.
3. De la pantalla **Overview** / **Properties**, copiar:
   - **Application ID** → es el `AppId` (y va al `.env` como `TC_MAIL_CLIENT_ID`)
   - **Object ID** → es el `ObjectId` que pide `New-ServicePrincipal`

El **Directory (tenant) ID** (el mismo que ya usa el portal) está en Entra →
**Overview** → `TC_MAIL_TENANT_ID`.

| Valor | De dónde sale | Para qué |
|-------|---------------|----------|
| Application ID | Enterprise applications → la app | `.env` + PowerShell |
| Object ID | Enterprise applications → la app | Solo PowerShell |
| Directory (tenant) ID | Entra → Overview | `.env` |
| Secret **Value** | App registrations → Certificates & secrets | `.env` |

### Paso 4 — Otorgar los permisos, acotados al buzón

Acá es donde se otorgan los permisos de verdad. Es **RBAC for Applications** de
Exchange Online, que desde 2025 reemplaza a las Application Access Policies.

#### 4.1 — Preparar PowerShell (una sola vez por computadora)

**Usar PowerShell 7, no el "Windows PowerShell" azul que viene con Windows.**
El 5.1 que trae el sistema tiene un gestor de módulos viejo que choca al
instalar el módulo de Exchange, y cuando falla suele dejarlo roto (ver los dos
errores al final de esta sección). PowerShell 7 no tiene ese problema.

Instalarlo, desde cualquier terminal:

```powershell
winget install --id Microsoft.PowerShell -e
```

Cerrar la terminal y abrir **PowerShell 7** desde el menú inicio (es un icono
distinto al azul de Windows PowerShell). Ahí:

```powershell
Install-Module -Name ExchangeOnlineManagement -Scope CurrentUser -Force
Get-InstalledModule ExchangeOnlineManagement
```

El segundo comando tiene que devolver una fila con la versión. Después, en cada
sesión:

```powershell
Import-Module ExchangeOnlineManagement
Connect-ExchangeOnline -UserPrincipalName <tu.usuario>@atlantic-agency.example.com
```

Se abre la ventana de login de Microsoft. Si pide MFA, es normal.

##### Si tenés que usar Windows PowerShell 5.1

Hace falta TLS 1.2 (los 5.1 sin actualizar no llegan a la galería sin eso) y
`-AllowClobber`:

```powershell
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

Install-Module -Name ExchangeOnlineManagement -Scope CurrentUser -Force -AllowClobber
```

`-AllowClobber` no es opcional acá. El módulo arrastra una versión nueva de
`PackageManagement`, que reemplaza los comandos `Find-Package`,
`Install-Package` y `Uninstall-Package` que ya trae el sistema. Sin el flag,
PowerShell aborta con `CommandAlreadyAvailable ... may override the existing
commands`. El flag solo autoriza ese reemplazo de comandos.

**Cerrar y volver a abrir PowerShell** después de instalar, para que cargue los
módulos nuevos.

##### Si ya falló y ahora dice que `PowerShellGet` no puede cargarse

```
Install-Module : El comando 'Install-Module' se encontró en el módulo
'PowerShellGet', pero el módulo no pudo cargarse.
```

El intento fallido dejó una copia parcial del gestor de módulos en el perfil del
usuario, y el `PowerShellGet` del sistema ya no arranca con esa mezcla. La salida
limpia es PowerShell 7 (arriba). Para repararlo en el 5.1:

```powershell
# Ver qué versiones hay y desde dónde cargan
Get-Module PowerShellGet, PackageManagement -ListAvailable |
    Select-Object Name, Version, Path
```

Borrar **solo** las carpetas que estén bajo el perfil del usuario, que son las
copias parciales:

```
%USERPROFILE%\Documents\WindowsPowerShell\Modules\PowerShellGet
%USERPROFILE%\Documents\WindowsPowerShell\Modules\PackageManagement
```

> ⚠️ **No tocar las de `C:\Program Files\WindowsPowerShell\Modules`.** Esas son
> las que vienen con Windows: borrarlas deja el sistema sin gestor de módulos.

Cerrar y volver a abrir PowerShell, y reintentar el `Install-Module` con los dos
parámetros de arriba.

##### Última alternativa: instalar el módulo a mano

Si no se puede instalar PowerShell 7 ni reparar el gestor (equipo con políticas
corporativas), el módulo se puede copiar a mano, sin gestor de por medio:

1. Bajar `https://www.powershellgallery.com/api/v2/package/ExchangeOnlineManagement`
   (descarga un archivo `.nupkg`).
2. Renombrarlo a `.zip` y descomprimirlo.
3. Copiar el contenido a
   `%USERPROFILE%\Documents\WindowsPowerShell\Modules\ExchangeOnlineManagement\3.9.0\`
   (la carpeta con el número de versión que corresponda; el número está en el
   nombre del `.nupkg`).
4. Desbloquear los archivos bajados de internet y verificar que cargue:

```powershell
Get-ChildItem "$env:USERPROFILE\Documents\WindowsPowerShell\Modules\ExchangeOnlineManagement" -Recurse |
    Unblock-File
Import-Module ExchangeOnlineManagement
```

#### 4.2 — Confirmar el alias del buzón

El ámbito se arma sobre el alias, no sobre la dirección (Exchange no acepta
`PrimarySmtpAddress` en estos filtros):

```powershell
Get-Mailbox tc@atlantic-agency.example.com | Select-Object Alias, RecipientTypeDetails
```

Anotar el `Alias` que devuelva (probablemente `tc`). Si `RecipientTypeDetails`
dice `SharedMailbox`, mejor: un buzón compartido no consume licencia.

#### 4.3 — Los cuatro comandos

Reemplazar `<APP-ID>`, `<OBJECT-ID>` y `<ALIAS>` por los valores de los pasos
anteriores:

```powershell
# 1. El puntero a la aplicación, del lado de Exchange
New-ServicePrincipal -AppId <APP-ID> -ObjectId <OBJECT-ID> `
    -DisplayName "AMA Bot Tipo de Cambio"

# 2. El ámbito: qué buzones puede tocar. Uno solo.
New-ManagementScope -Name "Scope-BotTipoCambio" `
    -RecipientRestrictionFilter "Alias -eq '<ALIAS>'"

# 3. Permiso de lectura y escritura de correo, SOLO en ese ámbito.
#    Es ReadWrite y no Read porque el bot crea el borrador de la respuesta
#    y marca el mensaje como leído: las dos cosas son escrituras.
New-ManagementRoleAssignment -App <APP-ID> `
    -Role "Application Mail.ReadWrite" `
    -CustomResourceScope "Scope-BotTipoCambio"

# 4. Permiso de envío, SOLO en ese ámbito.
New-ManagementRoleAssignment -App <APP-ID> `
    -Role "Application Mail.Send" `
    -CustomResourceScope "Scope-BotTipoCambio"
```

#### 4.4 — Verificar que el ámbito realmente limita

Este paso no se saltea. Es lo único que prueba que la app no llegue al resto de
la empresa:

```powershell
# Debe decir InScope: True
Test-ServicePrincipalAuthorization -Identity <APP-ID> `
    -Resource tc@atlantic-agency.example.com | Format-Table

# Debe decir InScope: FALSE (probar con cualquier otro buzón real)
Test-ServicePrincipalAuthorization -Identity <APP-ID> `
    -Resource finance@atlantic-agency.example.com | Format-Table
```

Si el segundo da `True`, el filtro del ámbito está mal: la app está viendo
buzones que no le corresponden. **No seguir hasta que dé `False`.**

> Los cambios de permisos tardan entre **30 minutos y 2 horas** en aplicarse por
> caché. `Test-ServicePrincipalAuthorization` saltea la caché, así que puede dar
> bien mientras el bot todavía recibe 403. Si el test está verde, esperá y volvé
> a probar.

#### 4.5 — Si tu tenant no soporta RBAC for Applications

Alternativa heredada: consentir `Mail.ReadWrite` y `Mail.Send` en Entra →
**API permissions** → **Add a permission** → **Microsoft Graph** →
**Application permissions** → **Grant admin consent**, y recién entonces acotar
con una Application Access Policy:

```powershell
New-DistributionGroup -Name "Bot-TipoCambio-Scope" -Type Security `
    -Members tc@atlantic-agency.example.com

New-ApplicationAccessPolicy -AppId "<APP-ID>" `
    -PolicyScopeGroupId "Bot-TipoCambio-Scope" `
    -AccessRight RestrictAccess `
    -Description "Bot de tipo de cambio: solo el buzon tc@"

Test-ApplicationAccessPolicy -Identity tc@atlantic-agency.example.com -AppId "<APP-ID>"
Test-ApplicationAccessPolicy -Identity finance@atlantic-agency.example.com -AppId "<APP-ID>"
```

El primer `Test` debe dar `Granted`; el segundo, **`Denied`**.

Microsoft marcó este camino como **legacy** y pide no usarlo para
configuraciones nuevas, así que conviene solo como plan B. Y no se combina con
el Paso 4: **un camino o el otro**, nunca los dos, porque los permisos se suman
y el acotado se pierde.

### Paso 5 — Cargar la configuración en el servidor

Agregar al `.env` (el bloque completo está en `.env.example`):

```env
TC_MAIL_CLIENT_ID=<Application ID del Paso 3>
TC_MAIL_CLIENT_SECRET=<Value del Paso 2>
TC_MAIL_TENANT_ID=<Directory (tenant) ID>
TC_MAIL_BUZON=tc@atlantic-agency.example.com
TC_BOT_TOKEN=<aleatorio, solo si el cron llama por URL>
```

### Paso 6 — Migración de base de datos

Correr en phpMyAdmin:

```
database/migrations/2026_09_tc_mailbot.sql
```

Crea `tc_mail_log`. Es idempotente: se puede correr varias veces.

La clave única sobre `internet_message_id` no es decorativa — es lo que impide
que un mismo correo se conteste dos veces si dos corridas del cron se
superponen.

### Paso 7 — Probar en seco, antes de contestarle a nadie

```bash
php /home/<usuario>/public_html/src/logic/api/tc_mailbot.php --simulacro
```

En modo simulacro el bot lee el buzón y dice qué haría, **sin enviar nada y sin
escribir en la base**. Salida esperada:

```
[2026-09-17 10:05:01] OK | estado TC: ok | revisados: 4, respondidos: 0, omitidos: 4, errores: 0
  - simulacro              responderia_ok               cliente@empresa.com
  - omitido                remitente_automatico         no-reply@banco.com
  - omitido                mensaje_antiguo              otro@empresa.com
  - omitido                asunto_automatico            colega@empresa.com
```

Revisar esa lista con calma: es exactamente lo que el bot va a hacer cuando se
encienda.

### Paso 8 — Programar la tarea

En el panel de el hosting compartido/cPanel → **Tareas Programadas**, cada 5 minutos:

```
*/5 * * * *  php /home/<usuario>/public_html/src/logic/api/tc_mailbot.php
```

Si el plan de hosting no permite ejecutar PHP por CLI, la alternativa es
llamarlo por URL (requiere `TC_BOT_TOKEN` en el `.env`):

```
*/5 * * * *  curl -s "https://atlantic-agency.example.com/api/tc_mailbot?token=<TC_BOT_TOKEN>"
```

Dos corridas nunca se pisan: la segunda encuentra el cerrojo tomado y sale sin
hacer nada.

## 2. Operación

### Ver qué hizo el bot

```sql
SELECT creado_en, remitente, asunto, estado_tc, accion, motivo
FROM tc_mail_log
ORDER BY creado_en DESC
LIMIT 50;
```

El log técnico va a `src/data/logs/app.log`, con una línea de resumen por
corrida.

### Apagarlo

Desactivar la tarea programada en el panel. Alternativa sin tocar el panel:
vaciar `TC_MAIL_CLIENT_ID` en el `.env` — el bot sale por "no configurado" y no
lee el buzón. No deja rastro en el buzón: los mensajes simplemente quedan sin
leer, como antes.

### Filas trabadas en `en_proceso`

Si una corrida muere entre que reserva el mensaje y lo envía, la fila queda en
`en_proceso` y ese mail no se reintenta. Es deliberado: mejor un mensaje sin
contestar que uno contestado dos veces. Para liberarlo a mano:

```sql
DELETE FROM tc_mail_log WHERE accion = 'en_proceso' AND creado_en < NOW() - INTERVAL 1 HOUR;
```

Y marcar el mensaje como no leído en Outlook para que la próxima corrida lo tome.

---

## 3. Problemas frecuentes

### "Bot no configurado"
Falta alguna de las cuatro variables `TC_MAIL_*` en el `.env`.

### HTTP 403 `ErrorAccessDenied` al leer el buzón
Cuatro causas, en orden de probabilidad:
1. **La caché todavía no se refrescó.** Los cambios de permisos tardan entre 30
   minutos y 2 horas. Correr `Test-ServicePrincipalAuthorization`: si da
   `InScope: True`, está bien configurado y solo hay que esperar.
2. Falta alguna de las dos asignaciones del Paso 4.3. Verificar con
   `Get-ManagementRoleAssignment -App <APP-ID>`.
3. El filtro del ámbito no matchea el buzón. Confirmar el alias con
   `Get-Mailbox tc@atlantic-agency.example.com | Select Alias` y compararlo con el del
   `New-ManagementScope`.
4. Se usaron los IDs de **App registrations** en vez de los de **Enterprise
   applications** en `New-ServicePrincipal` (Paso 3).

### HTTP 403 al responder, pero la lectura funciona
Falta el rol `Application Mail.ReadWrite` o `Application Mail.Send`. El bot
necesita los dos: crear el borrador de la respuesta y marcar el mensaje como
leído son escrituras, no alcanza con `Mail.Read`.

### `CommandAlreadyAvailable` al instalar el módulo de Exchange
Falta `-AllowClobber`, o directamente conviene usar PowerShell 7. Ver Paso 4.1.

### `PowerShellGet ... el módulo no pudo cargarse`
Secuela del error anterior: el intento fallido dejó el gestor de módulos a medio
actualizar. Ver Paso 4.1, sección de reparación.

### HTTP 401 `invalid_client`
El secreto venció o está mal copiado. Generar uno nuevo (Paso 2). Ojo con copiar
el *Secret ID* en lugar del *Value*: es el error más común.

### El bot no contesta y el log no dice nada
Revisar en este orden: ¿está corriendo el cron? ¿los mensajes están sin leer?
¿tienen menos de `TC_BOT_ANTIGUEDAD_HORAS`? ¿ya hay una fila en `tc_mail_log`
para ese `internet_message_id`?

Un caso fácil de pasar por alto: **el bot solo mira la Bandeja de entrada**. Si
hay una regla de Outlook que mueve las consultas a una subcarpeta, el bot no las
ve. O se desactiva esa regla, o se cambia la ruta en `GraphMail::mensajesNoLeidos()`.

### Contestó algo que no debía
Agregar el patrón a `LOCALES_PROHIBIDOS` o `ASUNTOS_AUTOMATICOS` en
`src/core/tc_mailbot.php`, y sumar el caso a `tests/test_tc_mailbot.php`.

---

## 4. Detalle técnico

### Archivos

| Archivo | Rol |
|---------|-----|
| `src/core/tipo_cambio.php` | Regla de negocio única (horario + cotización del día). La comparten la web y el bot |
| `src/core/graph_mail.php` | Cliente de Microsoft Graph app-only (curl nativo, sin Composer) |
| `src/core/tc_mailbot.php` | Motor del bot: filtros, bitácora y armado de respuestas |
| `src/logic/api/tc_mailbot.php` | Disparador: CLI para el cron, HTTP con token como alternativa |
| `src/email-templates/tipo_cambio.html` | Plantilla de la respuesta al cliente |
| `database/migrations/2026_09_tc_mailbot.sql` | Tabla `tc_mail_log` |
| `tests/test_tc_mailbot.php` | 57 casos: horario, anti-bucle, escapado y estados |

### Decisiones que conviene no revertir sin pensarlo

- **Se responde con Graph desde tc@, no con el SMTP de el hosting compartido.** El cliente ve
  la respuesta en su hilo, viniendo de la casilla a la que escribió, y queda
  archivada en Elementos enviados de tc@. Como sale por Microsoft 365, SPF y
  DKIM ya están alineados: no hay que tocar el DNS.
- **Solo se marcan como leídos los mensajes contestados.** Es lo que mantiene la
  bandeja usable para las personas del área: lo que queda en negrita es lo que
  necesita a alguien.
- **El estado del tipo de cambio se resuelve una vez por corrida.** Todos los
  mensajes de la misma tanda reciben la misma respuesta.
- **Si la base falla, no se responde.** Ver la tabla del punto 0.

### Correr los tests

```bash
php tests/test_tc_mailbot.php
```

No necesita base de datos ni red: usa SQLite en memoria con `CURDATE()`
registrada como función para ejercitar el SQL real.
