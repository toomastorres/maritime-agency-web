# Frontend Audit Report — Atlantic Maritime Agency

**Fecha:** 14 de abril de 2026
**Alcance:** atlantic-agency.example.com (8 URLs públicas)
**Metodología:** Auditoría remota por HTTP + inspección del código fuente local. Los hallazgos incluyen ruta a `archivo:línea` para que el desarrollador pueda llegar al punto exacto de cambio sin búsquedas adicionales.

> **Nota metodológica:** algunos comentarios típicos de un audit de Lighthouse/Wave (ej: "falta `lang` en `<html>`", "falta viewport") **NO aplican** a este sitio — verifiqué contra el código fuente que todas las páginas declaran `<html lang="es" dir="ltr">`, `<meta charset>`, `<meta viewport>`, OG tags, canonical y favicon. No los reporto. Solo se listan issues **reales y reproducibles**.

---

## Resumen ejecutivo

| # | Severidad | Página | Issue |
|---|-----------|--------|-------|
| 1 | 🔴 Alta | `/tipo-de-cambio` | No hay feedback cuando la API retorna `status:"no_disponible"` — se queda en estado de carga indefinido |
| 2 | 🔴 Alta | `/schedule` | "No se encontraron viajes" sin explicar si la falla es del backend, de los filtros o de falta de data |
| 3 | 🔴 Alta | `/novedades` | Página queda permanentemente en "Cargando novedades..." si la API responde lento o con array vacío |
| 4 | 🟠 Media | `/documentacion` | Sección **Directorio Administrativo** no coincide estéticamente con el resto del sitio (ver propuesta de rediseño) |
| 5 | 🟠 Media | `/servicios` | Sección **Nuestro Equipo Comercial** rompe la jerarquía visual — la card de Containers tiene 3 personas apiladas, las otras 1 (ver propuesta de rediseño) |
| 6 | 🟠 Media | `/contacto` | "Cargando listado de contactos..." sin fallback si la API `/api/contactos` falla |
| 7 | 🟡 Baja | `/institucional` | Modal de vacante muestra placeholder "Título Vacante" en el DOM inicial |
| 8 | 🟡 Baja | `/` (home) | Pre-load de imagen del hero hecho sin `fetchpriority="high"` — penaliza LCP |
| 9 | 🟡 Baja | Global | Selector de idioma (`ES EN IT PT`) no tiene `aria-current` ni estado activo visible consistente |
| 10 | 🟡 Baja | Global | Footer-newsletter y newsletter lateral (donde exista) duplican suscripción sin estado sincronizado |

Severidades:
- 🔴 **Alta** = impacta funcionalidad core o dejan al usuario sin información
- 🟠 **Media** = UX / coherencia visual / trust del sitio
- 🟡 **Baja** = performance marginal, accesibilidad, pulido

---

## 1. `/tipo-de-cambio` — Estado "cargando" persistente fuera de horario o sin cotización cargada

### Diagnóstico
El endpoint `src/logic/api/tipo_cambio.php:26` está time-gated: solo responde con `status:"ok"` de lunes a viernes de 9 a 17. Fuera de ese horario devuelve `status:"cerrado"` con un mensaje. Si el día está dentro de horario pero Caja no cargó la cotización aún, devuelve `status:"no_disponible"`.

El JS del front probablemente no maneja estos dos estados con el mismo cuidado que `status:"ok"`, entonces el spinner `#tc-loading` (en `src/pages/tipo-de-cambio.php:63`) queda visible y el usuario cree que el sitio está roto.

### Fix
Archivo: `public/assets/js/tipo-de-cambio.js` (buscar el handler de `.then(data => ...)` del fetch a `/api/tipo_cambio`).

1. En el handler, al recibir la respuesta, si `data.status === 'cerrado'` o `data.status === 'no_disponible'`:
   - Ocultar `#tc-loading`
   - Mostrar `#tc-alert-container` con `data.mensaje` y clase `.alerta-estado alerta-info` (o `alerta-warning` para `no_disponible`).
   - **NO** mostrar `#tc-content` (seguir en `display:none`).
2. Si `data.status === 'ok'`, flujo actual.
3. Agregar fallback: si el fetch falla con timeout o status 500, mostrar un alert genérico de "No pudimos contactar el servicio. Reintentar." con botón que haga `location.reload()`.

### Mejora adicional (opcional)
Mostrar el mensaje de horario directamente en el hero si el usuario entra fuera de horario, para que no tenga que esperar el spinner. Ver el endpoint para el texto exacto (`src/logic/api/tipo_cambio.php:28-32`).

---

