# 🌐 Web Institucional + Portal Interno — Agencia Marítima

> Sitio institucional multiidioma de una agencia marítima y un **portal privado de
> gestión** para empleados (RBAC), construido en **PHP** con arquitectura limpia,
> sin frameworks pesados. La parte pública muestra servicios, schedules, novedades,
> tipo de cambio y formularios; la privada permite a cada área cargar y administrar
> esa información.

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![JS](https://img.shields.io/badge/JS-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![Docker](https://img.shields.io/badge/Demo-Docker%20Compose-2496ED?logo=docker&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

> ℹ️ Versión **anonimizada para portafolio**: marca, dominios, emails y empleados
> son ficticios (empresa de ejemplo *Atlantic Maritime Agency / AMA*); los datos de
> ejemplo (`database/seed_employees.sql`) no corresponden a personas reales. Se
> eliminaron `.env`, documentos comerciales y logos de la empresa original.

---

## Funcionalidades

**Sitio público**
- Multiidioma (ES/EN/IT/PT) con sistema i18n propio (`src/i18n/*.json`).
- Páginas institucionales, servicios (con mapa D3), schedules (lectura de Excel),
  novedades, tipo de cambio y páginas legales.
- API interna (`src/logic/api/*`) para cotizaciones, vacantes, documentos,
  postulaciones (subida de CV) y healthcheck.

**Portal interno (`/gestion-interna-agencia/`)**
- Autenticación con **sesiones, CSRF, rate limiting** y política de contraseñas
  estilo NIST (blacklist de contraseñas comunes).
- **RBAC**: cada rol ve sólo sus módulos (RRHH, Caja/tipo de cambio, Customer
  Service, Documentación, gestión de usuarios).
- Auditoría de acciones (`admin_audit_logs`) y de emails enviados.

## Arquitectura

```
├── index.php                 # Front controller (router + headers de seguridad)
├── src/
│   ├── config/               # Configuración (lee .env / env del hosting)
│   ├── core/                 # PDO, EmailService (PHPMailer), utils, parsers XLSX
│   ├── components/           # Header / footer reutilizables
│   ├── pages/                # Vistas públicas
│   ├── logic/api/            # Endpoints JSON (GET de datos, POST de formularios)
│   ├── i18n/                 # Diccionarios de traducción
│   └── email-templates/      # Plantillas HTML de notificaciones
├── private/gestion-interna-agencia/
│   ├── includes/             # auth.php (sesiones/CSRF/rate-limit), db.php, password_policy.php
│   └── views/                # Módulos del panel (RRHH, caja, CS, usuarios, auditoría)
├── database/                 # install.sql (schema) + seed (datos ficticios) + reset
└── docs/                     # Documentación técnica (BACKEND, FRONTEND, ADMIN_PORTAL, ...)
```

Decisiones destacadas: prepared statements en todo el acceso a datos (PDO),
separación de configuración por entorno vía `.env`, carga condicional de
dependencias (degradación elegante si falta `vendor/`), y headers de seguridad
(CSP-friendly) en el front controller.

## Demo local (Docker)

```bash
cp .env.example .env        # opcional: el compose ya pasa las credenciales por entorno
docker compose up --build
```

- Sitio público: <http://localhost:8080>
- Portal interno: <http://localhost:8080/gestion-interna-agencia/>

MySQL se inicializa solo con el schema (`database/install.sql`) y los datos de
ejemplo. Para crear el primer usuario admin, ver [docs/PROVISION_USUARIOS.md](docs/PROVISION_USUARIOS.md).

> Para deploy público gratuito se puede usar Render/Railway (soportan Docker). La
> URL de la demo se agrega acá una vez desplegada.

## Stack

PHP 8.2 · MySQL 8 (PDO) · JavaScript vanilla + D3.js · PHPMailer (SMTP) ·
Apache (`.htaccess`: rewrite + cache) · Composer · Docker / Docker Compose.

## Seguridad

`.env`, logs y uploads están excluidos por [.gitignore](.gitignore). Las
credenciales se inyectan por variables de entorno; el repositorio no contiene
secretos ni datos reales.
