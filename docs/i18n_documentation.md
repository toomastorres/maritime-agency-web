# Documentación de Internacionalización (i18n)

**Proyecto**: Atlantic Maritime Agency Web
**Motor**: `public/assets/js/i18n.js`

## Arquitectura Base
El proyecto utiliza un sistema de internacionalización basado en JavaScript puro sin dependencias pesadas, ideal para el entorno híbrido de despliegue. 

- **Diccionarios**: Archivos JSON ubicados en `src/i18n/` (`es.json`, `en.json`, `pt.json`, `it.json`).
- **Motor**: `AmaI18n` provee los métodos principales para la carga asíncrona, detección inicial de idiomas usando URL Params o `localStorage`, y sustitución bidimensional en el DOM.

## Uso en Frontend (Estático)
Para traducir texto que se procesa a nivel de documento HTML (Archivos PHP en `src/pages/`):
Usa el atributo `data-i18n` indicando la ruta del key correspondiente en el diccionario.

```html
<h1 class="hero__title" data-i18n="hero.title">Bienvenidos a AMA</h1>
```

**Atributos soportados**:
- `data-i18n="key.subkey"`: Traduce el `textContent` y, si la cadena en JSON contiene HTML explícito y la etiqueta tiene el atributo `data-i18n-html`, traduce `innerHTML` sanitizado.
- `data-i18n-placeholder="key"`: Traduce placeholders en campos de formularios.
- `data-i18n-title="key"`: Traduce el tip (`title`).
- `data-i18n-alt="key"`: Traduce el atributo `alt` (ideal para imágenes informativas).

## Uso en Frontend (Dinámico a través de API)
Para texto generado a partir del lado del cliente (`fetch` en archivos JS):

1. **Inyección en Templates**: Al construir el HTML inyectado en el DOM con `innerHTML` o `createElement`, agrega los atributos `data-i18n` con llaves normalizadas.
   ```javascript
   const catKey = categoria.toLowerCase().replace(/\s+/g, '-').replace(/[\u0300-\u036f]/g, "");
   const template = `<span data-i18n="categories.${catKey}">${categoria}</span>`;
   ```

2. **Re-disparo del Motor (IMPORTANTE)**: Una vez que el DOM fue modificado con la respuesta asíncrona y nuevos atributos `data-i18n` existen, es necesario indicarle al manejador global que re-evalúe el documento:
   ```javascript
   if (typeof AmaI18n !== 'undefined') {
       AmaI18n.updateDOM();
   }
   ```
   Esto asegura que los nuevos elementos, como resultados de búsqueda o listados de empleados (`contacto.js`), se traduzcan inmediatamente al idioma actual del navegador.

## Agregar Nuevos Textos
1. Edite los 4 archivos JSON localizados en `src/i18n/`. Considere anidar propiedades semánticamente y asignar llaves lógicas sin espacios.
2. Compruebe la visualización sin errores de sintaxis en el archivo JSON. 
3. Asegúrese de que las traducciones dinámicas en JavaScript que referencian a objetos o strings que puedan tener mayúsculas o espacios pasen por un `replace(/\s+/g, '-')` y las llaves mantengan la uniformidad.
