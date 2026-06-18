# AMA Web Project — Code Style Guide

Estándar de formateo y documentación para todos los archivos del proyecto. Seguir estas reglas al crear o modificar archivos para mantener consistencia.

---

## General

- **Indentación**: 4 espacios (nunca tabs)
- **Fin de línea**: LF (Unix)
- **Charset**: UTF-8
- **Línea máxima**: ~120 caracteres (preferencia, no hard limit)
- **Líneas en blanco**: 1 línea entre secciones lógicas

---

## CSS

### Header de archivo
```css
/* ═══════════════════════════════════════════════════════════ */
/* AMA — nombre-del-archivo.css                          */
/* Descripción breve del propósito del archivo                */
/* ═══════════════════════════════════════════════════════════ */
```

### Separadores de sección
```css
/* ─── Nombre de Sección ─── */
```
    
### Reglas
- Una propiedad por línea
- Agrupar propiedades: layout (display, position) → box model (margin, padding) → visual (color, background) → tipografía → transiciones
- Media queries agrupadas al final bajo `/* ─── Responsive ─── */`
- Línea en blanco después de cada separador y antes del siguiente

---

## PHP

### Header de archivo
```php
<?php
/**
 * Título del archivo o endpoint
 * Método y ruta (para APIs): GET /api/recurso
 *
 * Descripción del propósito del archivo.
 */
```

### Separadores de sección
```php
// ── Nombre de Sección ──────────────────────────────────────
```

### Reglas
- Indentación: 4 espacios para PHP y HTML embebido
- Docblocks PHPDoc para funciones con `@param` y `@return`
- Comentarios inline con `//` (una línea)
- En archivos con HTML embebido: usar `<!-- ── Sección ── -->` para marcar bloques HTML

### HTML embebido
- Mismo nivel de indentación (4 espacios por nivel)
- Atributos en línea si caben; multilínea si superan ~100 chars (alinear con el primer atributo)

---

## JavaScript

### Header de archivo
```javascript
/**
 * AMA — nombre-del-archivo.js
 * Descripción breve del propósito del archivo.
 */
```

### Separadores de sección
```javascript
// ── Nombre de Sección ──────────────────────────────────────
```

### Reglas
- Variables: `camelCase`
- Constantes globales: `UPPER_SNAKE_CASE`
- Funciones: `camelCase` con JSDoc cuando sea complejo
- Strings: comillas simples (`'texto'`), template literals para interpolación
- Punto y coma: siempre al final de sentencias

---

## SQL

### Header de archivo
```sql
-- ═══════════════════════════════════════════════════════════
-- Título del archivo
-- Descripción breve
-- ═══════════════════════════════════════════════════════════
```

### Separadores de tabla/módulo
```sql
-- ── N. NOMBRE DEL MÓDULO ──────────────────────────────────
```

### Reglas
- Keywords SQL en UPPERCASE: `CREATE TABLE`, `VARCHAR`, `NOT NULL`, `SELECT`, `INSERT`
- Columnas indentadas con 4 espacios
- Una columna por línea en CREATE TABLE
- Comentarios con `--`
