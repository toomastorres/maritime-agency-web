# Correo del dominio — Casillas y envío desde la aplicación

**Versión:** 1.0 (Septiembre 2026)
**Dirigido a:** Administrador de Microsoft 365 de Atlantic Maritime Agency.

---

## 0. El dato que cambia todo

**El correo de `atlantic-agency.example.com` vive en Microsoft 365, no en el hosting.**

Estado real del DNS, verificado en septiembre 2026:

| Registro | Valor | Qué significa |
|---|---|---|
| `MX` | `atlanticagency-example-com.mail.protection.outlook.com` | Todo el correo entrante va a Microsoft |
| `TXT` (SPF) | `v=spf1 include:spf.protection.outlook.com -all` | **Solo Microsoft** puede enviar como el dominio |
| `CNAME selector1._domainkey` | `...atlanticagency.n-v1.dkim.mail.microsoft` | DKIM delegado a Microsoft |
| `CNAME selector2._domainkey` | `...atlanticagency.n-v1.dkim.mail.microsoft` | DKIM delegado a Microsoft |
| `TXT _dmarc` | `v=DMARC1; p=quarantine` | Lo que no valide, a cuarentena |
| `CNAME autodiscover` | `autodiscover.outlook.com` | Outlook se configura solo |
| `A` | `198.51.100.42` | Solo el **sitio web** está en el hosting compartido |

> **La zona DNS ya está bien.** No hay nada que agregar para que Microsoft
> "certifique" el correo: SPF, DKIM y DMARC ya están los tres puestos y
> apuntando a Microsoft. El problema de spam no se resuelve tocando el DNS.

---

## 0.bis Cuál de las dos zonas DNS manda

El dominio tiene una zona en **el proveedor DNS** y otra en el panel de **el hosting compartido**.
**Solo la de el proveedor DNS existe para internet.** Verificado en septiembre 2026:

```
NS  atlantic-agency.example.com  ->  ns1.dns-proveedor.example
                             ns2.dns-proveedor.example
                             ns3.dns-proveedor.example
SOA primario             ->  ns1.dns-proveedor.example
```

Ningún servidor de el hosting compartido figura entre los autoritativos. Los registros que
resuelve el mundo —el MX a Microsoft, el SPF, los DKIM— salen de el proveedor DNS.

Consecuencias prácticas:

- **Todo cambio de DNS se hace en el panel de el proveedor DNS.** Es el único lugar
  donde tiene efecto.
- **La zona de el hosting compartido es decorativa.** el hosting compartido la crea por defecto para cualquier
  dominio alojado, pero como nadie la consulta, lo que se escriba ahí no hace
  nada. Editarla no rompe nada; simplemente no pasa nada, y se pierde tiempo
  buscando por qué el cambio "no propaga".
- El panel de el hosting compartido sigue sirviendo para **generar** valores (la clave DKIM,
  el SPF sugerido). Se generan ahí y se **publican en el proveedor DNS**.

### ⛔ Lo único que nunca hay que hacer

**No cambiar los nameservers del dominio a los de el hosting compartido.** Es lo que suele
sugerir el soporte de un hosting cuando algo de DNS no anda, y sería
catastrófico: la zona de el hosting compartido no tiene el MX de Microsoft, ni el SPF, ni los
DKIM, ni el registro de verificación del tenant. En cuanto propague, **el correo
de toda la empresa deja de llegar** y Microsoft pierde la validación del
dominio.

Si el hosting compartido pide "apuntá los nameservers a nosotros", la respuesta es que el
dominio se administra en el proveedor DNS y que solo se necesita el **valor** del
registro, no el cambio de delegación.

---

## 1. Las dos rutas posibles

Hay dos formas de que la aplicación mande correo como `@atlantic-agency.example.com`
sin caer en spam. **Ambas son válidas**; la diferencia es el costo y a quién le
podés escribir.

