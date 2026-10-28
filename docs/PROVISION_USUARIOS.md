# Provisión de usuarios del Portal Interno

Guía para dejar operativo el acceso al portal `/gestion-interna-agencia`.
Método elegido: **contraseña manual cargada por el administrador** (sin invitación por email).

---

## 1. Verificar el estado de la base (hosting)

La base solo existe en el hosting. Desde phpMyAdmin (o consola MySQL) corré:

```sql
SHOW TABLES;
SELECT id, username, rol_portal, is_active FROM usuarios_admin;
```

- **Si no existen las tablas** → importá `database/install.sql`.
- **Si existen pero con datos viejos/inconsistentes** → (opcional) importá `database/reset_data.sql` para limpiar, y luego `database/install.sql` si hiciera falta.
- **Si ya hay usuarios del seed viejo** (clave `Temporal2026!`) → borralos: ese seed era inseguro y ya fue removido de `install.sql`. Podés vaciar la tabla con `reset_data.sql` (o `TRUNCATE TABLE usuarios_admin;` con `FOREIGN_KEY_CHECKS=0`).

> Importá también `database/seed_employees.sql` si querés el directorio de staff inicial.

## 2. Crear el primer administrador (bootstrap, una sola vez)

1. En el `.env` del hosting, definí un token temporal:
   ```
   SETUP_TOKEN=setup_<algo-aleatorio-largo>
   ```
2. Entrá a: `https://atlantic-agency.example.com/gestion-interna-agencia/setup.php?token=<ese-token>`
3. Completá nombre, usuario, correo y una **contraseña fuerte** (mín. 12 caracteres; el medidor te orienta). Se crea el `admin_global`.
4. **Cerrá la puerta:** borrá el archivo `setup.php` y dejá `SETUP_TOKEN` vacío en el `.env`.

> **`setup.php` ya fue eliminado del repositorio** (septiembre 2026), una vez creado el primer
> administrador y designada la cuenta break-glass. Si alguna vez hiciera falta volver a
> usarlo, se recupera del historial de Git con
> `git show <commit>:private/gestion-interna-agencia/setup.php`, se sube por FTP, se usa y se
> borra de nuevo.

## 3. Crear el resto de los usuarios (desde el panel)

Logueado como admin: **Usuarios Admin → Invitar Usuario**.

Para cada compañero, completá nombre/usuario/correo, elegí el rol y **escribí una contraseña en "Establecer Contraseña Manualmente"**. Al cargar una clave manual **no se envía ningún email**: se la pasás vos en mano.

| Rol | Acceso |
|-----|--------|
| `caja` | Tipo de cambio + datos bancarios |
| `cs` | Schedules + novedades |
| `rrhh` | Vacantes + directorio de empleados |
| `documentacion` | Documentos oficiales |
| `admin_global` | Todo + panel de Salud del Sistema |

## 4. Verificación final

- Probá login con un usuario de cada rol y confirmá que solo ve su sección.
- En **Salud del Sistema** mirá el "Resumen de Seguridad" (usuarios por rol, logins fallidos 24 h).
- Confirmá que `setup.php` ya no existe y `SETUP_TOKEN` está vacío.
