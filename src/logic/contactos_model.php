<?php
/**
 * AMA — contactos_model.php
 * Configuración del directorio institucional por área de trabajo.
 *
 * Directorio de áreas, no de personas: no hay datos de empleados acá.
 * Único origen de verdad de los buzones e internos del sitio: lo consumen
 * src/pages/contacto.php, src/pages/servicios.php y src/components/footer.php,
 * para que una casilla se cambie en un solo lugar.
 */

/**
 * Línea principal de la agencia. Los internos del directorio son extensiones
 * de este número: no son teléfonos propios.
 */
const AMA_TEL = '+541140000000';

/**
 * Pausa entre el número y el interno en un enlace tel:.
 *
 * Cada coma es una pausa de ~2 segundos antes de marcar el interno por tono,
 * que es lo que entienden los discadores de iOS y Android. Dos comas (~4s) dan
 * tiempo a que la central atienda antes de enviar la extensión. Si la central
 * tarda más o menos en atender, se ajusta acá y cambia en todo el sitio.
 */
const AMA_TEL_PAUSA = ',,';

if (!function_exists('contactos_orden_departamentos')) {

    /** Orden jerárquico institucional de los departamentos. */
    function contactos_orden_departamentos(): array {
        return [
            'Recepcion',
            'Comercial',
            'Operaciones',
            'Documentacion',
            'Equipment',
            'Finanzas',
        ];
    }

    /**
     * Canales directos (buzones funcionales) por área.
     * Cada canal: ['label' => clave i18n, 'email' => casilla, 'int' => interno].
     *
     * 'int' es opcional: Recepción y Customer Service atienden por la línea
     * general, y RRHH no tiene interno publicado.
     */
    function contactos_canales_area(): array {
        return [
            // Recepción absorbió a Gerencia General: ama@ es la casilla
            // principal de entrada a la agencia, institucional incluido.
            'Recepcion' => [
                ['label' => 'contact.ch_recepcion', 'email' => 'info@atlantic-agency.example.com'],
            ],
            'Comercial' => [
                ['label' => 'contact.ch_cs',          'email' => 'customerserv@atlantic-agency.example.com'],
                ['label' => 'contact.ch_contenedores', 'email' => 'containers@atlantic-agency.example.com', 'int' => ['500']],
                ['label' => 'contact.ch_projects',     'email' => 'projects@atlantic-agency.example.com',   'int' => ['504']],
                ['label' => 'contact.ch_autos',        'email' => 'cars.vans@atlantic-agency.example.com',  'int' => ['503']],
            ],
            'Operaciones' => [
                ['label' => 'contact.ch_operaciones', 'email' => 'oper@atlantic-agency.example.com', 'int' => ['600']],
            ],
            // Documentación trabaja separada por sentido de la operación.
            'Documentacion' => [
                ['label' => 'contact.ch_doc_impo', 'email' => 'doc.impo@atlantic-agency.example.com', 'int' => ['700']],
                ['label' => 'contact.ch_doc_expo', 'email' => 'doc.expo@atlantic-agency.example.com', 'int' => ['702']],
            ],
            // Equipment y Claims son dos casillas de un mismo equipo: comparten
            // el interno 800.
            'Equipment' => [
                ['label' => 'contact.ch_equipment', 'email' => 'equipment@atlantic-agency.example.com', 'int' => ['800']],
                ['label' => 'contact.ch_claims',    'email' => 'claims@atlantic-agency.example.com',    'int' => ['800']],
            ],
            // finance@ es la casilla paraguas del área; las otras tres son los
            // buzones concretos en los que se trabaja el día a día.
            'Finanzas' => [
                ['label' => 'contact.ch_finanzas',    'email' => 'finance@atlantic-agency.example.com',     'int' => ['301', '302']],
                ['label' => 'contact.ch_tesoreria',   'email' => 'tesoreria@atlantic-agency.example.com',   'int' => ['400']],
                ['label' => 'contact.ch_proveedores', 'email' => 'proveedores@atlantic-agency.example.com', 'int' => ['300']],
                ['label' => 'contact.ch_rrhh',        'email' => 'rrhh@atlantic-agency.example.com'],
            ],
        ];
    }

    /**
     * Descripción breve de qué resuelve cada área (fallback en español,
     * antes de que cargue la traducción vía contact.dept_desc.<slug>).
     */
    function contactos_descripcion_area(): array {
        return [
            'Recepcion'     => 'Primer contacto con la agencia, consultas generales y vínculo institucional con Atlantic Maritime Group.',
            'Comercial'     => 'Cotizaciones y atención comercial por tipo de carga: contenedores, carga proyecto y Ro-Ro.',
            'Operaciones'   => 'Coordinación operativa y portuaria de los buques de la red.',
            'Documentacion' => 'Gestión documental de las operaciones, con un buzón por sentido: importación y exportación.',
            'Equipment'     => 'Control de contenedores y equipos, y gestión de reclamos. Ambos buzones los atiende el mismo equipo.',
            'Finanzas'      => 'Facturación, gastos locales y transferencias por Tesorería; pagos a proveedores y servicios por Proveedores; recursos humanos por RRHH.',
        ];
    }

    /**
     * Enlace tel: que marca la línea principal y después el interno por tono.
     * Sin interno devuelve el número pelado.
     */
    function contactos_tel_href(?string $interno = null): string {
        if ($interno === null || $interno === '') {
            return 'tel:' . AMA_TEL;
        }
        return 'tel:' . AMA_TEL . AMA_TEL_PAUSA . preg_replace('/\D+/', '', $interno);
    }

    /** Número principal en formato legible. */
    function contactos_tel_legible(): string {
        return '+54 11 4000-0000';
    }

    /**
     * Casilla principal de un área (la primera de su lista), para quien
     * necesita un único destinatario y no el listado completo.
     */
    function contactos_casilla_principal(string $dept): string {
        $canales = contactos_canales_area()[$dept] ?? [];
        return $canales[0]['email'] ?? 'info@atlantic-agency.example.com';
    }

    /** Slug del departamento (clave i18n departments.<slug>). */
    function getDeptKey(string $dept): string {
        $map = [
            'Recepcion'     => 'recepcion',
            'Comercial'     => 'comercial',
            'Operaciones'   => 'operaciones',
            'Documentacion' => 'documentacion',
            'Equipment'     => 'equipment',
            'Finanzas'      => 'finanzas',
        ];
        return $map[$dept] ?? strtolower(trim(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $dept), '-'));
    }
}
