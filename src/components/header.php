<?php
/**
 * AMA — header.php
 * Componente de cabecera: carga CSS, fuentes, navegación principal.
 * Incluye detección de página activa por server-side, selector de idioma
 * compacto (globo) y comportamiento glassmorphism al hacer scroll.
 */
require_once __DIR__ . '/../config/config.php';

// ── Active-page detection ─────────────────────────────────────────────────
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPage = basename(trim($currentPath, '/'));

// Normalise: empty or index maps to 'home'
if ($currentPage === '' || $currentPage === 'index.php') {
    $currentPage = 'home';
}

/**
 * Returns 'active' CSS class if the given slug matches the current page.
 * Accepts an array of slugs that all resolve to the same link.
 *
 * @param string|array $slugs
 * @return string
 */
function navActive(string|array $slugs): string {
    global $currentPage;
    $slugs = (array) $slugs;
    return in_array($currentPage, $slugs, true) ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

  <!-- CSS design system — cache-busted by content hash -->
  <link rel="stylesheet" href="<?php echo assetUrl('css/variables.css'); ?>">
  <link rel="stylesheet" href="<?php echo assetUrl('css/base.css'); ?>">
  <link rel="stylesheet" href="<?php echo assetUrl('css/layout.css'); ?>">
  <link rel="stylesheet" href="<?php echo assetUrl('css/hero.css'); ?>">
  <link rel="stylesheet" href="<?php echo assetUrl('css/components.css'); ?>">
  <link rel="stylesheet" href="<?php echo assetUrl('css/header.css'); ?>">
  <link rel="stylesheet" href="<?php echo assetUrl('css/footer.css'); ?>">
</head>
<body>

<!-- ════════════════════════════════════════════════════════════════
     AMA HEADER
     ════════════════════════════════════════════════════════════════ -->
<header class="AMA-header" id="AMA-header" role="banner" data-scrolled="false">

  <div class="header-inner">

    <!-- ── Logo ─────────────────────────────────────────── -->
    <div class="header-logo">
      <a href="<?php echo BASE_URL; ?>" aria-label="AMA Agencies Argentina — Inicio">
        <div class="header-logo-inner">

          <div class="header-logo-icon" aria-hidden="true">
            <!-- AMA flag brand mark -->
            <img src="<?php echo ASSETS_URL; ?>/img/cropped-AMA_bandera_azul.png"
                 alt="" width="27" height="27" decoding="async">
          </div>

          <div class="header-logo-text">
            <span class="header-logo-brand">AMA AGENCIES</span>
            <span class="header-logo-country">Argentina</span>
          </div>

        </div>
      </a>
    </div><!-- /.header-logo -->

    <!-- ── Desktop Nav ───────────────────────────────────── -->
    <nav class="header-nav" id="header-nav" role="navigation" aria-label="Navegación principal">

      <a href="<?php echo BASE_URL; ?>"
         class="nav-link<?php echo navActive('home'); ?>"
         data-i18n="nav.home">Inicio</a>

      <a href="<?php echo BASE_URL; ?>tipo-de-cambio"
         class="nav-link<?php echo navActive('tipo-de-cambio'); ?>"
         data-i18n="nav.exchange">Tipo de Cambio</a>

      <a href="<?php echo BASE_URL; ?>schedule"
         class="nav-link<?php echo navActive('schedule'); ?>"
         data-i18n="nav.schedule">Schedule</a>

      <a href="<?php echo BASE_URL; ?>servicios"
         class="nav-link<?php echo navActive('servicios'); ?>"
         data-i18n="nav.services">Servicios</a>

      <!-- Estas secciones fueron comentadas para simplificar la navegación, pero se mantienen en el código para posible reactivación futura si se decide desarrollar esas páginas -->
      <!--
      <a href="<?php echo BASE_URL; ?>procedimientos"
         class="nav-link<?php echo navActive('procedimientos'); ?>"
         data-i18n="nav.procedures">Procedimientos</a>

      <a href="<?php echo BASE_URL; ?>institucional"
         class="nav-link<?php echo navActive('institucional'); ?>"
         data-i18n="nav.institutional">Institucional</a>
      -->

      <a href="<?php echo BASE_URL; ?>contacto"
         class="nav-link<?php echo navActive('contacto'); ?>"
         data-i18n="nav.contact">Contacto</a>

    </nav><!-- /#header-nav -->

    <!-- ── Right-side controls ──────────────────────────── -->
    <div class="header-controls">

      <!-- Globe / Language dropdown -->
      <div class="lang-dropdown" aria-label="Selector de idioma">
        <button class="lang-trigger" aria-haspopup="true" aria-expanded="false"
                aria-label="Cambiar idioma" title="Idioma / Language">
          <!-- Globe SVG -->
          <svg class="globe-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
               width="18" height="18" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <line x1="2" y1="12" x2="22" y2="12"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
          </svg>
          <span class="lang-current">ES</span>
          <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
               width="12" height="12" aria-hidden="true">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>

        <ul class="lang-menu" role="menu" aria-label="Opciones de idioma">
          <li role="none"><button class="lang-option active" data-lang="es" role="menuitem">
            <span class="lang-flag">🇦🇷</span> <span>Español</span>
          </button></li>
          <li role="none"><button class="lang-option" data-lang="en" role="menuitem">
            <span class="lang-flag">🇬🇧</span> <span>English</span>
          </button></li>
          <li role="none"><button class="lang-option" data-lang="it" role="menuitem">
            <span class="lang-flag">🇮🇹</span> <span>Italiano</span>
          </button></li>
          <li role="none"><button class="lang-option" data-lang="pt" role="menuitem">
            <span class="lang-flag">🇧🇷</span> <span>Português</span>
          </button></li>
        </ul>
      </div><!-- /.lang-dropdown -->

      <!-- Mobile hamburger -->
      <button class="nav-toggle" id="nav-toggle"
              aria-label="Abrir menú de navegación"
              aria-expanded="false"
              aria-controls="mobile-drawer">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>

    </div><!-- /.header-controls -->

  </div><!-- /.header-inner -->

</header><!-- /.AMA-header -->

<!-- ── Mobile Off-Canvas Drawer ────────────────────────────────── -->
<div class="nav-overlay" id="nav-overlay" aria-hidden="true"></div>

<nav class="mobile-drawer" id="mobile-drawer"
     role="navigation" aria-label="Menú móvil" aria-hidden="true">

  <div class="mobile-drawer-header">
    <div class="mobile-drawer-logo">
      <div class="mobile-drawer-icon" aria-hidden="true">
        <!-- AMA flag brand mark -->
        <img src="<?php echo ASSETS_URL; ?>/img/cropped-AMA_bandera_azul.png"
             alt="" width="24" height="24" decoding="async">
      </div>
      <div>
        <div class="mobile-drawer-brand">AMA AGENCIES</div>
        <div class="mobile-drawer-country">Argentina</div>
      </div>
    </div>
    <button class="drawer-close" aria-label="Cerrar menú">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
           width="22" height="22" aria-hidden="true">
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
  </div><!-- /.mobile-drawer-header -->

  <ul class="mobile-nav-list">
    <li>
      <a href="<?php echo BASE_URL; ?>"
         class="mobile-nav-link<?php echo navActive('home'); ?>">
        <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span data-i18n="nav.home">Inicio</span>
      </a>
    </li>
    <li>
      <a href="<?php echo BASE_URL; ?>tipo-de-cambio"
         class="mobile-nav-link<?php echo navActive('tipo-de-cambio'); ?>">
        <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <span data-i18n="nav.exchange">Tipo de Cambio</span>
      </a>
    </li>
    <li>
      <a href="<?php echo BASE_URL; ?>schedule"
         class="mobile-nav-link<?php echo navActive('schedule'); ?>">
        <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span data-i18n="nav.schedule">Schedule</span>
      </a>
    </li>
    <li>
      <a href="<?php echo BASE_URL; ?>servicios"
         class="mobile-nav-link<?php echo navActive('servicios'); ?>">
        <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        <span data-i18n="nav.services">Servicios</span>
      </a>
    </li>

    <!-- Estas secciones fueron comentadas para simplificar la navegación, pero se mantienen en el código para posible reactivación futura si se decide desarrollar esas páginas -->
    <!--
    <li>
      <a href="<?php echo BASE_URL; ?>procedimientos"
         class="mobile-nav-link<?php echo navActive('procedimientos'); ?>">
        <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
        <span data-i18n="nav.procedures">Procedimientos</span>
      </a>
    </li>
    <li>
      <a href="<?php echo BASE_URL; ?>institucional"
         class="mobile-nav-link<?php echo navActive('institucional'); ?>">
        <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span data-i18n="nav.institutional">Institucional</span>
      </a>
    </li>
    -->

    <li>
      <a href="<?php echo BASE_URL; ?>contacto"
         class="mobile-nav-link<?php echo navActive('contacto'); ?>">
        <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 5.93 5.93l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span data-i18n="nav.contact">Contacto</span>
      </a>
    </li>
  </ul>

  <!-- Mobile lang selector -->
  <div class="mobile-lang-selector">
    <span class="mobile-lang-label" data-i18n="nav.lang_label">Idioma</span>
    <div class="mobile-lang-buttons">
      <button class="mobile-lang-btn active" data-lang="es">ES</button>
      <button class="mobile-lang-btn" data-lang="en">EN</button>
      <button class="mobile-lang-btn" data-lang="it">IT</button>
      <button class="mobile-lang-btn" data-lang="pt">PT</button>
    </div>
  </div>

</nav><!-- /.mobile-drawer -->
