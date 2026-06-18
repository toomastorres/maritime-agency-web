/**
 * AMA — procedimientos.js
 * Lógica interactiva para la Guía de Procedimientos:
 * 1. Filtrado dinámico de tarjetas por categoría con transiciones premium.
 * 2. Smart Routing i18n: Generación en tiempo real de URLs mailto en el idioma activo.
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // ── Elementos del DOM ──────────────────────────────────────────────────
    const filterPills = document.querySelectorAll('.filter-pill');
    const procedureCards = document.querySelectorAll('.procedure-card');
    const langButtons = document.querySelectorAll('[data-lang]');

    // ── 1. Filtrado Dinámico de Tarjetas ───────────────────────────────────
    if (filterPills.length > 0 && procedureCards.length > 0) {
        filterPills.forEach(pill => {
            pill.addEventListener('click', () => {
                // Quitar clase active de todas las píldoras y asignarla a la seleccionada
                filterPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');

                const selectedCategory = pill.getAttribute('data-category');

                // Filtrar las tarjetas aplicando la transición visual
                procedureCards.forEach(card => {
                    const cardCategory = card.getAttribute('data-category');

                    if (selectedCategory === 'all' || cardCategory === selectedCategory) {
                        // Mostrar tarjeta: quitar clase de ocultamiento
                        card.classList.remove('is-hidden');
                    } else {
                        // Ocultar tarjeta: añadir clase de ocultamiento
                        card.classList.add('is-hidden');
                    }
                });
            });
        });
    }

    // ── 2. Smart Routing i18n: URLs mailto Dinámicas ───────────────────────
    function updateMailtoLinks() {
        // Verificar que el motor de i18n esté inicializado y disponible
        if (typeof AMAI18n === 'undefined' || typeof AMAI18n.t !== 'function') {
            // Reintentar en 50ms si aún no se ha cargado el script i18n
            setTimeout(updateMailtoLinks, 50);
            return;
        }

        const mailtoLinks = document.querySelectorAll('.mailto-link');
        
        mailtoLinks.forEach(link => {
            const email = link.getAttribute('data-email');
            const subjectKey = link.getAttribute('data-subject-key');
            const bodyKey = link.getAttribute('data-body-key');

            if (!email || !subjectKey || !bodyKey) return;

            // Obtener las traducciones en tiempo real
            let subject = AMAI18n.t(subjectKey);
            let body = AMAI18n.t(bodyKey);

            // Fallback si la clave de i18n fallara o no existiera
            if (subject === subjectKey) {
                // Si retorna la misma clave, significa que no se encontró en el JSON
                console.warn(`[i18n Mailto] Key not found: ${subjectKey}`);
                return;
            }

            // Sanitizar o decodificar si estuvieran con HTML entities
            // (El subject y body del JSON ya están limpios, pero en el body se usan saltos de línea codificados como %0A)
            
            // Decodificar el body primero si vino con secuencias escapadas del JSON (por ej. %0A),
            // para que encodeURIComponent no lo doble-escape.
            let decodedBody = body;
            try {
                decodedBody = decodeURIComponent(body);
            } catch (e) {
                // Ignorar error y usar crudo si no está codificado
            }

            // Codificar de forma segura para mailto
            const encodedSubject = encodeURIComponent(subject);
            const encodedBody = encodeURIComponent(decodedBody);

            // Asignar el enlace completo final
            link.setAttribute('href', `mailto:${email}?subject=${encodedSubject}&body=${encodedBody}`);
        });
    }

    // Inicializar los enlaces mailto al cargar la página
    updateMailtoLinks();

    // ── 3. Escuchar Cambios de Idioma para Recalcular mailto ───────────────
    if (langButtons.length > 0) {
        langButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // Dar un breve tiempo (150ms) para que i18n.js complete la descarga del JSON y actualice el DOM
                setTimeout(() => {
                    updateMailtoLinks();
                    console.log(`[Smart Routing] Mailto URLs updated to: ${AMAI18n.getCurrentLang().toUpperCase()}`);
                }, 150);
            });
        });
    }
});