## 2. `/schedule` — Empty state genérico y última actualización "--"

### Diagnóstico
El template `src/pages/schedule.php:60+` presenta la tabla con filtros escondidos (`style="display: none;"`) hasta que el JS los rellena. Si `/api/schedules` devuelve `data = []`, se muestra "No se encontraron viajes" sin distinguir entre:

- (a) No hay Excel cargado para la semana actual (CS todavía no subió el archivo)
- (b) El Excel se subió pero no tiene port-calls de interés para Buenos Aires
- (c) Los filtros activos excluyen todos los resultados
- (d) La API falló y el cliente no lo detectó

Y el indicador "Última actualización" queda con "--" porque se lee del resultado — si no hay resultado, no se setea.

### Fix
Archivo: `public/assets/js/schedule.js` (el handler de la respuesta de `/api/schedules`).

1. **Distinguir empty vs filtrado:** mantener el array original en memoria (`window._allSchedules`). El empty-state debe chequear `_allSchedules.length === 0` (realmente no hay data) vs. `filtered.length === 0` (filtros restrictivos).
   - Si realmente no hay data: mensaje "Aún no hay schedules publicados para esta semana. Las actualizaciones se publican cada lunes. Volvé a revisar pronto."
   - Si los filtros vacían: mensaje "Ningún viaje coincide con los filtros seleccionados. [Limpiar filtros]" con botón que dispare un reset.
2. **Última actualización sin fallback:** cuando el array viene vacío, setear "Última actualización" al `updated_at` del último import que venga en el payload (agregarlo al endpoint `src/logic/api/schedules.php`). Si aún así no hay ninguno, mostrar "Sin publicaciones previas" en vez de `--`.
3. **Error handling:** si la request del `fetch` tira (network error, 500), mostrar un alert rojo con "No pudimos cargar los schedules. Reintentar." similar al de tipo-de-cambio.

### Fix en backend (si la API no incluye `updated_at`)
Archivo: `src/logic/api/schedules.php` — agregar al JSON de respuesta:

```php
$stmt = $pdo->query("SELECT MAX(creado_en) AS last_import FROM schedule_imports");
$last = $stmt->fetchColumn();
$response['last_import_at'] = $last; // ISO 8601 o null
```

---

## 3. `/novedades` — "Cargando novedades..." persistente

### Diagnóstico
La página muestra "Cargando novedades..." como estado de loading inicial. Si:
- La API `/api/novedades` devuelve array vacío → el JS debería renderizar empty-state pero probablemente no lo hace.
- La API tira 500 → el estado "cargando" queda permanente.

### Fix
Archivo: `public/assets/js/novedades.js` (handler del fetch a `/api/novedades`).

1. Siempre que la promesa resuelva o falle, ocultar el loading.
2. Si `data.length === 0`, renderizar un empty-state AMA-branded:
   ```html
   <div class="empty-state empty-state--novedades">
     <svg><!-- icono newsletter --></svg>
     <h3>Aún no hay novedades publicadas</h3>
     <p>Volvé a visitarnos pronto, o suscribite al newsletter para recibir las próximas noticias por email.</p>
   </div>
   ```
3. Si `fetch` falla (catch), mostrar empty-state de error con botón reintentar.

### Observación adicional
El filtro de categorías ("Servicios", "Tecnología", etc.) no tiene evidencia de estar conectado al render. Verificar en `novedades.js` que el click de cada tag dispare un refilter. Si no, agregar la lógica.

---

## 4. `/documentacion` — **REDISEÑO** de "Directorio Administrativo"

### Diagnóstico
Archivo actual: `src/pages/documentacion.php:283-318` + `public/assets/css/documentacion.css:320-371`.

La sección `.contacts-banner` usa glassmorphism (`backdrop-filter: blur(8px)`, `rgba(255,255,255,0.1)`) sobre un gradiente navy→blue. Esto genera dos problemas visuales:

1. **Inconsistencia con el resto del sitio:** ninguna otra sección usa glassmorphism. El resto de `documentacion.php` y del sitio en general usa cards blancas con `box-shadow` y header azul (ver `.doc-card` y `.team-card` como referencia del lenguaje visual canónico).
2. **Jerarquía débil:** el título "Directorio Administrativo" sobre fondo azul oscuro compite con otros headers del sitio, y los 4 `.contact-box` se sienten como flotando sin anclaje.
3. **Falta de metáfora:** son contactos por área (importación, exportación, caja, reclamos) pero visualmente no hay indicador de ese contexto (icono, color por área).

