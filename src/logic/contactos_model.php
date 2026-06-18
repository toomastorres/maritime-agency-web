<?php
/**
 * AMA — contactos_model.php
 * Único origen de verdad del modelo del directorio de contactos.
 *
 * Lo usan tanto la página pública (src/pages/contacto.php) como la API
 * (src/logic/api/contactos.php) para que el orden, los canales de área y
 * los referentes nunca se desincronicen entre sí.
 */

if (!function_exists('contactos_orden_departamentos')) {

    /** Orden jerárquico institucional de los departamentos. */
    function contactos_orden_departamentos(): array {
        return [
            'Gerencia General',
            'Recepcion',
            'Finanzas',
            'Operaciones',
            'Comercial',
            'Documentacion',
            'Equipment',
        ];
    }

    /** Prioridad de los puestos dentro de cada departamento (1 = primero). */
    function contactos_orden_puestos(): array {
        return [
            // Gerencia / Recepción (rol único)
            'Gerente'                               => 1,
            'Recepcion & Consultas Generales'       => 1,
            // Finanzas: Gerente → RRHH → Contabilidad → Caja → Analista
            'Gerente de Finanzas'                   => 1,
            'Recursos Humanos'                      => 2,
            'Contabilidad & Finanzas'               => 3,
            'Caja & Finanzas'                       => 4,
            'Administracion & Finanzas'             => 5,
            // Operaciones: Port Captain → Operaciones
            'Port Capitan - Senior'                 => 1,
            'Port Capitan - Operaciones Portuarias' => 2,
            'Operaciones'                           => 3,
            // Comercial: agrupado por rubro
            'Customer Service'                      => 1,
            'Carga Proyecto'                        => 2,
            'Contenedores - Impo'                   => 3,
            'Contenedores - Expo'                   => 4,
            // Documentación: Impo → Expo
            'Documentacion Importacion'             => 1,
            'Documentacion Exportacion'             => 2,
            // Equipment & Claims
            'Equipment & Claims'                    => 1,
        ];
    }

    /**
     * Canales directos (buzones funcionales) por área.
     * Cada canal: ['label' => clave i18n, 'email' => casilla].
     * Gerencia General no lleva canal (el contacto directo es el gerente).
     * Comercial expone una casilla por tipo de carga.
     */
    function contactos_canales_area(): array {
        return [
            'Recepcion'     => [
                ['label' => 'contact.ch_recepcion', 'email' => 'AMA@ama-demo.example'],
            ],
            'Finanzas'      => [
                ['label' => 'departments.finanzas', 'email' => 'finance@ama-demo.example'],
            ],
            'Operaciones'   => [
                ['label' => 'departments.operaciones', 'email' => 'oper@ama-demo.example'],
            ],
            'Comercial'     => [
                ['label' => 'positions.customer_service', 'email' => 'customerserv@ama-demo.example'],
                ['label' => 'contact.ch_autos',           'email' => 'cars.vans@ama-demo.example'],
                ['label' => 'positions.carga_proyecto',   'email' => 'project@ama-demo.example'],
                ['label' => 'contact.ch_contenedores',    'email' => 'containers@ama-demo.example'],
            ],
            'Documentacion' => [
                ['label' => 'departments.documentacion', 'email' => 'doc@ama-demo.example'],
            ],
            'Equipment'     => [
                ['label' => 'departments.equipment', 'email' => 'equipment@ama-demo.example'],
            ],
        ];
    }

    /** Puestos cuyos titulares (referentes) exponen su email PERSONAL. */
    function contactos_puestos_email_personal(): array {
        return [
            'Gerente',                // Gerente General
            'Gerente de Finanzas',
            'Recursos Humanos',
            'Port Capitan - Senior',
        ];
    }

    /** Slug del departamento (clave i18n departments.<slug>). */
    function getDeptKey(string $dept): string {
        $map = [
            'Gerencia General' => 'gerencia-general',
            'Recepcion'        => 'recepcion',
            'Finanzas'         => 'finanzas',
            'Comercial'        => 'comercial',
            'Operaciones'      => 'operaciones',
            'Documentacion'    => 'documentacion',
            'Equipment'        => 'equipment',
        ];
        return $map[$dept] ?? strtolower(trim(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $dept), '-'));
    }

    /** Slug del puesto (clave i18n positions.<slug>). */
    function getPositionKey(string $puesto): string {
        $map = [
            'Gerente'                                => 'gerente',
            'Recepcion & Consultas Generales'        => 'recepcion_consultas_generales',
            'Gerente de Finanzas'                    => 'gerente_de_finanzas',
            'Recursos Humanos'                       => 'recursos_humanos',
            'Contabilidad & Finanzas'                => 'contabilidad_finanzas',
            'Administracion & Finanzas'              => 'administracion_finanzas',
            'Caja & Finanzas'                        => 'caja_finanzas',
            'Customer Service'                       => 'customer_service',
            'Carga Proyecto'                         => 'carga_proyecto',
            'Contenedores - Expo'                    => 'contenedores_expo',
            'Contenedores - Impo'                    => 'contenedores_impo',
            'Port Capitan - Senior'                  => 'port_capitan_senior',
            'Port Capitan - Operaciones Portuarias'  => 'port_capitan_operaciones_portuarias',
            'Operaciones'                            => 'operaciones',
            'Documentacion Importacion'              => 'documentacion_importacion',
            'Documentacion Exportacion'              => 'documentacion_exportacion',
            'Equipment & Claims'                     => 'equipment_claims',
        ];
        return $map[$puesto] ?? strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $puesto), '_'));
    }

    /**
     * Agrupa y ordena empleados por el modelo institucional.
     * Devuelve un array asociativo [departamento => [empleados ordenados]],
     * con los departamentos en orden jerárquico.
     */
    function contactos_agrupar_y_ordenar(array $rows): array {
        $ordenDepartamentos = contactos_orden_departamentos();
        $ordenPuestos       = contactos_orden_puestos();

        $empleados = [];
        foreach ($rows as $e) {
            $dept = $e['departamento'] ?? 'General';
            $empleados[$dept][] = $e;
        }

        foreach ($empleados as $dept => &$lista) {
            usort($lista, function ($a, $b) use ($ordenPuestos) {
                $pa = $ordenPuestos[$a['puesto'] ?? ''] ?? 99;
                $pb = $ordenPuestos[$b['puesto'] ?? ''] ?? 99;
                if ($pa !== $pb) return $pa - $pb;
                return strcmp($a['apellido'] ?? '', $b['apellido'] ?? '');
            });
        }
        unset($lista);

        uksort($empleados, function ($a, $b) use ($ordenDepartamentos) {
            $pa = array_search($a, $ordenDepartamentos, true);
            $pb = array_search($b, $ordenDepartamentos, true);
            $pa = $pa === false ? 99 : $pa;
            $pb = $pb === false ? 99 : $pb;
            return $pa - $pb;
        });

        return $empleados;
    }
}
