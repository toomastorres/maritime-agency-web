-- ═══════════════════════════════════════════════════════════
-- AMA — seed_demo.sql (SOLO PARA ENTORNO LOCAL / DOCKER DEMO)
-- ═══════════════════════════════════════════════════════════
-- Datos 100% ficticios para levantar el entorno de demostración con
-- `docker compose up --build` y visualizar cotizaciones vigentes,
-- schedules de buques, novedades y vacantes desde el primer arranque.
-- NO importar en entornos productivos.
-- ═══════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- 1. Usuario administrador de demostración local (break-glass habilitado para demo sin Entra ID)
--    Usuario: admin_demo  |  Clave: DemoPuertoAtlantico2026!
INSERT INTO usuarios_admin (
    id, nombre, username, email, password_hash, rol_portal, auth_provider, is_break_glass, is_active
) VALUES (
    1,
    'Administrador Demo',
    'admin_demo',
    'admin.demo@atlantic-agency.example.com',
    '$2y$10$0cyb4oJR6Dv6P9SW3V5HbeU3j8JWwir9tiJJMTUK8hh5a0gdzLNHS',
    'admin_global',
    'local',
    1,
    1
);

-- 2. Tipo de cambio operativo del día (USD y EUR)
INSERT INTO exchange_rates (moneda, valor, fecha_cotizacion, usuario_id) VALUES
    ('USD', 1185.5000, CURDATE(), 1),
    ('EUR', 1290.7500, CURDATE(), 1);

-- 3. Datos bancarios ficticios para cobros locales
INSERT INTO bank_details (id, contenido_html, actualizado_por) VALUES (
    1,
    '<p><strong>Razón Social:</strong> Atlantic Maritime Agency S.A.<br><strong>CUIT:</strong> 30-00000000-0<br><strong>Banco:</strong> Banco Atlántico S.A. — Sucursal Puerto Madero<br><strong>Cuenta Corriente en Pesos:</strong> 000-0123456/7<br><strong>CBU:</strong> 0000000000000000000000<br><strong>Alias:</strong> AMA.DEMO.PUERTO</p>',
    1
);

-- 4. Schedule de demostración (Norte de Europa — Costa Este de Sudamérica)
INSERT INTO schedule_imports (id, service_name, week_number, hash, archivo_original, subido_por) VALUES (
    1,
    'North Europe — South America Ro-Ro / Multipurpose Express',
    42,
    'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    'demo_schedule_wk42.xlsx',
    1
);

INSERT INTO schedule_port_calls (
    import_id, vessel_name, voyage_code, port_name, eta, ets, is_feeder, feeder_code, port_order, vessel_order, block_number, leg
) VALUES
    (1, 'MV ATLANTIC HORIZON', '0426S', 'Hamburg',      DATE_ADD(CURDATE(), INTERVAL -14 DAY), DATE_ADD(CURDATE(), INTERVAL -13 DAY), 0, NULL, 1, 1, 1, 'outbound'),
    (1, 'MV ATLANTIC HORIZON', '0426S', 'Antwerp',      DATE_ADD(CURDATE(), INTERVAL -11 DAY), DATE_ADD(CURDATE(), INTERVAL -10 DAY), 0, NULL, 2, 1, 1, 'outbound'),
    (1, 'MV ATLANTIC HORIZON', '0426S', 'Santos',       DATE_ADD(CURDATE(), INTERVAL 4 DAY),   DATE_ADD(CURDATE(), INTERVAL 5 DAY),   0, NULL, 3, 1, 1, 'outbound'),
    (1, 'MV ATLANTIC HORIZON', '0426S', 'Zárate',       DATE_ADD(CURDATE(), INTERVAL 8 DAY),   DATE_ADD(CURDATE(), INTERVAL 9 DAY),   0, NULL, 4, 1, 1, 'outbound'),
    (1, 'MV ATLANTIC HORIZON', '0426S', 'Buenos Aires', DATE_ADD(CURDATE(), INTERVAL 10 DAY),  DATE_ADD(CURDATE(), INTERVAL 11 DAY),  0, NULL, 5, 1, 1, 'outbound'),
    (1, 'MV ATLANTIC HORIZON', '0426S', 'Montevideo',   DATE_ADD(CURDATE(), INTERVAL 12 DAY),  DATE_ADD(CURDATE(), INTERVAL 13 DAY),  0, NULL, 6, 1, 1, 'outbound'),
    (1, 'MV OCEAN MERIDIAN',   '0427S', 'Hamburg',      DATE_ADD(CURDATE(), INTERVAL -2 DAY),  DATE_ADD(CURDATE(), INTERVAL -1 DAY),  0, NULL, 1, 2, 1, 'outbound'),
    (1, 'MV OCEAN MERIDIAN',   '0427S', 'Antwerp',      DATE_ADD(CURDATE(), INTERVAL 1 DAY),   DATE_ADD(CURDATE(), INTERVAL 2 DAY),   0, NULL, 2, 2, 1, 'outbound'),
    (1, 'MV OCEAN MERIDIAN',   '0427S', 'Santos',       DATE_ADD(CURDATE(), INTERVAL 16 DAY),  DATE_ADD(CURDATE(), INTERVAL 17 DAY),  0, NULL, 3, 2, 1, 'outbound'),
    (1, 'MV OCEAN MERIDIAN',   '0427S', 'Zárate',       DATE_ADD(CURDATE(), INTERVAL 20 DAY),  DATE_ADD(CURDATE(), INTERVAL 21 DAY),  0, NULL, 4, 2, 1, 'outbound'),
    (1, 'MV OCEAN MERIDIAN',   '0427S', 'Buenos Aires', DATE_ADD(CURDATE(), INTERVAL 22 DAY),  DATE_ADD(CURDATE(), INTERVAL 23 DAY),  0, NULL, 5, 2, 1, 'outbound'),
    (1, 'MV OCEAN MERIDIAN',   '0427S', 'Montevideo',   DATE_ADD(CURDATE(), INTERVAL 24 DAY),  DATE_ADD(CURDATE(), INTERVAL 25 DAY),  0, NULL, 6, 2, 1, 'outbound');