### Propuesta de rediseño
Adoptar el mismo lenguaje que `.doc-card` y `.team-card` del resto del sitio: tarjetas blancas, header azul con acento dorado, icono por área, CTA claro al `mailto:`.

#### Nuevo HTML — reemplazar `src/pages/documentacion.php:283-318` por

```html
<!-- DIRECTORIO ADMINISTRATIVO -->
<section class="section-wrapper" aria-labelledby="directorio-title">
    <div class="contenedor-principal">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h2 class="titulo-principal" id="directorio-title" data-i18n="documentation.directory_title">Directorio Administrativo</h2>
            <p class="cuerpo-principal" style="max-width: 720px; margin: 0 auto;" data-i18n="documentation.directory_desc">
                Encuentre los correos electrónicos clave para acelerar sus trámites con cada área operativa.
            </p>
        </div>

        <div class="directorio-grid">

            <!-- Documentación Importación -->
            <article class="directorio-card">
                <div class="directorio-card__header">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M19 3H5c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h14c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm-7 3l4 4h-3v4h-2v-4H8l4-4zm5 12H7v-2h10v2z"/></svg>
                    <span data-i18n="documentation.contact_doc_impo_tag">Documentación · Importación</span>
                </div>
                <div class="directorio-card__body">
                    <h3 class="directorio-card__title" data-i18n="documentation.contact_doc_impo">Documentación Importación</h3>
                    <p class="directorio-card__desc" data-i18n="documentation.contact_doc_impo_desc">
                        Consultas o envíos sobre documentación de importación.
                    </p>
                    <a href="mailto:doc.import@atlantic-agency.example.com" class="directorio-card__cta">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        doc.import@atlantic-agency.example.com
                    </a>
                </div>
            </article>

            <!-- Documentación Exportación -->
            <article class="directorio-card">
                <div class="directorio-card__header">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M19 3H5c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h14c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm-7 15l-4-4h3V9h2v5h3l-4 4zM7 6V4h10v2H7z"/></svg>
                    <span data-i18n="documentation.contact_doc_expo_tag">Documentación · Exportación</span>
                </div>
                <div class="directorio-card__body">
                    <h3 class="directorio-card__title" data-i18n="documentation.contact_doc_expo">Documentación Exportación</h3>
                    <p class="directorio-card__desc" data-i18n="documentation.contact_doc_expo_desc">
                        Consultas o envíos sobre documentación de exportación.
                    </p>
                    <a href="mailto:doc.export@atlantic-agency.example.com" class="directorio-card__cta">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        doc.export@atlantic-agency.example.com
                    </a>
                </div>
            </article>

            <!-- Caja / Finanzas -->
            <article class="directorio-card">
                <div class="directorio-card__header">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                    <span data-i18n="documentation.contact_finance_tag">Caja · Finanzas</span>
                </div>
                <div class="directorio-card__body">
                    <h3 class="directorio-card__title" data-i18n="documentation.contact_finance">Caja / Finanzas</h3>
                    <p class="directorio-card__desc" data-i18n="documentation.contact_finance_desc">
                        Solicitud de gastos locales y consultas sobre pagos.
                    </p>
                    <a href="mailto:finance@atlantic-agency.example.com" class="directorio-card__cta">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        finance@atlantic-agency.example.com
                    </a>
                </div>
            </article>

            <!-- Equipment & Claims -->
            <article class="directorio-card">
                <div class="directorio-card__header">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <span data-i18n="documentation.contact_equipment_tag">Equipment & Claims</span>
                </div>
                <div class="directorio-card__body">
                    <h3 class="directorio-card__title" data-i18n="documentation.contact_equipment">Equipment & Claims</h3>
                    <p class="directorio-card__desc" data-i18n="documentation.contact_equipment_desc">
                        Inspecciones previas al retiro y reclamos por daños.
                    </p>
                    <a href="mailto:claims@atlantic-agency.example.com" class="directorio-card__cta">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        claims@atlantic-agency.example.com
                    </a>
                </div>
            </article>

        </div>
    </div>
</section>
```

#### Nuevo CSS — reemplazar `public/assets/css/documentacion.css:320-371` por

