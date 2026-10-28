/* ═══════════════════════════════════════════════════════════
 * AMA — admin-password.js
 * Medidor de fuerza de contraseña (mejora progresiva).
 *
 * Se activa en cualquier <input data-pw-meter>. Crea (si no existe)
 * una barra .pw-meter justo después del input y la actualiza al tipear.
 * Es solo orientativo en el cliente: la validación real es server-side
 * (password_policy.php). Si el JS no carga, el formulario sigue funcionando.
 * ═══════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    // Inyectar estilos una sola vez
    if (!document.getElementById('pw-meter-styles')) {
        var css = '' +
            '.pw-meter{height:4px;border-radius:0;background:rgba(128,128,131,.25);margin-top:.55rem;overflow:hidden}' +
            '.pw-meter > span{display:block;height:100%;width:0;border-radius:0;transition:width .25s ease,background .25s ease}' +
            '.pw-meter-label{font-size:.72rem;font-weight:600;margin-top:.3rem;letter-spacing:.2px}';
        var style = document.createElement('style');
        style.id = 'pw-meter-styles';
        style.textContent = css;
        document.head.appendChild(style);
    }

    var LEVELS = [
        { label: 'Muy débil', color: '#B3261E', width: '20%' },
        { label: 'Débil',     color: '#B3261E', width: '40%' },
        { label: 'Aceptable', color: '#8a5a00', width: '60%' },
        { label: 'Fuerte',    color: '#0069CC', width: '80%' },
        { label: 'Excelente', color: '#0F396C', width: '100%' }
    ];

    function score(pw) {
        if (!pw) return -1;
        var s = 0;
        if (pw.length >= 12) s++;
        if (pw.length >= 16) s++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
        if (/\d/.test(pw)) s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        // Penalizar repeticiones triviales o secuencias obvias
        if (/(.)\1{3,}/.test(pw) || /1234|qwerty|password|ama/i.test(pw)) s = Math.max(0, s - 2);
        return Math.min(s, 4);
    }

    function attach(input) {
        if (input.dataset.pwMeterReady) return;
        input.dataset.pwMeterReady = '1';

        var meter = input.parentElement && input.parentElement.parentElement
            ? input.closest('.login-field, .form-group, .perfil-form-group, div').querySelector('.pw-meter')
            : null;
        if (!meter) {
            meter = document.createElement('div');
            meter.className = 'pw-meter';
            meter.innerHTML = '<span></span>';
            input.parentNode.insertBefore(meter, input.nextSibling);
        }
        var bar = meter.querySelector('span') || meter.appendChild(document.createElement('span'));

        var label = meter.parentNode.querySelector('.pw-meter-label');
        if (!label) {
            label = document.createElement('div');
            label.className = 'pw-meter-label';
            meter.parentNode.insertBefore(label, meter.nextSibling);
        }

        function update() {
            var sc = score(input.value);
            if (sc < 0) {
                bar.style.width = '0';
                label.textContent = '';
                return;
            }
            var lvl = LEVELS[sc];
            bar.style.width = lvl.width;
            bar.style.background = lvl.color;
            label.textContent = 'Seguridad: ' + lvl.label;
            label.style.color = lvl.color;
        }

        input.addEventListener('input', update);
        update();
    }

    function init() {
        var inputs = document.querySelectorAll('input[data-pw-meter]');
        for (var i = 0; i < inputs.length; i++) attach(inputs[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
