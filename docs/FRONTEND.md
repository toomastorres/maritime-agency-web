# Frontend Architecture — Maritime Agency Web

**Versión:** 3.0 (Auditoría Completa — Marzo 2026)

## Routing

All page requests go through the **Front Controller** (`index.php`):
- Clean URLs: `atlantic-agency.example.com/servicios` → `src/pages/servicios.php`
- API routes: `atlantic-agency.example.com/api/tipo_cambio` → `src/logic/api/tipo_cambio.php`
- 404 fallback: `404.php`

---

## CSS Design System (`public/assets/css/`)

Modular CSS loaded via PHP constants (`CSS_URL`). **All color values must reference design tokens from `variables.css`** — never hardcode hex or rgba values.

| File | Scope | Notes |
|------|-------|-------|
| `variables.css` | Design tokens: colors, spacing, typography, shadows | Source of truth for all visual values |
| `base.css` | Reset, typography, global element styles | |
| `layout.css` | Grid layouts, containers, responsive utilities | |
| `components.css` | Cards, buttons, alerts, forms, tables | Canonical home for reusable component styles |
| `header.css` | Navigation header + mobile toggle | |
| `footer.css` | Footer grid + contact items | |
| `hero.css` | Hero banner sections (home + inner pages) | Loaded globally via `header.php` |
| `index.css` | **Page-specific** — Stats, FAQs, news section | Loaded only on home page |
| `contacto.css` | Contact directory cards and sector filter | |
| `documentacion.css` | Document cards and filter buttons | |
| `institucional.css` | Mission/vision grid, vacancy cards, modal, timeline, team grid | |
| `novedades.css` | News listing, category filter, sidebar widgets | |
| `novedad.css` | Individual news article detail page | |
| `schedule.css` | Schedule table and controls | |
| `servicios.css` | Route explorer, service cards, ports grid, map section | |
| `tipo-de-cambio.css` | Exchange rate display, bank card, `.tc-bank-note`, `.tc-last-update` | |
| `404.css` | Error pages (shared with 500.php) | Uses `variables.css` — no local `:root` |
| `admin-login.css` | Admin portal login page | |
| `admin-portal.css` | Admin portal dashboard (sidebar, header, views) | Uses `--admin-*` token namespace |
| `admin-master.css` | Admin master layout styles | |

### CSS Rules

1. **No local `:root` overrides** — never redefine brand colors in page-specific files.
2. **No hardcoded hex/rgba** — use `var(--ama-*)` tokens for all colors.
3. **Spacing via scale** — use `var(--space-xs/sm/md/lg/xl/2xl/3xl/4xl)`.
4. **Transitions via variables** — use `var(--transition-fast/normal/slow)`.
5. **All interactive elements** must have `:focus-visible` states for keyboard accessibility.
6. **Responsive breakpoints**: `480px` (mobile-sm), `768px` (tablet), `1024px` (desktop).

---

## JavaScript (`public/assets/js/`)

| File | Function |
|------|----------|
| `i18n.js` | Internationalization engine — loads `src/i18n/{lang}.json`, replaces `data-i18n` attributes. Exposes `AmaI18n.updateDOM()` for post-render re-translation. |
| `main.js` | Global: mobile nav toggle, active nav highlighting, scroll animations |
| `index.js` | Home page: FAQ accordion, stat counters, hero parallax, news feed |
| `contacto.js` | Fetches `/api/contactos`, renders employee cards with department filter |
| `documentacion.js` | Fetches `/api/documentos`, renders document cards with category filter |
| `institucional.js` | Fetches `/api/vacantes`, renders vacancy cards + modal; handles spontaneous form submit |
| `schedule.js` | Fetches `/api/schedules`, renders table + Excel export |
| `servicios.js` | D3 world map; `serviciosContactos` data + `abrirModalServicio()` / `cerrarModalServicio()` |
| `tipo-de-cambio.js` | Fetches `/api/tipo_cambio`, renders time-gated exchange rate + PDF export |
| `novedades.js` | Fetches `/api/novedades`, category filter, sidebar widgets |

### i18n Usage