| | **A. Casilla del hosting** | **B. Casilla de Microsoft 365** |
|---|---|---|
| Costo | **$0** (incluida en el hosting) | Una licencia más (Exchange Online Plan 1) |
| Cambios de DNS | Sí: sumar SPF + DKIM de el hosting compartido | Ninguno |
| Sirve para destinatarios externos | Sí, con reputación de IP compartida | Sí, con la reputación de Microsoft |
| Vence | No | La auth básica de SMTP, fines de 2026 |

**Para este proyecto, la ruta A es la adecuada.** Todos los correos que manda la
aplicación son notificaciones internas a direcciones `@atlantic-agency.example.com`
(los `NOTIFY_*`): postulaciones a RRHH, cotizaciones a Comercial, avisos a Caja
y CS. No le escribe a clientes ni a postulantes. Para ese uso, la casilla del
hosting alcanza y no justifica pagar una licencia.

> Si en el futuro la aplicación tuviera que escribirle a gente de afuera
> (confirmación al postulante, por ejemplo), conviene revisar la decisión: la IP
> de un hosting compartido tiene peor reputación que la de Microsoft. En ese
> caso, antes que pagar una licencia, la opción barata es un proveedor
> transaccional con plan gratuito (Brevo, Mailjet, SMTP2GO, Resend), que se
> suma al SPF igual que el hosting compartido.

---

## 2. Ruta A — Casilla del hosting, en un subdominio (gratuita, recomendada)

### El problema que resuelve el subdominio

Un servidor de correo, antes de salir a buscar el MX de un destinatario, mira si
**él mismo** es responsable de ese dominio. Si el hosting tiene alojado a
`atlantic-agency.example.com` y le pedimos que mande a `rrhh@atlantic-agency.example.com`,
razona "ese dominio es mío" y entrega en su propio disco: el mensaje nunca sale
hacia Microsoft y queda en una casilla del hosting que nadie lee.

Es un riesgo real, no teórico. En cPanel se controla con **Email Routing**
(*Local* vs *Remote Mail Exchanger*), y ponerlo en **Remote** lo corrige. Pero
esa solución tiene dos costados incómodos:

- Es un interruptor global del dominio: si alguien lo vuelve a *Local* —o el
  panel lo hace solo al crear una casilla—, el correo interno se cae sin aviso.
- Con el dominio en *Remote*, el servidor tampoco entrega **hacia**
  `no-reply@atlantic-agency.example.com`: los rebotes y respuestas se van a Microsoft,
  donde esa casilla no existe.

**El subdominio elimina el problema de raíz.** Para el servidor,
`avisos.atlantic-agency.example.com` es un dominio distinto de
`atlantic-agency.example.com`. Al mandar a `rrhh@atlantic-agency.example.com`, no lo reconoce
como propio, hace la consulta MX normal, encuentra a Microsoft y entrega. Sin
interruptores, sin ambigüedad.

### Por qué además es la opción más segura

- **No se toca el SPF del dominio principal.** Ese registro es el que sostiene
  el correo de toda la empresa; el subdominio lleva el suyo propio y el original
  queda intacto. Desaparece el riesgo que más preocupaba.
- **Separa la reputación.** Si la aplicación alguna vez genera rebotes o
  quejas, el daño queda contenido en el subdominio y no arrastra al correo del
  personal.
- **Los rebotes tienen a dónde volver**, porque el subdominio sí puede recibir
  en el hosting.

### Qué copiar de la zona de el hosting compartido a el proveedor DNS

Al crear el subdominio, el hosting compartido genera en **su** zona (la inerte, ver 0.bis) una
lista larga de registros. **No hay que copiarla entera**: la mayoría sobra y dos
de ellos harían daño.

#### Copiar — los 5 que importan

| # | Nombre | Tipo | Valor |
|---|---|---|---|
| 1 | `avisos.atlantic-agency.example.com` | **`A`** | `198.51.100.42` ← **publicar este primero** |
| 2 | `mail.avisos.atlantic-agency.example.com` | `A` | `198.51.100.42` |
| 3 | `avisos.atlantic-agency.example.com` | `MX` prio `10` | `mail.avisos.atlantic-agency.example.com` |
| 4 | `avisos.atlantic-agency.example.com` | `TXT` | `v=spf1 include:spf.hosting-ejemplo.example -all` |
| 5 | `mail._domainkey.avisos.atlantic-agency.example.com` | `TXT` | la clave DKIM completa del subdominio |

