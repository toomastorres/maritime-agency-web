/**
 * AMA — contacto.js
 * Filtro de departamentos para el directorio de contactos (renderizado server-side).
 * No hay fetch/AJAX — los empleados los genera PHP directamente en el HTML.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Department filter buttons
    document.querySelectorAll('[data-filter-dept]').forEach(btn => {
        btn.addEventListener('click', () => {
            const dept = btn.dataset.filterDept;

            // Update active state
            document.querySelectorAll('[data-filter-dept]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Show/hide department groups
            document.querySelectorAll('.dept-group').forEach(g => {
                if (dept === 'todos' || g.dataset.dept === dept) {
                    g.style.display = '';
                } else {
                    g.style.display = 'none';
                }
            });
        });
    });
});
