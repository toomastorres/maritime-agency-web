<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMA Agencies Argentina — Próximamente / Coming Soon</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap');

        :root {
            --AMA-blue-dark: #000B18;
            --AMA-blue-medium: #001A3D;
            --AMA-gold: #C5A059;
            --AMA-gold-light: #E0C184;
            --AMA-gold-dark: #A47E3B;
            --white: #ffffff;
            --white-muted: rgba(255, 255, 255, 0.7);
            --white-dim: rgba(255, 255, 255, 0.45);
            --font-primary: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
            --radius-lg: 24px;
            --radius-md: 12px;
            --transition-smooth: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-primary);
            background: radial-gradient(circle at center, var(--AMA-blue-medium) 0%, var(--AMA-blue-dark) 100%);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            padding: 24px;
        }

        /* Background Glowing Elements */
        body::before,
        body::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            filter: blur(160px);
            z-index: 1;
            pointer-events: none;
            opacity: 0.15;
        }

        body::before {
            background: var(--AMA-gold);
            top: 10%;
            left: 15%;
        }

        body::after {
            background: #0056b3;
            bottom: 10%;
            right: 15%;
        }

        /* Main Container */
        .maintenance-container {
            max-width: 620px;
            width: 100%;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-lg);
            padding: 54px 44px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.45), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.12);
            z-index: 10;
            position: relative;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Branded Title/Header */
        .brand-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
        }

        .brand-logo-fallback {
            font-family: var(--font-heading);
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-logo-fallback span {
            color: var(--AMA-gold);
            font-weight: 400;
            text-transform: none;
            font-size: 1.25rem;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 8px 18px;
            background: rgba(197, 160, 89, 0.1);
            border: 1px solid rgba(197, 160, 89, 0.3);
            color: var(--AMA-gold-light);
            font-family: var(--font-heading);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            border-radius: 50px;
            margin-bottom: 28px;
            animation: pulseGlow 2.5s infinite ease-in-out;
        }

        /* Illustration Wrapper */
        .illustration-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .compass-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 1.5px dashed rgba(197, 160, 89, 0.25);
            border-radius: 50%;
            animation: rotateCompass 20s linear infinite;
        }

        .compass-ring::before {
            content: '';
            position: absolute;
            top: -3px;
            left: 50%;
            width: 6px;
            height: 6px;
            background: var(--AMA-gold);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--AMA-gold);
        }

        .ship-illustration {
            color: var(--AMA-gold);
            animation: floatShip 4.5s ease-in-out infinite;
            z-index: 2;
        }

        /* Typography */
        .maintenance-title {
            font-family: var(--font-heading);
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--white) 40%, var(--AMA-gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .maintenance-title-en {
            font-family: var(--font-heading);
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--white-muted);
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .maintenance-desc {
            font-size: 1.05rem;
            line-height: 1.6;
            color: var(--white-muted);
            max-width: 480px;
            margin: 0 auto 12px;
        }

        .maintenance-desc-en {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--white-dim);
            max-width: 480px;
            margin: 0 auto 36px;
            border-top: 1px dashed rgba(255, 255, 255, 0.08);
            padding-top: 16px;
        }

        /* Actions */
        .actions-wrapper {
            display: flex;
            gap: 16px;
            justify-content: center;
            align-items: center;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--AMA-gold) 0%, var(--AMA-gold-dark) 100%);
            color: var(--AMA-blue-dark);
            padding: 14px 28px;
            font-family: var(--font-primary);
            font-size: 0.95rem;
            font-weight: 700;
            border: none;
            border-radius: var(--radius-md);
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(197, 160, 89, 0.25),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: var(--transition-smooth);
            cursor: pointer;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--AMA-gold-light) 0%, var(--AMA-gold) 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(197, 160, 89, 0.35);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.04);
            color: var(--white);
            padding: 14px 28px;
            font-family: var(--font-primary);
            font-size: 0.95rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: var(--transition-smooth);
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(197, 160, 89, 0.2);
                opacity: 0.9;
            }
            50% {
                box-shadow: 0 0 15px 4px rgba(197, 160, 89, 0.4);
                opacity: 1;
            }
        }

        @keyframes floatShip {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-6px) rotate(-1.5deg);
            }
        }

        @keyframes rotateCompass {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 580px) {
            .maintenance-container {
                padding: 40px 24px;
            }
            .maintenance-title {
                font-size: 1.6rem;
            }
            .maintenance-title-en {
                font-size: 1.2rem;
            }
            .actions-wrapper {
                flex-direction: column;
                width: 100%;
                gap: 12px;
            }
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <!-- Brand Header Logo -->
        <div class="brand-header">
            <div class="brand-logo-fallback">
                AMA AGENCIES <span>Argentina</span>
            </div>
        </div>

        <!-- Pulsing Status Badge -->
        <div class="status-badge">
            PRÓXIMAMENTE / COMING SOON
        </div>

        <!-- Maritime Navigation Circle & Floating Ship Illustration -->
        <div class="illustration-wrapper">
            <div class="compass-ring"></div>
            <svg class="ship-illustration" viewBox="0 0 120 120" width="95" height="95" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <!-- Sea Waves -->
                <path d="M10 85 C 30 80, 50 90, 70 85 C 90 80, 100 88, 110 85" stroke-width="1.5" />
                <path d="M20 95 C 40 92, 55 98, 75 95 C 95 92, 100 96, 105 95" stroke-width="1" opacity="0.6" />
                
                <!-- Ship Hull -->
                <path d="M25 65 L85 65 L95 78 C 80 82, 40 82, 20 78 Z" fill="currentColor" fill-opacity="0.1" stroke-width="2" />
                
                <!-- Ship Cabin / Superstructure -->
                <rect x="35" y="45" width="22" height="20" fill="currentColor" fill-opacity="0.2" />
                <rect x="42" y="33" width="10" height="12" fill="currentColor" fill-opacity="0.3" />
                <line x1="47" y1="33" x2="47" y2="25" />
                <line x1="51" y1="33" x2="51" y2="28" />
                
                <!-- Windows -->
                <circle cx="42" cy="55" r="1.5" fill="currentColor" />
                <circle cx="50" cy="55" r="1.5" fill="currentColor" />
                <rect x="44" y="37" width="6" height="4" fill="var(--AMA-blue-dark)" />
                
                <!-- Cargo Containers on deck -->
                <rect x="62" y="53" width="10" height="12" fill="currentColor" fill-opacity="0.2" />
                <rect x="73" y="57" width="10" height="8" fill="currentColor" fill-opacity="0.3" />
                <line x1="67" y1="53" x2="67" y2="65" stroke-width="0.5" opacity="0.5" />
                <line x1="78" y1="57" x2="78" y2="65" stroke-width="0.5" opacity="0.5" />
                
                <!-- Radar / Communication Dish on cabin -->
                <path d="M36 45 L32 39 M32 39 A 4 4 0 0 1 36 35" stroke-width="1.5" />
            </svg>
        </div>

        <!-- Bilingual Content -->
        <h1 class="maintenance-title">Estamos renovando nuestra experiencia digital</h1>
        <h2 class="maintenance-title-en">We are renewing our digital experience</h2>
        
        <div class="maintenance-desc">
            <p>Nuestro nuevo portal institucional está en fase final de despliegue para brindarle un servicio más ágil, seguro y eficiente.</p>
        </div>
        <div class="maintenance-desc-en">
            <p>Our new institutional portal is in the final phase of deployment to provide you with a faster, safer, and more efficient experience.</p>
        </div>

        <!-- Premium Navigation/Contact Actions -->
        <div class="actions-wrapper">
            <a href="mailto:customerserv@ama-demo.example?subject=Consulta%20Urgente%20-%20Portal%20Web" class="btn-primary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Customer Service
            </a>
            <a href="mailto:finance@ama-demo.example?subject=Consulta%20Caja/Finanzas%20-%20Portal%20Web" class="btn-secondary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="18"/><path d="M17 9h-5.5a2.5 2.5 0 0 0 0 5h3a2.5 2.5 0 0 1 0 5H12"/></svg>
                Finance
            </a>
        </div>
    </div>
</body>
</html>