#### ⛔ NO copiar — los dos peligrosos

**`avisos.atlantic-agency.example.com CNAME atlantic-agency.example.com`**

En el proveedor DNS hay que publicarlo como **`A`**, no como `CNAME`. Un nombre con
`CNAME` **no puede tener ningún otro registro**: es una regla del protocolo
(RFC 1034). Como `avisos` necesita llevar su `MX` y su `TXT` de SPF, el `CNAME`
los invalida. La zona de el hosting compartido tiene las tres cosas a la vez porque nadie la
consulta; en una zona real, eso no resuelve.

**`_dmarc.atlantic-agency.example.com TXT v=DMARC1; p=none`**

Este es el más peligroso de los dos, porque **no es del subdominio: es del
dominio principal**. El valor real en el proveedor DNS es `p=quarantine`; el de el hosting compartido
es el `p=none` que pone por defecto. Copiarlo **bajaría la protección de todo
el correo de la empresa** sin que nadie lo note, porque `p=none` significa "no
hagas nada con lo que falle".

Ese registro ya existe y está bien. **No tocarlo.**

#### Descartar — los que no hacen falta

| Registro | Por qué |
|---|---|
| `avisos...MX mx1.atlantic-agency.example.com` | `mx1` **no existe** en la zona real: es un MX roto |
| `www.avisos.atlantic-agency.example.com CNAME` | Es para servir una web en el subdominio; no la hay |
| `autoconfig.avisos...` y `autodiscover.avisos...` | Autoconfiguración de Outlook/Thunderbird; la casilla solo envía desde el servidor |
| `_imaps`, `_pop3s`, `_smtps`, `_submission` (SRV) | Lo mismo: autoconfiguración de clientes de correo |
| `mail.avisos... AAAA` | IPv6, opcional. Solo afecta la recepción de rebotes |

Nada de eso rompe si se publica, pero ensucia la zona y da más superficie para
equivocarse. Cuantos menos registros, mejor.

#### Recomendado — el DMARC del subdominio, para un despliegue seguro

Sin registro propio, `avisos` hereda el `p=quarantine` del dominio principal.
Conviene publicarlo en `p=none` **mientras se prueba**, para que un error de
configuración no mande los avisos a cuarentena:

| Nombre | Tipo | Valor |
|---|---|---|
| `_dmarc.avisos.atlantic-agency.example.com` | `TXT` | `v=DMARC1; p=none; rua=mailto:dmarc@atlantic-agency.example.com` |

Una vez confirmado `spf=pass` y `dkim=pass` en un correo real, **subirlo a
`p=quarantine`** (o borrar el registro para que vuelva a heredar del dominio).
Dejarlo en `p=none` para siempre sería renunciar a la protección en ese
subdominio.

### Por qué el DMARC igual va a dar `pass`

El DMARC del dominio es `v=DMARC1; p=quarantine`, **sin etiqueta `sp=`**, así
que los subdominios heredan `p=quarantine`. El mensaje lo supera igual:

- El `From:` será `no.reply@avisos.atlantic-agency.example.com`.
- El SPF se evalúa contra ese mismo dominio, que autoriza a el hosting compartido → `pass`.
- El DKIM firma con `d=avisos.atlantic-agency.example.com` → `pass` y alineado.

Con SPF y DKIM alineados sobre el mismo dominio del `From:`, DMARC da `pass`.

### Pasos en el panel del hosting

1. ~~Crear el subdominio~~ — hecho.
2. ~~Crear la casilla~~ — hecho: `no.reply@avisos.atlantic-agency.example.com`.
3. ~~Confirmar el host SMTP~~ — hecho: `mail.hosting-ejemplo.example`, puerto **465**,
   **SSL** (resuelve a `198.51.100.42`).
