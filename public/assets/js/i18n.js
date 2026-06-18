/**
 * AMA — i18n.js
 * Motor de internacionalización: traducciones JSON, detección de idioma, reemplazo DOM.
 */

const AMAI18n = (function () {
    'use strict';

    const SUPPORTED_LANGS = ['es', 'en', 'it', 'pt'];
    const DEFAULT_LANG = 'es';
    const STORAGE_KEY = 'AMA_lang';

    let currentLang = DEFAULT_LANG;
    let translations = {};
    let basePath = '';

    /**
     * Detect the base path for i18n files relative to the current page
     */
    function detectBasePath() {
        const logoLink = document.querySelector('.header-logo a');
        if (logoLink) {
            basePath = logoLink.getAttribute('href');
            if (!basePath.endsWith('/')) basePath += '/';
        } else {
            basePath = '/';
        }
    }

    /**
     * Detect the user's preferred language
     */
    function detectLanguage() {
        // 1. URL parameter takes priority
        const urlParams = new URLSearchParams(window.location.search);
        const urlLang = urlParams.get('lang');
        if (urlLang && SUPPORTED_LANGS.includes(urlLang)) {
            return urlLang;
        }

        // 2. Check localStorage
        const storedLang = localStorage.getItem(STORAGE_KEY);
        if (storedLang && SUPPORTED_LANGS.includes(storedLang)) {
            return storedLang;
        }

        // 3. Browser language
        const browserLang = navigator.language.split('-')[0];
        if (SUPPORTED_LANGS.includes(browserLang)) {
            return browserLang;
        }

        return DEFAULT_LANG;
    }

    /**
     * Fetch translation JSON file
     */
    async function loadTranslations(lang) {
        try {
            const response = await fetch(`${basePath}src/i18n/${lang}.json`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch (error) {
            console.warn(`[i18n] Failed to load ${lang}.json, falling back to ${DEFAULT_LANG}`);
            if (lang !== DEFAULT_LANG) {
                const fallback = await fetch(`${basePath}src/i18n/${DEFAULT_LANG}.json`);
                return await fallback.json();
            }
            return {};
        }
    }

    /**
     * Get a nested value from an object using dot notation
     * e.g., getNestedValue(obj, 'hero.title') -> obj.hero.title
     */
    function getNestedValue(obj, path) {
        return path.split('.').reduce((current, key) => {
            return current && current[key] !== undefined ? current[key] : null;
        }, obj);
    }

    /**
     * Apply translations to all DOM elements with [data-i18n]
     */
    function applyTranslations() {
        // Text content
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            const value = getNestedValue(translations, key);
            if (value) {
                // Solo permitir HTML en elementos marcados explícitamente
                if (value.includes('<') && el.hasAttribute('data-i18n-html')) {
                    // Sanitizar: solo permitir tags seguros
                    const tmp = document.createElement('div');
                    tmp.innerHTML = value;
                    tmp.querySelectorAll('script,iframe,object,embed,form,input,style,link,meta').forEach(n => n.remove());
                    tmp.querySelectorAll('*').forEach(n => {
                        for (const attr of [...n.attributes]) {
                            if (attr.name.startsWith('on') || attr.name === 'srcdoc') n.removeAttribute(attr.name);
                            if (attr.name === 'href' && attr.value.trim().toLowerCase().startsWith('javascript:')) n.removeAttribute(attr.name);
                        }
                    });
                    el.innerHTML = tmp.innerHTML;
                } else {
                    el.textContent = value;
                }
            }
        });

        // Placeholder attributes
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            const value = getNestedValue(translations, key);
            if (value) el.setAttribute('placeholder', value);
        });

        // Title attributes (for accessibility)
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            const value = getNestedValue(translations, key);
            if (value) el.setAttribute('title', value);
        });

        // Alt attributes for images
        document.querySelectorAll('[data-i18n-alt]').forEach(el => {
            const key = el.getAttribute('data-i18n-alt');
            const value = getNestedValue(translations, key);
            if (value) el.setAttribute('alt', value);
        });

        // Update HTML lang attribute
        document.documentElement.setAttribute('lang', currentLang);

        // Update meta tags
        updateMetaTags();

        // Update language selector active state
        updateLangSelector();
        
        // Dispatch event for other scripts to update dynamic text
        document.dispatchEvent(new CustomEvent('AMA:lang_updated'));
    }

    /**
     * Update document title and meta tags
     */
    function updateMetaTags() {
        let meta = { ...(translations.meta || {}) };

        // Detect current page to check for page-specific meta
        let path = window.location.pathname;
        let pageName = path.split('/').pop().replace('.php', '') || 'index';

        const pageKeyMap = {
            'tipo-de-cambio': 'exchange',
            'contacto': 'contact',
            'servicios': 'services_page',
            'institucional': 'institutional',
            'procedimientos': 'procedures',
            'schedule': 'schedule',
            'index': 'index'
        };

        const key = pageKeyMap[pageName] || pageName;
        if (translations[key] && translations[key].meta) {
            meta = { ...meta, ...translations[key].meta };
        }

        if (meta.title) {
            document.title = meta.title;
        }

        // Meta description
        let metaDesc = document.querySelector('meta[name="description"]');
        if (metaDesc && meta.description) {
            metaDesc.setAttribute('content', meta.description);
        }

        // OpenGraph
        let ogTitle = document.querySelector('meta[property="og:title"]');
        if (ogTitle && (meta.og_title || meta.title)) {
            ogTitle.setAttribute('content', meta.og_title || meta.title);
        }

        let ogDesc = document.querySelector('meta[property="og:description"]');
        if (ogDesc && (meta.og_description || meta.description)) {
            ogDesc.setAttribute('content', meta.og_description || meta.description);
        }
    }

    /**
     * Update language selector button states
     */
    function updateLangSelector() {
        document.querySelectorAll('.lang-selector button').forEach(btn => {
            const lang = btn.getAttribute('data-lang');
            btn.classList.toggle('active', lang === currentLang);
        });
    }

    /**
     * Switch to a different language
     */
    async function setLanguage(lang) {
        if (!SUPPORTED_LANGS.includes(lang)) return;

        currentLang = lang;
        localStorage.setItem(STORAGE_KEY, lang);
        translations = await loadTranslations(lang);
        applyTranslations();

        // Update URL without reload
        const url = new URL(window.location);
        if (lang === DEFAULT_LANG) {
            url.searchParams.delete('lang');
        } else {
            url.searchParams.set('lang', lang);
        }
        window.history.replaceState({}, '', url);
    }

    /**
     * Initialize the i18n system
     */
    async function init() {
        detectBasePath();
        currentLang = detectLanguage();
        translations = await loadTranslations(currentLang);
        applyTranslations();

        // Bind language selector buttons
        document.querySelectorAll('[data-lang]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                setLanguage(btn.getAttribute('data-lang'));
            });
        });
    }

    // Public API
    return {
        init,
        setLanguage,
        getCurrentLang: () => currentLang,
        t: (key) => getNestedValue(translations, key) || key,
        // updateDOM: re-applies translations to the current DOM.
        // Used by page scripts after they inject dynamic content (contacts, vacancies, etc.)
        updateDOM: applyTranslations,
        SUPPORTED_LANGS
    };
})();