After injecting dynamic HTML content via JS, call `AmaI18n.updateDOM()` to apply translations to newly rendered `data-i18n` attributes.

**Correct:**
```js
container.innerHTML = html;
if (typeof AmaI18n !== 'undefined') AmaI18n.updateDOM();
```

**Incorrect (will silently fail):**
```js
if (window.i18nManager) window.i18nManager.updateDOM(); // i18nManager does not exist
```

### JS Security Rules

- **Never use `innerHTML`** with user-supplied or database-sourced data unless it is explicitly formatted HTML from a trusted internal API (document it).
- **Use `textContent`** for all user-visible content that should be treated as plain text.
- **No `escape()` / `unescape()`** — these are deprecated. Use `encodeURIComponent()` / `decodeURIComponent()`.
- **No `onclick` attributes** — use `addEventListener` in external JS files.
- **No inline `<script>` blocks** in PHP templates — all logic belongs in `public/assets/js/`.
- **Null-guard all `querySelector()` results** before accessing properties.

---

## Components (`src/components/`)

| File | Loads |
|------|-------|
| `header.php` | `variables.css`, `base.css`, `layout.css`, `components.css`, `header.css`, `footer.css` + navigation markup |
| `footer.php` | Footer markup with contact info (including `tel:` links), quick links, legal links, newsletter form. **Loads SweetAlert2 CDN** for global aesthetic popups. |

---

## Popups & Native PDF Tickets

- **Aesthetic Popups**: Triggered natively using **SweetAlert2** (`Swal.fire`). They enforce AMA branding (`#0b2b5e`, `#c8a145`) directly within JS callbacks (e.g., `institucional.js`, `servicios.js`). Include formal legal notices regarding data handling.
- **Portability-first PDF Tickets**: 
  - File: `src/pages/ticket.php`
  - Function: Generates a printable receipt/ticket for Form submissions without heavy server-side PDF engines (DOMPDF/TCPDF). 
  - Usage: Receives metadata via GET (Base64-encoded JSON in `?data=`), renders a print-optimized layout with watermarks and calls `window.print()` instantly on load, shifting PDF rendering strictly to the Client/Browser level.

---

## Internationalization (i18n)

- **Languages:** ES, EN, IT, PT
- **Files:** `src/i18n/{lang}.json` (14-15KB each)
- **Mechanism:** `data-i18n` attributes on HTML elements, replaced by `AmaI18n.init()`
- **Legal pages:** Use `data-lang` attribute on container divs for full-page language switching

---

## Asset URL Constants

All asset references use PHP constants (never relative paths):

| Constant | Resolves to |
|----------|-------------|
| `ASSETS_URL` | `public/assets` |
| `CSS_URL` | `public/assets/css` |
| `JS_URL` | `public/assets/js` |
| `BASE_URL` | Root URL with auto-detected protocol |

---

## Admin Portal (`private/gestion-interna-agencia/`)

- **Stylesheet:** `admin-portal.css` — uses `--admin-*` token namespace (intentionally separate from public site tokens to allow independent evolution).
- **Auth:** `includes/auth.php` — session-based RBAC with roles: `caja`, `cs`, `rrhh`, `documentacion`, `admin_global`.
- **Views:** `views/{role}.php` — validated against the `$permissions` whitelist before inclusion.

---

## Changelog

| Version | Date | Summary |
|---------|------|---------|
| 3.2 | 2026-03-26 | Implemented SweetAlert2 integration in footer. Introduced native, zero-dependency `ticket.php` for browser-based print-to-PDF receipts with legal compliance. |
| 3.1 | 2026-03-26 | Frontend audit: removed `onclick` attrs (servicios.php), inline `<script>` (contacto.php), replaced `fa-spinner` with SVG spinners, fixed OG image paths, unified phone number, migrated inline styles |
| 3.0 | 2026-03-26 | Auditoría completa: actualización de versión, validación de contenido contra estado real del código |
| 2.1 | 2026-03-17 | Production-ready audit: JS critical bug fixes (null refs, XSS), CSS variable standardization, removed duplicate component definitions, admin portal alignment |
| 2.0 | 2026-03 | Consolidated frontend architecture |