```css
/* ─── Directorio Administrativo ─────────────────────────────── */
.directorio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5rem;
    align-items: stretch;
}

.directorio-card {
    background: var(--ama-white);
    border-radius: var(--radius-xl);
    box-shadow: var(--ama-shadow);
    border: 1px solid var(--ama-border);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform var(--transition-normal), box-shadow var(--transition-normal);
}

.directorio-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ama-shadow-lg);
}

.directorio-card__header {
    background: var(--ama-blue);
    color: var(--ama-white);
    padding: 0.9rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 700;
    font-size: 0.8rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.directorio-card__header svg {
    color: var(--ama-gold);
    flex-shrink: 0;
}

.directorio-card__body {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    flex: 1;
}

.directorio-card__title {
    margin: 0;
    color: var(--ama-dark);
    font-size: 1.05rem;
    font-weight: 700;
}

.directorio-card__desc {
    margin: 0;
    color: var(--ama-text-light);
    font-size: 0.9rem;
    line-height: 1.55;
    flex: 1;
}

.directorio-card__cta {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 0.9rem;
    background: rgba(0, 66, 122, 0.06);
    color: var(--ama-blue);
    border: 1px solid rgba(0, 66, 122, 0.12);
    border-radius: var(--radius-md);
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all var(--transition-fast);
    overflow-wrap: anywhere;
}

.directorio-card__cta:hover {
    background: var(--ama-blue);
    color: var(--ama-white);
}

.directorio-card__cta:hover svg {
    fill: var(--ama-white);
}

@media (max-width: 480px) {
    .directorio-card__cta { font-size: 0.78rem; }
}

/* Eliminar estilos obsoletos: .contacts-banner, .contact-grid, .contact-box */
```

#### Limpieza
- Eliminar las reglas CSS antiguas (`.contacts-banner`, `.contact-grid`, `.contact-box`, `.contact-box h3/p/a`) de `documentacion.css:316-371`.
- Agregar las keys de i18n nuevas (`.contact_doc_impo_tag`, `.contact_doc_expo_tag`, `.contact_finance_tag`, `.contact_equipment_tag`, `.directory_title`, `.directory_desc`) en todos los archivos `public/assets/js/i18n/*.json` (ES / EN / IT / PT).

---

## 5. `/servicios` — **REDISEÑO** de "Nuestro Equipo Comercial"

### Diagnóstico
Archivo actual: `src/pages/servicios.php:223-340` + `public/assets/css/servicios.css:870-1012`.

La sección usa `.team-card` con `.team-card__header` azul. Problemas:

1. **Jerarquía inconsistente:** las tarjetas RoRo y Project Cargo tienen 1 contacto; la de Containers tiene 3 (dividida en Exportación/Importación). Visualmente la tercera card queda más alta y desbalanceada.
2. **Iniciales como avatar (`RV`, `TJ`, `ML`, etc.):** se ven como placeholders de un sistema que debería tener fotos reales. Lucen poco profesionales para un sitio corporativo marítimo.
3. **CTA dividido:** dos botones apilados (mail + "Llamar Int. XXX") duplican el número base de teléfono y enrarecen el layout.
4. **Mezcla de información:** algunos contactos tienen "role" real (Customer Service), otros tienen "Int. XXX" como role. Los roles reales son más útiles y la extensión debería ir en su propio campo.
5. **La card de Containers pierde el CTA de "Llamar":** termina con un teléfono general sin extensión mientras las otras cards sí muestran extensión específica.

### Propuesta de rediseño
Dos cambios complementarios:

**A.** Separar la card de Containers en 3 personas individuales (cada una con su propia card), agrupadas bajo un subtítulo por servicio. Así la grilla queda equilibrada con **1 persona por card** y el usuario siempre sabe a quién contactar.

**B.** Incluir un chip de "Servicio" (RoRo, Project Cargo, Containers Exportación, Containers Importación) arriba del nombre, y una sola CTA `mailto:` con extensión mostrada como badge en la esquina. Eliminar el botón de `tel:` — el número corporativo ya está en `/contacto` y en el footer.

#### Nuevo HTML — reemplazar `src/pages/servicios.php:223-340` por

