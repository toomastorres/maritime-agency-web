# CONTRIBUTING: Reglas y Estándares de AMA Web Project

**Versión:** 3.0 (Actualizado — Marzo 2026)

Bienvenido al repositorio maestro. Este proyecto debe mantenerse bajo estrictos lineamientos corporativos para garantizar escalabilidad, seguridad y profesionalismo. Todos los desarrolladores o arquitectos de IA que intervengan deben seguir las siguientes normas.

---

## 1. Reglas de Oro de Arquitectura

1. **No Inventar Datos:** Si el contenido no se encuentra en `legacy_source/` o `mockup/`, solicitar al usuario real. No se admiten "Lorem Ipsum" en la build final.
2. **Entorno Híbrido Estricto:** Nunca crear scripts PHP que asuman que la BD es local en entornos no locales. Siempre usar el Bridge localmente configurado en `.env`.
3. **Uso de `.env`**: Las credenciales como contraseñas SQL, URLs base u ocultas se definen bajo un archivo oculto, nunca directo en el repositorio.
4. **Respeto Visual**: Consultar la paleta de colores en `variables.css` (e.g. `var(--AMA-blue)`) para toda referencia de color. Nunca usar valores hex hardcodeados.
5. **Portabilidad en Hosting Compartido**: El server asume Apache. Mantén el `.htaccess` optimizado sin funciones raras que rompan un hosting compartido (el hosting/el hosting).

---

## 2. Estándares de Código y Nombres

### PHP
- Convención PSR-12 obligatoria.
- Toda referencia de asset usa constantes PHP: `ASSETS_URL`, `CSS_URL`, `JS_URL`, `BASE_URL`.
- **Separación PHP/HTML:** la lógica de negocio va arriba del `<!DOCTYPE html>`, el renderizado abajo.
- Nunca usar inline `style=""` para colores o tipografía — siempre mover a CSS.

### CSS
- Todos los colores: `var(--AMA-*)` desde `variables.css`.
- Todos los espaciados: `var(--space-*)` desde `variables.css`.
- Todos los transitions: `var(--transition-fast/normal/slow)`.
- **No duplicar selectores** entre archivos. El componente vive en `components.css`; las páginas solo sobreescriben lo necesario.
- Cada elemento interactivo requiere `:hover`, `:focus-visible`, y `:active` (cuando aplique).
- Breakpoints estándar: `480px` (mobile-sm), `768px` (tablet), `1024px` (desktop).

### JavaScript
- ES6+ estricto, Vanilla JS sin jQuery ni librerías innecesarias.
- **No inline `<script>` en templates PHP** — todo JS va en `public/assets/js/`.
- **No `onclick="..."` en HTML** — usar `addEventListener` en archivos externos.
- **Null-guards obligatorios:** todo resultado de `querySelector()` debe verificarse antes de usarse.
- **No `innerHTML` con datos de usuario o BD** salvo que sean HTML explícitamente formateado desde una API interna de confianza (documentar con comentario).
- **No funciones deprecadas:** `escape()`, `unescape()` → usar `encodeURIComponent()` / `decodeURIComponent()`.
- Llamadas a `AMAI18n.updateDOM()` (no `window.i18nManager`) tras inyectar contenido dinámico.

### Nomenclatura
- IDs en HTML: `kebab-case` (`AMA-header`, `vacantes-container`).
- Variables/funciones en JS: `camelCase`.
- Clases CSS: `kebab-case` (`.card-AMA`, `.boton-primario`).
- Constantes PHP: `SCREAMING_SNAKE_CASE`.

---

## 3. Seguridad Frontend (Production-Ready Checklist)

Antes de hacer merge a `main`, verificar:

- [ ] No hay inline styles con valores de color hardcodeados.
- [ ] No hay bloques `<script>` inline en páginas PHP.
- [ ] No hay referencias a `window.i18nManager` (incorrecto) — usar `AMAI18n`.
- [ ] Todos los `querySelector()` tienen null-guard.
- [ ] Los datos de usuario/API se renderizan con `textContent`, no `innerHTML`.
- [ ] Los formularios tienen estados visuales: `:focus`, error (`.input-error`), success.
- [ ] Los elementos interactivos tienen `:focus-visible` para accesibilidad de teclado.
- [ ] Los números de teléfono son `<a href="tel:...">` links.
- [ ] Los links externos tienen `rel="noopener noreferrer"`.

---

## 4. Workflow de Git

1. `main` es sagrada: Representa el Live/Production actual en el hosting.
2. Todo el trabajo de desarrollo se centra en la rama `develop`.
3. Los commits deben seguir el formato **Angular Commit Style**:
   - `feat:` — nueva funcionalidad
   - `fix:` — corrección de bug
   - `style:` — cambio de CSS/estilo sin impacto lógico
   - `refactor:` — reorganización de código sin cambio de comportamiento
   - `docs:` — actualización de documentación
4. Sube contenido estático, nunca la base de datos completa.

---

## 5. Portal Interno de Agencia (`private/gestion-interna-agencia/`)

- Usa `admin-portal.css` con el namespace de tokens `--admin-*`.
- Las vistas (`views/*.php`) son validadas contra el whitelist de permisos antes de ser incluidas.
- El botón "Cerrar Sesión" es siempre visible en el sidebar, independientemente del rol.
- Cualquier dato sensible mostrado en las vistas debe pasar por `htmlspecialchars()`.

---

El seguimiento de este documento es fundamental para la correcta y segura vida de la plataforma digital.