4. **Generar el DKIM del subdominio** — *Email Deliverability* → fila de
   `avisos.atlantic-agency.example.com` → copiar el TXT. **Pendiente.**
5. **Publicar los cuatro registros en el proveedor DNS** (tabla de arriba).
   **Pendiente:** al 17/09/2026 ninguno de los cuatro resuelve todavía.

### El `.env` con esta ruta

```env
SMTP_HOST=mail.hosting-ejemplo.example
SMTP_PORT=465
SMTP_SECURE=ssl
SMTP_USER=no.reply@avisos.atlantic-agency.example.com
SMTP_PASS=<la contraseña de la casilla>
SMTP_FROM=no.reply@avisos.atlantic-agency.example.com
SMTP_FROM_NAME=AMA Sistemas
SMTP_REPLY_TO=info@atlantic-agency.example.com
```

`SMTP_REPLY_TO` apunta al dominio principal: quien responda una notificación le
escribe a una casilla real de Microsoft, no al subdominio.

### Variante: casilla en el dominio principal

Solo si se descarta el subdominio. Exige poner **Email Routing → Remote Mail
Exchanger** para `atlantic-agency.example.com` **antes** de crear la casilla, y
**modificar** el SPF del dominio (no agregar un segundo registro) para que quede:

```
v=spf1 include:spf.protection.outlook.com include:spf.hosting-ejemplo.example -all
```

más el DKIM en `mail._domainkey.atlantic-agency.example.com`. Es la ruta que describe la
sección 2.ter. Funciona, pero deja el correo de toda la empresa dependiendo de
un interruptor del panel, y por eso no es la recomendada.

## 2.bis Ruta B — Casilla de Microsoft 365 (paga)

Solo si se decide pagar la licencia. No requiere ningún cambio de DNS.

1. <https://admin.microsoft.com> → **Usuarios → Usuarios activos → Agregar**.
2. Usuario `no-reply`, nombre para mostrar `AMA Sistemas`.
3. **Asignarle una licencia** (Exchange Online Plan 1). Una casilla compartida
   sin licencia **no puede autenticarse por SMTP**.
4. **Usuarios activos → `no-reply` → Correo → Administrar aplicaciones de correo
   electrónico →** tildar **SMTP autenticado**.
5. Si el tenant tiene **Security Defaults** activo, SMTP falla con `535 5.7.139`
   igual. Hay que reemplazarlo por una política de Acceso Condicional que exija
   MFA a todos **excluyendo** esta cuenta. No apagar Security Defaults sin
   política de reemplazo: dejaría a todo el personal sin MFA.

Bloque del `.env` para esta ruta:

```env
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_SECURE=tls
```

## 2.ter Registros para la variante en el dominio principal

> Esto aplica **solo** a la variante del final de la sección 2, no a la ruta
> recomendada del subdominio. En la ruta del subdominio el SPF del dominio
> principal **no se toca**.

Valores confirmados con el panel de el hosting compartido (septiembre 2026), para publicar en
la zona de el proveedor DNS.

### Registro 1 — SPF (MODIFICAR el que ya existe)

Este es el delicado: **no se agrega un registro nuevo**, se edita el que ya
está. Un dominio con dos registros SPF falla la validación por completo.

| | |
|---|---|
| **Nombre** | `atlantic-agency.example.com` (la raíz, o `@` según el panel) |
| **Tipo** | `TXT` |
| **Valor actual** | `v=spf1 include:spf.protection.outlook.com -all` |
| **Valor nuevo** | `v=spf1 include:spf.protection.outlook.com include:spf.hosting-ejemplo.example -all` |

Se agrega `include:spf.hosting-ejemplo.example` **antes** del `-all`, sin tocar nada más.

Consumo de consultas DNS: Outlook 1 + hosting-ejemplo 1 + el `exists:` que hosting-ejemplo usa
internamente 1 = **3 de las 10 permitidas**. Sin riesgo de `permerror`.