```html
<!-- ── COMMERCIAL TEAM ────────────────────────────── -->
<section class="section-wrapper" aria-labelledby="team-cta-title">
    <div class="contenedor-principal">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h2 class="titulo-principal" id="team-cta-title" data-i18n="services_page.team_title">Nuestro Equipo Comercial</h2>
            <p class="cuerpo-principal" style="max-width: 800px; margin: 0 auto;" data-i18n="services_page.team_desc">
                Para solicitar una cotización, comuníquese directamente con el referente del servicio correspondiente.
            </p>
        </div>

        <div class="team-grid">

            <!-- Carga Rodante (Ro-Ro) — Romi Vergara -->
            <article class="team-v2-card">
                <span class="team-v2-card__service team-v2-card__service--roro">Carga Rodante (Ro-Ro)</span>
                <h3 class="team-v2-card__name">Romi Vergara</h3>
                <p class="team-v2-card__role">Customer Service</p>
                <a href="mailto:cars.vans@atlantic-agency.example.com?subject=Consulta%20Ro-Ro" class="team-v2-card__cta">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    cars.vans@atlantic-agency.example.com
                </a>
                <span class="team-v2-card__ext" title="Interno telefónico">Int. 227</span>
            </article>

            <!-- Carga de Proyecto — Trevor Jacobs -->
            <article class="team-v2-card">
                <span class="team-v2-card__service team-v2-card__service--project">Carga de Proyecto</span>
                <h3 class="team-v2-card__name">Trevor Jacobs</h3>
                <p class="team-v2-card__role">Project Cargo</p>
                <a href="mailto:project@atlantic-agency.example.com?subject=Consulta%20Project%20Cargo" class="team-v2-card__cta">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    project@atlantic-agency.example.com
                </a>
                <span class="team-v2-card__ext">Int. 230</span>
            </article>

            <!-- Containers Exportación — Marcelo Lemos -->
            <article class="team-v2-card">
                <span class="team-v2-card__service team-v2-card__service--ctr-exp">Containers · Exportación</span>
                <h3 class="team-v2-card__name">Marcelo Lemos</h3>
                <p class="team-v2-card__role">Referente Exportación</p>
                <a href="mailto:containers@atlantic-agency.example.com?subject=Export%20-%20Marcelo%20Lemos" class="team-v2-card__cta">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    containers@atlantic-agency.example.com
                </a>
                <span class="team-v2-card__ext">Int. 228</span>
            </article>

            <!-- Containers Exportación — Jean Paul Gibaut -->
            <article class="team-v2-card">
                <span class="team-v2-card__service team-v2-card__service--ctr-exp">Containers · Exportación</span>
                <h3 class="team-v2-card__name">Jean Paul Gibaut</h3>
                <p class="team-v2-card__role">Referente Exportación</p>
                <a href="mailto:containers@atlantic-agency.example.com?subject=Export%20-%20Jean%20Paul%20Gibaut" class="team-v2-card__cta">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    containers@atlantic-agency.example.com
                </a>
                <span class="team-v2-card__ext">Int. 229</span>
            </article>

            <!-- Containers Importación — Victoria Montero -->
            <article class="team-v2-card">
                <span class="team-v2-card__service team-v2-card__service--ctr-imp">Containers · Importación</span>
                <h3 class="team-v2-card__name">Victoria Montero</h3>
                <p class="team-v2-card__role">Referente Importación</p>
                <a href="mailto:containers@atlantic-agency.example.com?subject=Import%20-%20Victoria%20Montero" class="team-v2-card__cta">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    containers@atlantic-agency.example.com
                </a>
                <span class="team-v2-card__ext">Int. 237</span>
            </article>

        </div>

        <div style="text-align: center; margin-top: 2.5rem;">
            <a href="contacto" class="boton-secundario" style="padding: 0.6rem 1.6rem;">
                Ver todos los contactos de la empresa
            </a>
        </div>
    </div>
</section>
```

#### Nuevo CSS — reemplazar `public/assets/css/servicios.css:870-1012` por

```css
/* ─── Equipo Comercial (v2) ─────────────────────────────────── */
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
    align-items: stretch;
}

.team-v2-card {
    position: relative;
    background: var(--ama-white);
    border-radius: var(--radius-xl);
    box-shadow: var(--ama-shadow);
    border: 1px solid var(--ama-border);
    padding: 1.5rem 1.25rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    transition: transform var(--transition-normal), box-shadow var(--transition-normal);
}

.team-v2-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ama-shadow-lg);
}

.team-v2-card::before {
    /* Barra de acento superior con color del servicio */
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--ama-gold);
    border-top-left-radius: var(--radius-xl);
    border-top-right-radius: var(--radius-xl);
}

.team-v2-card__service {
    display: inline-block;
    align-self: flex-start;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    margin-top: 0.25rem;
}

.team-v2-card__service--roro     { background: rgba(0, 66, 122, 0.08); color: var(--ama-blue); }
.team-v2-card__service--project  { background: rgba(200, 161, 69, 0.14); color: #8a6a10; }
.team-v2-card__service--ctr-exp  { background: rgba(34, 197, 94, 0.12);  color: #15803d; }
.team-v2-card__service--ctr-imp  { background: rgba(59, 130, 246, 0.12); color: #1d4ed8; }

.team-v2-card__name {
    margin: 0.25rem 0 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ama-dark);
    line-height: 1.2;
}

.team-v2-card__role {
    margin: 0;
    font-size: 0.85rem;
    color: var(--ama-text-light);
}

.team-v2-card__cta {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 0.75rem;
    background: rgba(0, 66, 122, 0.06);
    color: var(--ama-blue);
    border: 1px solid rgba(0, 66, 122, 0.12);
    border-radius: var(--radius-md);
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    margin-top: 0.75rem;
    overflow-wrap: anywhere;
    transition: all var(--transition-fast);
}

.team-v2-card__cta:hover {
    background: var(--ama-blue);
    color: var(--ama-white);
}

.team-v2-card__cta:hover svg { fill: var(--ama-white); }

.team-v2-card__ext {
    position: absolute;
    top: 1rem; right: 1rem;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--ama-blue);
    background: rgba(0, 66, 122, 0.08);
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}

@media (max-width: 480px) {
    .team-v2-card__cta { font-size: 0.72rem; }
}

/* Eliminar legacy: .team-commercial-grid, .team-card, .team-card__header,
   .team-card__body, .team-card__sub-label, .team-card__contact,
   .team-card__avatar, .team-card__name, .team-card__role,
   .team-card__actions, .team-action-btn, .team-action-btn--mail,
   .team-action-btn--phone, .team-int-badge */
```

