-- ═══════════════════════════════════════════════════════════
-- AMA — seed_employees.sql
-- Datos iniciales de empleados (FICTICIOS) para la tabla employees.
-- Ningún dato corresponde a personas reales.
-- ═══════════════════════════════════════════════════════════

SET NAMES utf8mb4;

INSERT IGNORE INTO employees (id, nombre, apellido, puesto, departamento, email, telefono, foto_path, jerarquia) VALUES
(1,  'Diego',     'Funes',     'Gerente',                                'Gerencia General', 'gerencia@ama-demo.example',     '+54 11 4000-0000 Interno: 201',       NULL, 1),
(3,  'Paula',     'Ibarra',    'Recepcion & Consultas Generales',        'Recepcion',        'recepcion@ama-demo.example',    '+54 11 4000-0000 Interno: 200 - 251', NULL, 1),
(4,  'Hernan',    'Robledo',   'Gerente de Finanzas',                    'Finanzas',         'finance@ama-demo.example',      '+54 11 4000-0000 Interno: 207',       NULL, 1),
(5,  'Lucia',     'Bravo',     'Recursos Humanos',                       'Finanzas',         'rrhh@ama-demo.example',         '+54 11 4000-0000 Interno: 206',       NULL, 2),
(6,  'Mariana',   'Acosta',    'Contabilidad & Finanzas',                'Finanzas',         'finance@ama-demo.example',      '+54 11 4000-0000 Interno: 220',       NULL, 4),
(7,  'Nadia',     'Mora',      'Administracion & Finanzas',              'Finanzas',         'finance@ama-demo.example',      '+54 11 4000-0000 Interno: 217',       NULL, 5),
(8,  'Tomas',     'Rios',      'Administracion & Finanzas',              'Finanzas',         'finance@ama-demo.example',      '+54 11 4000-0000 Interno: 216',       NULL, 5),
(9,  'Julian',    'Castro',    'Caja & Finanzas',                        'Finanzas',         'finance@ama-demo.example',      '+54 11 4000-0000 Interno: 218',       NULL, 3),
(10, 'Carlos',    'Vega',      'Caja & Finanzas',                        'Finanzas',         'finance@ama-demo.example',      '+54 11 4000-0000 Interno: 235',       NULL, 3),
(11, 'Romina',    'Vera',      'Customer Service',                       'Comercial',        'customerserv@ama-demo.example', '+54 11 4000-0000 Interno: 227',       NULL, 4),
(12, 'Tobias',    'Juarez',    'Carga Proyecto',                         'Comercial',        'project@ama-demo.example',      '+54 11 4000-0000 Interno: 230',       NULL, 4),
(13, 'Marcos',    'Leiva',     'Contenedores',                           'Comercial',        'containers@ama-demo.example',   '+54 11 4000-0000 Interno: 228',       NULL, 4),
(14, 'Pablo',     'Gimenez',   'Contenedores',                           'Comercial',        'containers@ama-demo.example',   '+54 11 4000-0000 Interno: 229',       NULL, 4),
(15, 'Valentina', 'Molina',    'Contenedores',                           'Comercial',        'containers@ama-demo.example',   '+54 11 4000-0000 Interno: 237',       NULL, 4),
(16, 'Carlos',    'Arce',      'Port Capitan - Senior',                  'Operaciones',      'oper@ama-demo.example',         '+54 11 4000-0000 Interno: 202',       NULL, 3),
(17, 'Sebastian', 'Ortiz',     'Port Capitan - Operaciones Portuarias',  'Operaciones',      'oper@ama-demo.example',         '+54 11 4000-0000 Interno: 225',       NULL, 4),
(18, 'Lucas',     'Zarate',    'Port Capitan - Operaciones Portuarias',  'Operaciones',      'oper@ama-demo.example',         '+54 11 4000-0000 Interno: 226',       NULL, 4),
(19, 'Federico',  'Toledo',    'Operaciones',                            'Operaciones',      'oper@ama-demo.example',         '+54 11 4000-0000 Interno: 302',       NULL, 4),
(20, 'Martin',    'Duarte',    'Documentacion Importacion',              'Documentacion',    'doc@ama-demo.example',          '+54 11 4000-0000 Interno: 301',       NULL, 4),
(21, 'Miguel',    'Arias',     'Documentacion Importacion',              'Documentacion',    'doc@ama-demo.example',          '+54 11 4000-0000 Interno: 300',       NULL, 4),
(22, 'Joaquin',   'Ramos',     'Documentacion Exportacion',              'Documentacion',    'doc@ama-demo.example',          '+54 11 4000-0000 Interno: 244',       NULL, 4),
(23, 'Gustavo',   'Urquiza',   'Documentacion Exportacion',              'Documentacion',    'doc@ama-demo.example',          '+54 11 4000-0000 Interno: 246',       NULL, 4),
(24, 'Alejandro', 'Delfino',   'Equipment & Claims',                     'Equipment',        'equipment@ama-demo.example',    '+54 11 4000-0000 Interno: 231',       NULL, 4),
(25, 'Raul',      'Navarro',   'Equipment & Claims',                     'Equipment',        'equipment@ama-demo.example',    '+54 11 4000-0000 Interno: 232',       NULL, 4);