> Nota: `spf.hosting-ejemplo.example` autoriza el conjunto de servidores de salida de
> el hosting compartido, no solo el tuyo. Es inherente al hosting compartido y es el modo en
> que el hosting compartido lo documenta.

### Registro 2 — DKIM de el hosting compartido (AGREGAR nuevo)

| | |
|---|---|
| **Nombre** | `mail._domainkey.atlantic-agency.example.com` |
| **Tipo** | `TXT` |
| **Valor** | `v=DKIM1; g=*; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0ExamplePublicKeyForPortfolioDocumentationOnly00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000IDAQAB` |

El selector es **`mail`** (no `default`). No interfiere con `selector1` ni
`selector2`, que son los de Microsoft.

**Dos detalles del panel de el proveedor DNS:**

1. **El valor mide 415 caracteres y una cadena TXT admite 255.** La mayoría de
   los paneles parten el valor solos; si el de el proveedor DNS lo rechaza por largo,
   hay que cargarlo en dos cadenas entre comillas dentro del mismo registro:

   ```
   "v=DKIM1; g=*; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0ExamplePublicKeyForPortfolioDocumentationOnly0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000"
   "0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000IDAQAB"
   ```

   **No cortar la clave con saltos de línea ni espacios**: partirla en cadenas
   es distinto de romperla.

2. `g=*;` es una etiqueta heredada del viejo DomainKeys, retirada en el estándar
   actual. Es inofensiva (`*` significa "cualquier remitente", el comportamiento
   por defecto). Si el panel la rechazara, se puede publicar sin ella.

### Verificación (a los 15–30 minutos)

Desde cualquier terminal, o en <https://mxtoolbox.com>:

```bash
dig +short TXT atlantic-agency.example.com
dig +short TXT mail._domainkey.atlantic-agency.example.com
```

El primero tiene que devolver **un solo** SPF, con los dos `include`. Si
devuelve dos registros SPF, hay que borrar el que sobra.

---

## 3. Configurar el servidor

El bloque de correo del `.env` es el de la ruta elegida (ver arriba).

Valen las reglas de siempre del `.env` de este hosting: sin comillas, fin de
línea LF, FTP en binario, permisos 644. **Y no reemplazar el archivo: agregar
sobre lo existente**, o se pierde el bloque de base de datos.

No hay que tocar código: `src/core/email.php` pasa `SMTP_SECURE` directo a
PHPMailer, que entiende `tls` como STARTTLS en el puerto 587.

---

## 4. Verificar que funciona

1. Disparar un envío real desde el portal (una notificación cualquiera).
2. Revisar `public_html/src/data/logs/email_audit.log`: hay una línea JSON por
   envío con `status` y `error`.
3. En el correo recibido, abrir **Ver origen del mensaje** y buscar
   `Authentication-Results`. Tiene que decir:

   ```
   spf=pass   dkim=pass   dmarc=pass
   ```

   Si alguno dice `fail`, el mensaje no salió por Microsoft.

### Errores frecuentes

| Error SMTP | Causa |
|---|---|
| `535 5.7.139` autenticación no soportada | Falta habilitar SMTP AUTH en la casilla, o lo bloquea Security Defaults / Acceso Condicional |
| `535 5.7.3` authentication unsuccessful | Contraseña incorrecta, o la casilla no tiene licencia |
| `550 5.7.60` no puede enviar como este remitente | `SMTP_FROM` no coincide con `SMTP_USER` y la cuenta no tiene permiso *Send As* |
| Llega a spam con `spf=fail` | El servidor que envía no está en el SPF del dominio |
| Llega a spam con `dkim=none` | Falta publicar el TXT `default._domainkey` de cPanel |
| El correo entrante deja de llegar a Outlook | El dominio quedó en *Local Mail Exchanger*: volver a *Remote* |

---

## 4.bis Que el correo del personal (Microsoft) no caiga en spam

Esto es independiente de la aplicación: son las casillas de las personas.