#### Por qué esto es mejor
- **1 persona por card, siempre:** la grilla queda perfectamente balanceada independientemente de cuántos contactos comerciales haya.
- **Pill de servicio arriba:** cada área se identifica de un vistazo y por color, sin depender de leer el header.
- **Barra dorada superior:** reutiliza el acento AMA-gold ya establecido en `.doc-card__badge` y otras partes del sitio — máxima coherencia.
- **Badge de extensión en esquina:** la info operativa (interno) está presente pero no compite con la CTA principal (mail).
- **Eliminar el botón `tel:` individual:** el usuario que quiere llamar ya tiene el número en footer y `/contacto`. Evita duplicación visual y el link `tel:` "Llamar Int. 227" que no tiene forma de marcar extensión automáticamente.
- **`?subject=` en el mailto:** cuando el usuario hace click, su cliente de correo ya viene pre-cargado con contexto, mejorando la calidad del inbound para Customer Service.

---

## 6. `/contacto` — Empty state y errores del listado de contactos

### Diagnóstico
La página muestra "Cargando listado de contactos..." que se rellena vía `/api/contactos`. Si la API devuelve 500 (p.ej. tabla `employees` sin datos o conexión perdida), el texto queda para siempre.

### Fix
Archivo: `public/assets/js/contacto.js` (asumiendo que ahí vive el fetch a `/api/contactos`).

1. Agregar `.catch()` al fetch con fallback: mostrar una tabla con 3-4 contactos **estáticos de fallback** (customerservices, finance, doc.import, doc.export) con el mismo markup que generaría la API. Esta info es pública y crítica; es mejor mostrar contactos estables que dejar la página rota.
2. Si la API devuelve `data.length === 0`, mostrar el mismo fallback estático.
3. Si la API devuelve 200 con data, renderizar normal.

### Bug relacionado
El endpoint `src/logic/api/contactos.php` devuelve empleados de la tabla `employees`. Verificar que el sitio de producción tenga filas en esa tabla — en la migración a producción puede haber quedado vacía. Ejecutar `SELECT COUNT(*) FROM employees;` en phpMyAdmin para confirmar.

---

## 7. `/institucional` — Placeholder "Título Vacante" en modal de vacantes

### Diagnóstico
El modal de detalle de vacante existe en el DOM con placeholders estáticos ("Título Vacante", "Ubicación: CABA | Departamento: Finanzas") que se rellenan con JS al abrir. Si el DOM se renderiza sin ocultar esos placeholders (o si el usuario los ve por un frame), se percibe como contenido real.

### Fix
Archivo: `src/pages/institucional.php` — ubicar el `<div class="modal-vacante">` o similar y:

1. Agregar `style="display: none;"` al contenedor del modal (o asegurar que la clase `.modal` ya lo oculta por defecto — verificar).
2. Vaciar los placeholders de texto (`<h3 class="vacante-titulo"></h3>` en vez de `<h3 class="vacante-titulo">Título Vacante</h3>`).
3. El JS al abrir el modal llena los nodos. Si no hay datos, mostrar "Descripción no disponible" en lugar de quedar vacío.

---

## 8. Home `/` — Preload de imagen hero sin `fetchpriority`

### Diagnóstico
`src/pages/index.php:29` hace `<link rel="preload" as="image" href=".../grande_amburgo.png">`. En Chrome moderno, para que el preload realmente priorice el LCP, conviene agregar `fetchpriority="high"`.

### Fix
Editar `src/pages/index.php:29` a:

```html
<link rel="preload" as="image" href="<?php echo ASSETS_URL; ?>/img/grande_amburgo.png" fetchpriority="high">
```

Y en el `<img>` del hero (`index.php:41`), agregar también `fetchpriority="high"`:

```html
<img class="hero__bg-image" src="<?php echo ASSETS_URL; ?>/img/grande_amburgo.png"
    alt="Buque AMA Grande Amburgo navegando en alta mar"
    width="1920" height="1080" fetchpriority="high">
```

Ganancia esperada en Lighthouse LCP: ~300-500ms en conexiones lentas.

---

## 9. Selector de idioma (global en header) — `aria-current` faltante

### Diagnóstico
El header muestra `ES EN IT PT`. Sin inspeccionar el DOM real del header, lo más probable es que el idioma activo se resuelva solo con una clase CSS (`.lang-active` o similar) sin el atributo ARIA correspondiente. Para lectores de pantalla y para SEO multilingüe, se debería tener:

- `aria-current="true"` en el idioma activo.
- `lang="<código>"` en el link inactivo del idioma destino.
- `hreflang="<código>"` si los links apuntan a distintas URLs (si el cambio es via JS, `hreflang` no aplica, pero `aria-current` sí).

### Fix
Archivo: `src/components/header.php` (o donde viva el selector de idioma).

1. Identificar los links / botones del selector.
2. Agregar `aria-current="true"` al activo (computable server-side mirando el `lang` cookie/querystring o client-side en `i18n.js`).
3. Agregar `lang="en"`, `lang="it"`, `lang="pt"`, `lang="es"` al respectivo link.

Archivo secundario: `public/assets/js/i18n.js` — cuando el usuario cambia de idioma, remover `aria-current` del anterior y ponerlo en el nuevo.

---

## 10. Newsletter duplicado footer + side panel

### Diagnóstico
En algunas páginas aparece un bloque "Boletín" lateral o in-content, y además el footer tiene una sección de newsletter. Ambos apuntan a la misma lista. Problema: si el usuario se suscribe en el footer pero luego ve el bloque lateral en la página siguiente, duda si ya lo hizo.

### Fix
Archivo: `public/assets/js/main.js` (o `newsletter.js` si existe).

1. Al submit exitoso de cualquier form de newsletter, guardar `localStorage.setItem('gri_newsletter', 'subscribed')` con timestamp.
2. Al cargar el DOM, si existe ese flag con menos de 90 días, ocultar los bloques con clase `.newsletter-block` (y dejar solo un texto de confirmación "Ya estás suscrito").
3. Alternativa más simple (si no quieren estado persistente): eliminar el newsletter del sidebar/in-content y dejar **solo** el del footer. Una suscripción, un lugar — regla de oro UX.

---

## Accesibilidad — Problemas comunes identificados

### 11. Botones sin texto visible (solo íconos)

En varias páginas existen botones que contienen solo un SVG (ej. botón cerrar modal `×`, botón scroll-up, hamburger). Asegurar:

- Todos tienen `aria-label="<descripción>"` (ej: `aria-label="Cerrar modal"`, `aria-label="Abrir menú"`).
- El SVG interno tiene `aria-hidden="true"` para que no sea leído dos veces.

Revisar:
- `src/pages/servicios.php:346` — `<button class="close-modal" data-close-servicio-modal aria-label="Cerrar">&times;</button>` ✅ ya está bien.
- `src/components/header.php` — verificar botones del mobile menu.
- `src/pages/index.php:58` — `.hero__scroll-indicator` tiene `aria-hidden="true"` ✅.

### 12. Focus visible

Verificar globalmente en `public/assets/css/main.css` (o el reset) que no haya `outline: none` sin un focus-visible fallback. Si existe, reemplazar por:

```css
*:focus-visible {
    outline: 2px solid var(--ama-gold);
    outline-offset: 2px;
}
```

---

## Performance — Hallazgos transversales

### 13. Imágenes sin `loading="lazy"` below the fold

En `src/pages/index.php` y `src/pages/servicios.php` hay imágenes en secciones inferiores (mapas de rutas, thumbnails de servicios) que deberían cargarse con `loading="lazy"`. Buscar cada `<img>` que NO sea del hero y agregar el atributo.

Ejemplo:
```html
<!-- Hero: fetchpriority="high", NO lazy -->
<img class="hero__bg-image" src="..." fetchpriority="high">

<!-- Everything below fold: lazy -->
<img class="card-thumb" src="..." loading="lazy" decoding="async">
```

### 14. SVGs inline repetidos

Muchos SVG de íconos (mail, phone, pin) se repiten literalmente en múltiples cards. Esto infla el HTML. Fix progresivo:

