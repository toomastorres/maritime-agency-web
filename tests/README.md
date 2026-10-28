# Pruebas — Integración con Microsoft Entra ID

Scripts sin dependencias externas. Se corren con el PHP del sistema y no
necesitan base de datos ni conexión a Microsoft.

```bash
php tests/test_entra_oidc.php    # 17 casos: validación del ID token con criptografía real
php tests/test_entra_roles.php   # 30 casos: mapeo de App Roles y redirecciones seguras
php tests/test_entra_state.php   #  7 casos: state de un solo uso y anti-replay
php tests/test_entra_ui.php      # 19 casos: que el portal no ofrezca contraseñas
```

Cada script devuelve código de salida 0 si todo pasa.

`test_entra_oidc.php` genera un par RSA y un certificado autofirmado en memoria,
arma un JWKS y firma tokens de prueba, para verificar que la validación acepta
los tokens legítimos y rechaza firma alterada, `alg: none`, tenant ajeno,
audiencia ajena, emisor distinto, expiración, replay de nonce y token sin `oid`.

`test_entra_ui.php` extrae los fragmentos de vista de los archivos reales y los
renderiza con variables controladas, para verificar el requisito central: con
Entra ID activo un empleado nunca ve un campo de contraseña, y el listado de
usuarios no se desalinea ni antes ni después de la migración.