El DNS ya está a favor: SPF, DKIM y DMARC están los tres publicados y las claves
DKIM del tenant están provisionadas (verificado en los destinos de los CNAME
`selector1`/`selector2`). Si aun así los mensajes caen en spam, revisar en este
orden.

### 1. Confirmar que Microsoft está firmando (la causa más común)

Que los CNAME existan **no** significa que Microsoft esté firmando. Es un
interruptor aparte, y si está apagado, los mensajes salen firmados como
`atlanticagency.onmicrosoft.com` en lugar de `atlantic-agency.example.com`: el DKIM no
alinea con el dominio y el DMARC pasa a depender solo del SPF.

<https://security.microsoft.com> → **Email & collaboration → Policies & rules →
Threat policies → Email authentication settings → DKIM** → seleccionar
`atlantic-agency.example.com` → **Sign messages for this domain with DKIM signatures**
debe estar en **Enabled**.

### 2. Leer el encabezado de un mensaje real

Mandar un correo a una cuenta externa (Gmail personal sirve) y abrir
**Mostrar original** / **Ver origen del mensaje**. Buscar:

```
Authentication-Results: spf=pass (sender IP is ...)
    smtp.mailfrom=atlantic-agency.example.com;
    dkim=pass (signature was verified) header.d=atlantic-agency.example.com;
    dmarc=pass action=none header.from=atlantic-agency.example.com;
```

Lo importante es que **`header.d` diga `atlantic-agency.example.com`** y no
`...onmicrosoft.com`. Si dice onmicrosoft, volver al punto 1.

### 3. Darle visibilidad al DMARC

Hoy el DMARC es `v=DMARC1; p=quarantine`, sin dirección de reportes: está
mandando a cuarentena sin que nadie se entere de qué ni de quién. Cambiarlo en
el proveedor DNS por:

```
v=DMARC1; p=quarantine; rua=mailto:dmarc@atlantic-agency.example.com; fo=1
```

Es seguro: `rua` solo pide reportes, no cambia cómo se evalúan los mensajes. A
los pocos días llegan informes XML que muestran exactamente qué servidores están
enviando como el dominio y cuáles fallan.

### 4. Reputación: lo que no se arregla con DNS

Si la autenticación da `pass` en las tres y aun así hay spam, ya no es
configuración:

- **Dominio nuevo o poco usado.** La reputación se construye con volumen
  constante y sin rebotes. Empeora si el primer envío masivo sale de golpe.
- **Contenido.** Un mensaje con una sola imagen y poco texto, acortadores de
  URL, o un asunto en mayúsculas, puntúa mal aunque autentique perfecto.
- **Listas.** Mandar a direcciones inexistentes genera rebotes duros que hunden
  la reputación rápido.
- **Registrarse en las herramientas de los grandes.** Para Microsoft:
  <https://sendersupport.olc.protection.outlook.com/snds/> (SNDS). Para Google:
  Postmaster Tools. Muestran cómo ven tu dominio desde el otro lado.

### 5. Verificadores externos

Un envío a un servicio de prueba da el panorama completo de una sola vez:

- <https://www.mail-tester.com> — puntúa el mensaje sobre 10 y detalla qué falla.
- <https://mxtoolbox.com/deliverability> — igual, con foco en listas negras.

Conviene hacerlo **antes** de dar por cerrado el tema, y repetirlo después de
publicar el SPF y el DKIM de el hosting compartido.

---

## 4.ter A dónde van las notificaciones (y por qué importa que existan)

`SMTP_REPLY_TO` y `NOTIFY_TO` **no son decorativos**: cumplen dos funciones
distintas y una de ellas puede perder correo en silencio.

### Las dos funciones

**`SMTP_REPLY_TO` — a dónde contesta quien responde.** La casilla remitente es
`no.reply@avisos...`, que nadie lee. Este encabezado hace que, al responder una
notificación, el mensaje vaya a una casilla atendida.