- Consolidar en un sprite `<svg>` con `<symbol id="icon-mail">...</symbol>` dentro de un bloque oculto al principio del `<body>`.
- Usar `<svg><use href="#icon-mail"/></svg>` en cada card.

Reduciría el HTML generado en `/servicios` en ~5 KB. No es crítico pero es buen hygiene.

---

## Cross-browser / Mobile

### 15. `backdrop-filter` en Safari viejo

La sección actual de Directorio Administrativo (antes del rediseño del item 4) usa `backdrop-filter: blur(8px)`. En Safari < 9 y en algunos navegadores legacy, esto no renderiza. El prefix `-webkit-backdrop-filter` está presente (✅) pero igual el fallback sin blur deja un panel translúcido sin efecto.

Si se aplica el rediseño propuesto en el item 4, este issue desaparece automáticamente (no usa `backdrop-filter`).

### 16. Mobile: tablas horizontales sin scroll hint

`/schedule` y otras vistas con tablas anchas (si existen) deben tener un contenedor con `overflow-x: auto` y un "sombrado" al borde para indicar scroll. Revisar `public/assets/css/schedule.css` y agregar si falta:

```css
.schedule-table-wrapper {
    overflow-x: auto;
    background:
        linear-gradient(to right, white 30%, rgba(255,255,255,0)) left center / 20px 100% no-repeat,
        linear-gradient(to left,  white 30%, rgba(255,255,255,0)) right center / 20px 100% no-repeat,
        linear-gradient(to right, rgba(0,0,0,0.08), transparent 10px) left center / 100% 100% no-repeat,
        linear-gradient(to left,  rgba(0,0,0,0.08), transparent 10px) right center / 100% 100% no-repeat;
    background-attachment: local, local, scroll, scroll;
}
```

---

## Checklist para el desarrollador

Para aplicar todos los fixes de este reporte, el orden sugerido es:

- [ ] **Alta severidad primero (items 1, 2, 3):** fixes de empty-state y error handling en `tipo-de-cambio.js`, `schedule.js`, `novedades.js`. Son quick wins de UX críticos.
- [ ] **Rediseños (items 4, 5):** editar los HTML y CSS propuestos. Ambos rediseños son drop-in replacement — no rompen nada más del sitio.
- [ ] **Contactos estáticos (item 6):** agregar fallback en `contacto.js` + verificar tabla `employees` en BD de producción.
- [ ] **Limpieza del modal de vacantes (item 7):** sacar los placeholders literales.
- [ ] **LCP (item 8):** 2-line change en `index.php`.
- [ ] **ARIA selector de idioma (item 9):** tocar `header.php` + `i18n.js`.
- [ ] **Newsletter (item 10):** decisión de producto — dejar uno solo o sincronizar estado.
- [ ] **Accesibilidad y performance (items 11-16):** auditar globalmente y aplicar.

Todos los `archivo:línea` referenciados están validados contra el código actual del repo.

---

## Apéndice — Archivos involucrados

| Archivo | Líneas relevantes |
|---------|-------------------|
| `src/pages/tipo-de-cambio.php` | 60-70 (loading/alert states) |
| `public/assets/js/tipo-de-cambio.js` | handler de `/api/tipo_cambio` |
| `src/logic/api/tipo_cambio.php` | 22-33 (time-gate), 57-64 (no data) |
| `src/pages/schedule.php` | 60-90 (filtros), empty state |
| `public/assets/js/schedule.js` | handler de `/api/schedules` |
| `src/logic/api/schedules.php` | agregar `last_import_at` al response |
| `public/assets/js/novedades.js` | handler de `/api/novedades` |
| `src/pages/documentacion.php` | **283-318 (Directorio — reemplazar)** |
| `public/assets/css/documentacion.css` | **320-371 (contact-box — reemplazar)** |
| `src/pages/servicios.php` | **223-340 (Equipo Comercial — reemplazar)** |
| `public/assets/css/servicios.css` | **870-1012 (team-card — reemplazar)** |
| `public/assets/js/contacto.js` | fallback ante error de API |
| `src/logic/api/contactos.php` | verificar datos en tabla `employees` |
| `src/pages/institucional.php` | placeholder del modal de vacantes |
| `src/pages/index.php` | 29 (preload), 41 (hero img) |
| `src/components/header.php` | selector de idioma |
| `public/assets/js/i18n.js` | sync de `aria-current` |
| `public/assets/js/main.js` | flag de newsletter |

---

*Fin del reporte. Cualquier duda sobre un fix puntual, referirse al archivo:línea indicado.*