-- 5. Novedades institucionales y operativas
INSERT INTO novedades (
    titulo, bajada, cuerpo, categoria, autor, destacada, etiquetas, imagen_url, fecha_iso, publicado_por
) VALUES
    (
        'Optimización digital en consultas de Tipo de Cambio operativo',
        'Nuevo canal automatizado de atención para clientes y despachantes con respuesta inmediata las 24 horas.',
        'Incorporamos atención automática sobre nuestra casilla dedicada de tipo de cambio, conectada en tiempo real con la pizarra operativa publicada por Caja.',
        'Operaciones',
        'Comunicaciones AMA',
        1,
        '["Operaciones", "Tipo de Cambio", "Digitalización"]',
        NULL,
        CURDATE(),
        1
    ),
    (
        'Frecuencia quincenal reforzada en el corredor Atlántico Norte — Río de la Plata',
        'Mayor capacidad para carga rodante (Ro-Ro), contenedores y carga proyecto en las terminales de Zárate y Buenos Aires.',
        'La programación actualizada del trimestre incorpora ventanas operativas optimizadas para unidades rodantes, maquinaria pesada y carga contenedorizada.',
        'Servicios',
        'Comunicaciones AMA',
        0,
        '["Ro-Ro", "Schedules", "Comercio Exterior"]',
        NULL,
        DATE_SUB(CURDATE(), INTERVAL 5 DAY),
        1
    );

-- 6. Búsquedas activas de Recursos Humanos
INSERT INTO vacancies (
    titulo, departamento, ubicacion, descripcion, requisitos, activo, estado
) VALUES
    (
        'Analista de Documentación Marítima (Importación / Exportación)',
        'Documentación',
        'Puerto Madero, CABA (Híbrido)',
        'Gestión integral de conocimientos de embarque (B/L), manifiestos aduaneros y coordinación documental con terminales portuarias y agentes del exterior.',
        'Experiencia mínima de 2 años en agencias marítimas o forwarders, manejo de sistemas aduaneros (Malvina) e inglés técnico intermedio/avanzado.',
        1,
        1
    ),
    (
        'Ejecutivo de Customer Service — Tráfico Ro-Ro & Contenedores',
        'Customer Service',
        'Puerto Madero, CABA',
        'Seguimiento operativo de embarques, atención a importadores/exportadores, avisos de arribo y coordinación de liberaciones junto con Caja y Operaciones.',
        'Formación en Comercio Internacional o Logística, vocación de servicio al cliente e idioma inglés fluido.',
        1,
        1
    );