**`NOTIFY_TO` — el destinatario de respaldo.** Acá está el riesgo real. En
`src/core/email.php`, `resolveChannel()` busca primero la dirección específica
del área (`NOTIFY_RRHH`, `NOTIFY_CAJA`…); si está vacía, cae a `NOTIFY_TO`; y si
esa también falta, al remitente. Con `NOTIFY_TO` apuntando a una casilla que ya
no existe, **el aviso se envía correctamente y se pierde**: el envío figura como
exitoso en el registro, y el rebote vuelve a `no.reply@avisos`, que nadie mira.

Es la peor clase de falla: silenciosa. Una postulación a RRHH que nunca llega no
avisa que no llegó.

### Qué poner

Una **casilla compartida de Microsoft 365**, que **no consume licencia** (hasta
50 GB, mientras nadie inicie sesión directamente en ella; los empleados la ven
desde su propia cuenta). Sirve exactamente para esto y no agrega costo.

Si `info@` ya no existe, hay que crear su reemplazo —`contacto@`, `sistemas@`,
`avisos@`— como casilla compartida y usarla en los dos valores.

> **Estado al 17/09/2026:** provisionalmente apuntan a
> `sistemas@atlantic-agency.example.com`, una casilla personal. Es deliberado para la
> puesta en marcha —permite ver de primera mano todo lo que llega, incluidos
> los avisos que caen al respaldo—, pero **queda pendiente reemplazarla por una
> casilla compartida** antes de dar el sistema por cerrado. Mientras siga así,
> el día que esa persona no esté, los avisos huérfanos dejan de tener dueño sin
> que nada lo indique.

### Revisar también el resto de los canales

El mismo riesgo corre para cada dirección de área. Al 17/09/2026 hay dos que
conviene mirar antes de confiar en ellas:

| Variable | Valor | Observación |
|---|---|---|
| `NOTIFY_CS` | `custormerserv@…` | `custormerserv` parece un error de tipeo por `customerserv` |
| `NOTIFY_RRHH` | `rrhh.personal@…` | Casilla **personal**: deja de funcionar cuando esa persona se va |

Las direcciones personales conviene reemplazarlas por casillas compartidas del
área. El portal seguiría avisando aunque cambie la gente.

`tests/diagnostico-email.php` lista las siete direcciones configuradas en su
sección 5, para revisarlas de una sola mirada.

> **Cuidado al editar el `.env`:** el lector no admite comentarios al final de
> una línea. Todo lo que siga al `=` se toma como parte del valor, así que
> `NOTIFY_CS=casilla@dominio  # revisar` guarda el comentario dentro de la
> dirección y rompe el envío. Los comentarios van en su propia línea.

---

## 5. Dos cosas a tener en el calendario

### La autenticación básica de SMTP tiene fecha de vencimiento (solo ruta B)

Esto aplica únicamente si se eligió la **ruta B** (casilla de Microsoft). La
casilla del hosting no se ve afectada.

Microsoft la va a **deshabilitar por defecto a fines de diciembre de 2026** en
los tenants existentes (un administrador todavía podrá volver a habilitarla), y
en la segunda mitad de 2027 anunciará la fecha de eliminación definitiva. El
reemplazo es **OAuth 2.0**.

Cuando llegue el momento, la migración es natural para este proyecto: ya existe
un App Registration en Entra ID (el del portal). Se le agrega el permiso
`Mail.Send`, y PHPMailer se configura con `XOAUTH2` en lugar de usuario y
contraseña. Conviene hacerlo antes de que sea urgente.

### Visibilidad de DMARC

El DMARC actual es `v=DMARC1; p=quarantine`, sin dirección de reportes. Está
aplicando la política a ciegas: nadie se entera si algo legítimo se está yendo
a cuarentena. Agregar un `rua` da esa visibilidad sin cambiar el nivel de
exigencia:

```
v=DMARC1; p=quarantine; rua=mailto:dmarc@atlantic-agency.example.com; fo=1
```

Es el único cambio de DNS que vale la pena hacer, y es seguro: `rua` solo
agrega reportes, no altera cómo se evalúan los mensajes.
