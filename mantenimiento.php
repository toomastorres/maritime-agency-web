<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atlantic Maritime Agency — Próximamente / Coming Soon</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="public/assets/img/logo-mark.svg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Serif+Display&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'DM Sans', Verdana, Geneva, Arial, sans-serif;
            background: #0F396C;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .cs-wrapper {
            width: 100%;
            max-width: 560px;
        }

        .cs-card {
            background: #ffffff;
            border: 1px solid rgba(128, 128, 131, .35);
            padding: 56px 48px;
            text-align: center;
        }

        .cs-brand {
            margin-bottom: 32px;
        }

        .cs-logo {
            height: 28px;
            max-width: 100%;
            width: auto;
        }

        .cs-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #0F396C;
            margin-bottom: 24px;
        }

        .cs-kicker {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: #0069CC;
            margin-bottom: 20px;
        }

        .cs-title {
            font-family: 'DM Serif Display', Georgia, serif;
            font-weight: 400;
            font-size: 28px;
            line-height: 1.3;
            color: #0F396C;
            margin: 0 0 16px;
        }

        .cs-lead {
            font-size: 16px;
            line-height: 1.7;
            color: #1A1A1C;
            margin: 0 auto;
            max-width: 420px;
        }

        .cs-rule {
            height: 1px;
            background: rgba(128, 128, 131, .25);
            margin: 24px auto;
            max-width: 100px;
        }

        .cs-lead-en {
            font-size: 14px;
            line-height: 1.6;
            color: #808083;
            margin: 0 auto 36px;
            max-width: 420px;
        }

        .cs-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cs-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 24px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
        }

        .cs-btn--primary { background: #0F396C; color: #ffffff; }
        .cs-btn--primary:hover { background: #0069CC; }

        .cs-btn--outline { border-color: #0F396C; color: #0F396C; font-weight: 500; }
        .cs-btn--outline:hover { background: rgba(15, 57, 108, .06); }

        @media (max-width: 480px) {
            .cs-card { padding: 40px 24px; }
            .cs-title { font-size: 24px; }
            .cs-actions { flex-direction: column; }
            .cs-btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="cs-wrapper">
        <div class="cs-card">

            <div class="cs-brand">
                <img class="cs-logo" src="public/assets/img/logo-blue.svg" alt="Atlantic Maritime Agency">
            </div>

            <div class="cs-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="5" r="2"/>
                    <line x1="12" y1="7" x2="12" y2="21"/>
                    <line x1="8" y1="10" x2="16" y2="10"/>
                    <path d="M5 12H2a10 10 0 0 0 10 10 10 10 0 0 0 10-10h-3"/>
                </svg>
            </div>

            <div class="cs-kicker">Próximamente / Coming Soon</div>

            <h1 class="cs-title">Estamos renovando nuestra experiencia digital</h1>
            <p class="cs-lead">Nuestro nuevo portal institucional está en fase final de despliegue para brindarle un servicio más ágil, seguro y eficiente.</p>

            <div class="cs-rule"></div>

            <p class="cs-lead-en">Our new institutional portal is in the final phase of deployment to provide you with a faster, safer, and more efficient experience.</p>

            <div class="cs-actions">
                <a href="mailto:customerserv@atlantic-agency.example.com?subject=Consulta%20Urgente%20-%20Portal%20Web" class="cs-btn cs-btn--primary">Atención al Cliente / Customer Service</a>
                <a href="mailto:finance@atlantic-agency.example.com?subject=Consulta%20Caja/Finanzas%20-%20Portal%20Web" class="cs-btn cs-btn--outline">Finanzas / Finance</a>
            </div>

        </div>
    </div>
</body>
</html>
