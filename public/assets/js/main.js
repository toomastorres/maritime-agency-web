/**
 * AMA — main.js
 * Script global de inicialización: i18n, navegación móvil off-canvas,
 * glassmorphism scroll, lang dropdown, smooth scroll.
 */

document.addEventListener('DOMContentLoaded', async function () {
    'use strict';

    // ── All initializers called at the bottom after all function definitions ──


    /* ═══════════════════════════════════════════════════════════════
       SCROLL BEHAVIOR
       Adds data-scrolled="true" to header after 10px scroll,
       which triggers the CSS glassmorphism transition.
       ═══════════════════════════════════════════════════════════════ */
    function initScrollBehavior() {
        const header = document.getElementById('AMA-header');
        if (!header) return;

        const THRESHOLD = 10;

        function updateScrollState() {
            const scrolled = window.scrollY > THRESHOLD;
            header.dataset.scrolled = scrolled ? 'true' : 'false';
        }

        // Run immediately on load in case page is already scrolled
        updateScrollState();

        window.addEventListener('scroll', updateScrollState, { passive: true });
    }


    /* ═══════════════════════════════════════════════════════════════
       MOBILE OFF-CANVAS DRAWER
       Hamburger opens a 280px drawer from the right.
       Overlay click / Escape / nav-link click closes it.
       ═══════════════════════════════════════════════════════════════ */
    function initMobileDrawer() {
        const toggle  = document.getElementById('nav-toggle');
        const drawer  = document.getElementById('mobile-drawer');
        const overlay = document.getElementById('nav-overlay');
        const closeBtn = drawer ? drawer.querySelector('.drawer-close') : null;

        if (!toggle || !drawer) return;

        function openDrawer() {
            toggle.classList.add('active');
            toggle.setAttribute('aria-expanded', 'true');
            drawer.classList.add('open');
            drawer.setAttribute('aria-hidden', 'false');
            if (overlay) {
                overlay.classList.add('active');
                overlay.setAttribute('aria-hidden', 'false');
            }
            document.body.style.overflow = 'hidden';

            // Move focus to first focusable element in drawer
            const firstFocusable = drawer.querySelector('a, button');
            if (firstFocusable) firstFocusable.focus();
        }

        function closeDrawer() {
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
            drawer.classList.remove('open');
            drawer.setAttribute('aria-hidden', 'true');
            if (overlay) {
                overlay.classList.remove('active');
                overlay.setAttribute('aria-hidden', 'true');
            }
            document.body.style.overflow = '';
            toggle.focus();
        }

        // Hamburger toggle
        toggle.addEventListener('click', () => {
            drawer.classList.contains('open') ? closeDrawer() : openDrawer();
        });

        // Close button inside drawer
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

        // Overlay click
        if (overlay) overlay.addEventListener('click', closeDrawer);

        // Close on nav link click
        drawer.querySelectorAll('.mobile-nav-link').forEach(link => {
            link.addEventListener('click', closeDrawer);
        });

        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && drawer.classList.contains('open')) {
                closeDrawer();
            }
        });

        // Focus trap inside drawer when open
        drawer.addEventListener('keydown', (e) => {
            if (!drawer.classList.contains('open') || e.key !== 'Tab') return;
            const focusables = Array.from(drawer.querySelectorAll(
                'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
            ));
            if (focusables.length === 0) return;
            const first = focusables[0];
            const last  = focusables[focusables.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });

        // Mobile lang buttons — keep in sync with globe dropdown
        drawer.querySelectorAll('.mobile-lang-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                drawer.querySelectorAll('.mobile-lang-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                // Sync globe dropdown current label if present
                const globeCurrent = document.querySelector('.lang-current');
                if (globeCurrent) globeCurrent.textContent = btn.dataset.lang.toUpperCase();
                // Dispatch custom event for i18n module
                document.dispatchEvent(new CustomEvent('AMA:lang', { detail: { lang: btn.dataset.lang } }));
            });
        });
    }


    /* ═══════════════════════════════════════════════════════════════
       GLOBE LANGUAGE DROPDOWN
       Toggles .open on .lang-dropdown, auto-closes on outside click.
       ═══════════════════════════════════════════════════════════════ */
    function initLangDropdown() {
        const dropdown = document.querySelector('.lang-dropdown');
        if (!dropdown) return;

        const trigger = dropdown.querySelector('.lang-trigger');
        const menu    = dropdown.querySelector('.lang-menu');

        function openDropdown() {
            dropdown.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            // Focus first option
            const first = menu.querySelector('.lang-option');
            if (first) first.focus();
        }

        function closeDropdown() {
            dropdown.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.contains('open') ? closeDropdown() : openDropdown();
        });

        // Option selection
        dropdown.querySelectorAll('.lang-option').forEach(opt => {
            opt.addEventListener('click', () => {
                const lang = opt.dataset.lang;
                // Update active state
                dropdown.querySelectorAll('.lang-option').forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                // Update trigger label
                const current = trigger.querySelector('.lang-current');
                if (current) current.textContent = lang.toUpperCase();
                // Sync mobile buttons
                document.querySelectorAll('.mobile-lang-btn').forEach(b => {
                    b.classList.toggle('active', b.dataset.lang === lang);
                });
                closeDropdown();
                document.dispatchEvent(new CustomEvent('AMA:lang', { detail: { lang } }));
            });
        });

        // Keyboard navigation inside menu
        menu.addEventListener('keydown', (e) => {
            const options = Array.from(menu.querySelectorAll('.lang-option'));
            const idx = options.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                options[(idx + 1) % options.length].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                options[(idx - 1 + options.length) % options.length].focus();
            } else if (e.key === 'Escape') {
                closeDropdown();
                trigger.focus();
            }
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target)) closeDropdown();
        });
    }


    /* ═══════════════════════════════════════════════════════════════
       SMOOTH SCROLL — in-page anchors
       ═══════════════════════════════════════════════════════════════ */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    const headerHeight = document.getElementById('AMA-header')?.offsetHeight || 72;
                    const top = target.getBoundingClientRect().top + window.scrollY - headerHeight - 16;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            });
        });
    }


    /* ═══════════════════════════════════════════════════════════════
       SCROLL REVEAL — fade-in-up on scroll via IntersectionObserver
       ═══════════════════════════════════════════════════════════════ */
    function initScrollReveal() {
        const sectionEls = document.querySelectorAll(
            '.section-wrapper, .solutions-section, .services-preview, ' +
            '.stats-section, .faq-section, .routes-map-section, .careers-section, ' +
            '.contacto-info-grid-section, .mv-section, [data-reveal]'
        );

        const cardEls = document.querySelectorAll(
            '.solution-card, .service-card, .stat-item, .faq-item, ' +
            '.team-card, .value-card-v2, .mv-card, .empleado-card, ' +
            '.servicio-card-img, .puerto-region-card, .vacante-card'
        );

        if (!sectionEls.length && !cardEls.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.07, rootMargin: '0px 0px -40px 0px' });

        sectionEls.forEach(el => {
            el.classList.add('reveal-on-scroll');
            observer.observe(el);
        });

        cardEls.forEach((el, i) => {
            el.classList.add('reveal-card');
            el.style.transitionDelay = `${(i % 4) * 75}ms`;
            observer.observe(el);
        });
    }


    /* ═══════════════════════════════════════════════════════════════
       BACK TO TOP BUTTON
       ═══════════════════════════════════════════════════════════════ */
    function initBackToTop() {
        const btn = document.createElement('button');
        btn.id = 'back-to-top';
        btn.setAttribute('aria-label', 'Volver al inicio');
        btn.innerHTML = `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6 1.41 1.41z"/></svg>`;
        document.body.appendChild(btn);

        window.addEventListener('scroll', () => {
            btn.classList.toggle('visible', window.scrollY > 400);
        }, { passive: true });

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }


    /* ═══════════════════════════════════════════════════════════════
       MODAL CLOSE (generic)
       ═══════════════════════════════════════════════════════════════ */
    function initModalClose() {
        document.querySelectorAll('.close-modal, [data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal');
                if (modal) modal.style.display = 'none';
            });
        });
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
        });
    }


    /* ═══════════════════════════════════════════════════════════════
       INJECT GLOBAL STYLES (scroll reveal + back-to-top)
       ═══════════════════════════════════════════════════════════════ */
    function injectGlobalStyles() {
        if (document.getElementById('AMA-global-styles')) return;
        const style = document.createElement('style');
        style.id = 'AMA-global-styles';
        style.textContent = `
            /* Scroll Reveal */
            .reveal-on-scroll {
                opacity: 0;
                transform: translateY(28px);
                transition: opacity 0.6s cubic-bezier(0.4,0,0.2,1),
                            transform 0.6s cubic-bezier(0.4,0,0.2,1);
            }
            .reveal-on-scroll.revealed { opacity: 1; transform: translateY(0); }
            .reveal-card {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.5s ease, transform 0.5s ease;
            }
            .reveal-card.revealed { opacity: 1; transform: translateY(0); }

            /* Back to Top */
            #back-to-top {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                z-index: 998;
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: var(--AMA-blue, #00427A);
                color: #fff;
                border: 2px solid var(--AMA-gold, #C5A059);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 20px rgba(0,66,122,0.3);
                opacity: 0;
                transform: translateY(20px) scale(0.8);
                pointer-events: none;
                transition: opacity 0.3s ease, transform 0.3s ease, background 0.2s ease;
            }
            #back-to-top.visible {
                opacity: 1;
                transform: translateY(0) scale(1);
                pointer-events: auto;
            }
            #back-to-top:hover {
                background: var(--AMA-gold, #C5A059);
                transform: translateY(-3px) scale(1);
            }
            @media (max-width: 768px) {
                #back-to-top { bottom: 1.25rem; right: 1.25rem; width: 44px; height: 44px; }
            }

            /* Reduced motion */
            @media (prefers-reduced-motion: reduce) {
                .reveal-on-scroll, .reveal-card { transition: none !important; opacity: 1 !important; transform: none !important; }
                #back-to-top { transition: none !important; }
            }
        `;
        document.head.appendChild(style);
    }

    // ── Run all initializers ────────────────────────────────────────────
    if (typeof AMAI18n !== 'undefined') {
        await AMAI18n.init();
    }
    initScrollBehavior();
    initMobileDrawer();
    initLangDropdown();
    initSmoothScroll();
    initScrollReveal();
    initBackToTop();
    initModalClose();
    injectGlobalStyles();

});


