/**
 * AMA — index.js
 * Lógica de la página principal: FAQ accordion, contadores de estadísticas,
 * typewriter en el badge del hero y scroll parallax en el hero.
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ── Typewriter Animation on Hero Badge ──────────────────────────────
    function initHeroTypewriter() {
        const typewriterEl = document.getElementById('heroTypewriterText');
        if (!typewriterEl) return;

        const configEl = document.getElementById('heroBadgeConfig');
        const defaultWords = ['Conectividad', 'Confiabilidad', 'Eficiencia'];
        let words = defaultWords;
        
        if (configEl && configEl.hasAttribute('data-i18n-words')) {
            // Se sobreescriben en updateDOM si es necesario, pero las leemos de dataset o texto.
            // Para integrar con i18n.js usaremos CustomEvent.
            document.addEventListener('AMA:lang_updated', () => {
                const text = AMAI18n.t('hero.words');
                if (text && text !== 'hero.words') {
                    words = text.split('|');
                    wordIdx = 0;
                }
            });
            // Try to load initial translations
            if (typeof AMAI18n !== 'undefined') {
                const text = AMAI18n.t('hero.words');
                if (text && text !== 'hero.words') {
                    words = text.split('|');
                }
            }
        }

        let wordIdx = 0;
        let charIdx = 0;
        let isDeleting = false;
        let typingSpeed = 120;

        function type() {
            const currentWord = words[wordIdx];
            if (isDeleting) {
                typewriterEl.textContent = currentWord.substring(0, charIdx - 1);
                charIdx--;
                typingSpeed = 50;
            } else {
                typewriterEl.textContent = currentWord.substring(0, charIdx + 1);
                charIdx++;
                typingSpeed = 120;
            }

            if (!isDeleting && charIdx === currentWord.length) {
                // Pause at full word
                isDeleting = true;
                typingSpeed = 2000;
            } else if (isDeleting && charIdx === 0) {
                isDeleting = false;
                wordIdx = (wordIdx + 1) % words.length;
                typingSpeed = 500;
            }

            setTimeout(type, typingSpeed);
        }

        // Start typewriter after a short delay
        setTimeout(type, 1000);
    }

    // ── Hero Parallax ──────────────────────────────────────────────────
    function initHeroParallax() {
        const heroImg = document.getElementById('heroParallaxImg');
        const heroSection = document.getElementById('hero');
        if (!heroImg || !heroSection) return;

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            const heroHeight = heroSection.offsetHeight;

            if (scrollY <= heroHeight) {
                // Move bg image down slightly slower than scroll (0.3x)
                heroImg.style.transform = `translateY(${scrollY * 0.28}px) scale(1.02)`;
            }
        }, { passive: true });
    }

    // ── Animated Counter Stats ─────────────────────────────────────────
    function initStatCounters() {
        const statsSec = document.getElementById('statsSection');
        const counters = document.querySelectorAll('.idx-stat-card__number');
        if (!statsSec || !counters.length) return;

        let animated = false;

        function animateCounter(el) {
            const target = parseInt(el.getAttribute('data-target'), 10) || 0;
            const suffix = el.getAttribute('data-suffix') || '';
            const duration = 2000; // ms
            const start = performance.now();

            function run(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                
                // Ease out cubic
                const eased = 1 - Math.pow(1 - progress, 3);
                const val = Math.round(eased * target);

                el.textContent = val + suffix;

                if (progress < 1) {
                    requestAnimationFrame(run);
                } else {
                    el.textContent = target + suffix;
                }
            }
            requestAnimationFrame(run);
        }

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !animated) {
                        animated = true;
                        counters.forEach(el => animateCounter(el));
                        observer.unobserve(statsSec);
                    }
                });
            }, { threshold: 0.25 });
            observer.observe(statsSec);
        } else {
            // Fallback
            counters.forEach(el => animateCounter(el));
        }
    }

    // ── FAQ Accordion ──────────────────────────────────────────────────
    function initFAQAccordion() {
        const faqItems = document.querySelectorAll('.idx-faq-item');
        if (!faqItems.length) return;

        faqItems.forEach(item => {
            const btn = item.querySelector('.idx-faq-btn');
            const body = item.querySelector('.idx-faq-body');
            if (!btn || !body) return;

            btn.addEventListener('click', () => {
                const isOpen = item.classList.contains('open');

                // Close all other accordion items
                faqItems.forEach(otherItem => {
                    if (otherItem !== item && otherItem.classList.contains('open')) {
                        otherItem.classList.remove('open');
                        const otherBtn = otherItem.querySelector('.idx-faq-btn');
                        const otherBody = otherItem.querySelector('.idx-faq-body');
                        if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                        if (otherBody) {
                            otherBody.style.maxHeight = '0px';
                            // Wait for transition before hiding
                            setTimeout(() => {
                                if (!otherItem.classList.contains('open')) {
                                    otherBody.setAttribute('hidden', '');
                                }
                            }, 420);
                        }
                    }
                });

                // Toggle current item
                if (isOpen) {
                    item.classList.remove('open');
                    btn.setAttribute('aria-expanded', 'false');
                    body.style.maxHeight = '0px';
                    setTimeout(() => {
                        if (!item.classList.contains('open')) {
                            body.setAttribute('hidden', '');
                        }
                    }, 420);
                } else {
                    body.removeAttribute('hidden');
                    // Force reflow
                    body.offsetHeight; 
                    item.classList.add('open');
                    btn.setAttribute('aria-expanded', 'true');
                    
                    // Set height to scrollHeight for smooth transition
                    const inner = body.querySelector('.idx-faq-body__inner');
                    const height = inner ? inner.offsetHeight : 200;
                    body.style.maxHeight = `${height + 32}px`;
                }
            });
        });
    }

    // ── Fade-In Reveal Observer for sections (support index.css fade-in-up class) ──
    function initIndexFadeInReveal() {
        const revealItems = document.querySelectorAll('.fade-in-up');
        if (!revealItems.length || !('IntersectionObserver' in window)) return;

        const obs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.05, rootMargin: '0px 0px -20px 0px' });

        revealItems.forEach(item => obs.observe(item));
    }

    // Initialize home page features
    initHeroTypewriter();
    initHeroParallax();
    initStatCounters();
    initFAQAccordion();
    initIndexFadeInReveal();
});
