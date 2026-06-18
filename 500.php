<?php
/**
 * ============================================================
 *  AMA Agencies Argentina — 500 Error Interno (Premium)
 * ============================================================
 */
require_once __DIR__ . '/src/config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del Servidor — AMA Agencies Argentina</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo ASSETS_URL; ?>/img/favicon.ico" type="image/x-icon">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?php echo assetUrl('css/404.css'); ?>">
</head>
<body>
    <div class="error-container">
        <!-- Brand Logo Header -->
        <div class="logo-header">
            <img src="<?php echo ASSETS_URL; ?>/img/logo-blue.svg" alt="AMA Agencies Argentina">
        </div>

        <!-- Maritime Radar Sweep & Floating Ship Illustration -->
        <div class="illustration-wrapper">
            <div class="radar-sweep"></div>
            <svg class="ship-illustration" viewBox="0 0 120 120" width="100" height="100" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

        <!-- Error Code and Bilingual Message -->
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Error Interno del Servidor</h2>
        
        <div class="error-desc">
            <p>Lo sentimos, ha ocurrido un error interno de procesamiento en nuestro servidor. Nuestro equipo técnico ya ha sido notificado automáticamente.</p>
            <div class="error-desc-en">
                <p>We are sorry, an internal server error has occurred while processing your request. Our technical support team has been automatically notified.</p>
            </div>
        </div>

        <!-- Premium Navigation Actions -->
        <div class="actions-wrapper">
            <a href="<?php echo BASE_URL; ?>/" class="btn-primary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Volver al Inicio / Home
            </a>
            <a href="mailto:info@ama-demo.example?subject=Consulta%20Web%20-%20Error%20500" class="btn-secondary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Reportar / Support
            </a>
        </div>
    </div>
</body>
</html>
