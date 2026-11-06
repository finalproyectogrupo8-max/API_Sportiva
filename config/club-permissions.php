<?php

/*
|--------------------------------------------------------------------------
| Catálogo de permisos y roles estándar del club
|--------------------------------------------------------------------------
|
| Fuente de verdad de docs/roles-y-permisos.md. `php artisan club:sync-permissions` lo copia a
| las tablas `permissions`, `roles` y `role_permissions` de cada base de club.
|
| - modules: módulo → recursos → acciones. La clave de cada permiso es "<recurso>.<acción>".
|   `scoped` lista las acciones que admiten alcance "sus categorías" (own).
| - roles: los roles estándar (is_system). "*" = todos los permisos con alcance all.
|   Un permiso con ":own" se asigna con alcance "sus categorías".
|
| Cada ABM nuevo agrega acá sus permisos y los suma a los roles estándar que correspondan.
*/

$crud = ['view', 'create', 'update', 'delete'];

return [

    'action_labels' => [
        'view' => 'Ver',
        'create' => 'Crear',
        'update' => 'Editar',
        'delete' => 'Eliminar',
        'manage_players' => 'Armar planteles',
        'manage_coaches' => 'Asignar profes',
        'record' => 'Registrar',
        'approve' => 'Aprobar',
        'deliver' => 'Entregar',
        'adjust' => 'Ajustar',
        'transfer' => 'Transferir',
        'assign' => 'Asignar',
        'invite' => 'Invitar',
        'disable' => 'Deshabilitar',
        'upload' => 'Subir',
        'publish' => 'Publicar',
        'send' => 'Enviar',
    ],

    'modules' => [
        'jugadores' => [
            'label' => 'Jugadores',
            'resources' => [
                'players' => ['label' => 'Jugadores', 'actions' => $crud, 'scoped' => $crud],
                'positions' => ['label' => 'Posiciones por deporte', 'actions' => $crud],
                'categories' => ['label' => 'Planteles', 'actions' => ['manage_players'], 'scoped' => ['manage_players']],
                'player_contacts' => ['label' => 'Contactos y tutores', 'actions' => ['view', 'update'], 'scoped' => ['view', 'update']],
                'player_documents' => ['label' => 'Documentos (apto, ficha, DNI)', 'actions' => ['view', 'upload', 'delete'], 'scoped' => ['view', 'upload']],
            ],
        ],
        'profes' => [
            'label' => 'Profes',
            'resources' => [
                'coaches' => ['label' => 'Profes', 'actions' => $crud, 'scoped' => ['view']],
                'categories' => ['label' => 'Profes por categoría', 'actions' => ['manage_coaches']],
            ],
        ],
        'partidos' => [
            'label' => 'Partidos',
            'resources' => [
                'matches' => ['label' => 'Partidos', 'actions' => $crud, 'scoped' => $crud],
                'trainings' => ['label' => 'Entrenamientos', 'actions' => $crud, 'scoped' => $crud],
                'attendance' => ['label' => 'Asistencia', 'actions' => ['view', 'record'], 'scoped' => ['view', 'record']],
                'callups' => ['label' => 'Convocatorias', 'actions' => ['view', 'update', 'publish'], 'scoped' => ['view', 'update', 'publish']],
            ],
        ],
        'analisis' => [
            'label' => 'Análisis',
            'resources' => [
                'physical_evaluations' => ['label' => 'Evaluaciones físicas', 'actions' => $crud, 'scoped' => $crud],
            ],
        ],
        'competencia' => [
            'label' => 'Proyecto',
            'resources' => [
                'project' => ['label' => 'Objetivos y tabla', 'actions' => ['view', 'update'], 'scoped' => ['view']],
            ],
        ],
        'habilitacion' => [
            'label' => 'Habilitación',
            'resources' => [
                'competitions' => ['label' => 'Competencias y requisitos', 'actions' => $crud, 'scoped' => ['view']],
                'player_requirements' => ['label' => 'Papeles de cada jugador', 'actions' => ['view', 'update', 'approve'], 'scoped' => ['view', 'update']],
            ],
        ],
        'indumentaria' => [
            'label' => 'Indumentaria',
            'resources' => [
                'apparel_items' => ['label' => 'Catálogo de prendas', 'actions' => $crud],
                'apparel_orders' => [
                    'label' => 'Pedidos de indumentaria',
                    'actions' => [...$crud, 'approve', 'deliver'],
                    'scoped' => $crud,
                ],
            ],
        ],
        'materiales' => [
            'label' => 'Materiales',
            'resources' => [
                'materials' => ['label' => 'Materiales', 'actions' => $crud],
                'storage_locations' => ['label' => 'Depósitos', 'actions' => $crud],
                'inventory' => ['label' => 'Stock', 'actions' => ['view', 'adjust', 'transfer']],
                'material_orders' => [
                    'label' => 'Pedidos de materiales',
                    'actions' => [...$crud, 'approve', 'deliver'],
                    'scoped' => $crud,
                ],
            ],
        ],
        'canchas' => [
            'label' => 'Canchas',
            'resources' => [
                'facilities' => ['label' => 'Canchas e instalaciones', 'actions' => $crud],
                'repairs' => ['label' => 'Reparaciones', 'actions' => [...$crud, 'assign']],
                'maintenance' => ['label' => 'Mantenimiento', 'actions' => $crud],
            ],
        ],
        'configuracion' => [
            'label' => 'Configuración',
            'resources' => [
                'club' => ['label' => 'Perfil del club', 'actions' => ['update']],
                'sports' => ['label' => 'Deportes', 'actions' => $crud],
                'seasons' => ['label' => 'Temporadas', 'actions' => $crud],
                'categories' => ['label' => 'Categorías', 'actions' => $crud, 'scoped' => ['view']],
                'venues' => ['label' => 'Sedes', 'actions' => $crud],
            ],
        ],
        'equipo' => [
            'label' => 'Equipo del club',
            'resources' => [
                'club_users' => ['label' => 'Usuarios', 'actions' => ['view', 'invite', 'update', 'disable']],
                'roles' => ['label' => 'Roles', 'actions' => [...$crud, 'assign']],
                'audit' => ['label' => 'Registro de actividad', 'actions' => ['view']],
            ],
        ],
        'comunicados' => [
            'label' => 'Comunicados',
            'resources' => [
                'announcements' => ['label' => 'Comunicados', 'actions' => ['view', 'send'], 'scoped' => ['view', 'send']],
            ],
        ],
    ],

    'roles' => [
        'admin' => [
            'name' => 'Administrador del club',
            'description' => 'Comisión directiva o gerente. Tiene todos los permisos.',
            'permissions' => '*',
        ],
        'sport_coordinator' => [
            'name' => 'Coordinador de deporte',
            'description' => 'Consulta todo lo deportivo de sus deportes asignados, sin modificar ni configurar el club.',
            // Ve todo lo de sus deportes (":sport", nunca otro deporte) y lo compartido del club que no
            // pertenece a ningún deporte (catálogo de prendas, stock, canchas). No ve Equipo, roles,
            // comunicados ni la configuración del admin, y no aprueba pedidos.
            'permissions' => [
                'seasons.view',
                'categories.view:sport',
                'competitions.view:sport',
                'players.view:sport',
                'player_contacts.view:sport',
                'player_documents.view:sport',
                'player_requirements.view:sport',
                'coaches.view:sport',
                'matches.view:sport',
                'trainings.view:sport',
                'attendance.view:sport',
                'callups.view:sport',
                'physical_evaluations.view:sport',
                'project.view:sport',
                'apparel_orders.view:sport',
                'material_orders.view:sport',
                'apparel_items.view',
                'materials.view',
                'storage_locations.view',
                'inventory.view',
                'facilities.view',
                'repairs.view',
                'maintenance.view',
            ],
        ],
        'coach' => [
            'name' => 'Profe',
            'description' => 'Entrenador de una o más categorías.',
            'permissions' => [
                'players.view:own',
                'player_contacts.view:own',
                'player_documents.view:own', 'player_documents.upload:own',
                'coaches.view:own',
                'matches.view:own', 'matches.create:own', 'matches.update:own',
                'trainings.view:own', 'trainings.create:own', 'trainings.update:own',
                'attendance.view:own', 'attendance.record:own',
                'callups.view:own', 'callups.update:own', 'callups.publish:own',
                'physical_evaluations.view:own',
                'project.view',
                'apparel_items.view',
                'apparel_orders.view:own', 'apparel_orders.create:own', 'apparel_orders.update:own',
                'material_orders.view:own', 'material_orders.create:own', 'material_orders.update:own',
                'facilities.view', 'repairs.view', 'repairs.create',
                'venues.view',
                'categories.view:own',
                'competitions.view:own',
                'player_requirements.view:own', 'player_requirements.update:own',
                'announcements.view:own', 'announcements.send:own',
            ],
        ],
        'physical_trainer' => [
            'name' => 'Preparador físico',
            'description' => 'Evaluaciones físicas de sus categorías.',
            'permissions' => [
                'players.view:own',
                'coaches.view:own',
                'matches.view:own', 'trainings.view:own', 'attendance.view:own', 'callups.view:own',
                'physical_evaluations.view:own', 'physical_evaluations.create:own',
                'physical_evaluations.update:own', 'physical_evaluations.delete:own',
                'project.view',
                'material_orders.view:own', 'material_orders.create:own', 'material_orders.update:own',
                'facilities.view', 'repairs.view', 'repairs.create',
                'venues.view',
                'categories.view:own',
                'competitions.view:own',
                'player_requirements.view:own',
            ],
        ],
        'inventory_manager' => [
            'name' => 'Utilero / Encargado',
            'description' => 'Materiales, indumentaria y entregas.',
            'permissions' => [
                'matches.view', 'trainings.view', 'callups.view',
                'apparel_items.view', 'apparel_items.create', 'apparel_items.update', 'apparel_items.delete',
                'apparel_orders.view', 'apparel_orders.create', 'apparel_orders.update', 'apparel_orders.deliver',
                'materials.view', 'materials.create', 'materials.update', 'materials.delete',
                'storage_locations.view', 'storage_locations.create', 'storage_locations.update', 'storage_locations.delete',
                'inventory.view', 'inventory.adjust', 'inventory.transfer',
                'material_orders.view', 'material_orders.create', 'material_orders.update', 'material_orders.deliver',
                'facilities.view', 'repairs.view', 'repairs.create',
                'venues.view',
            ],
        ],
        'maintenance_manager' => [
            'name' => 'Mantenimiento',
            'description' => 'Canchas e instalaciones.',
            'permissions' => [
                'matches.view', 'trainings.view', 'venues.view',
                'facilities.view', 'facilities.create', 'facilities.update', 'facilities.delete',
                'repairs.view', 'repairs.create', 'repairs.update', 'repairs.delete', 'repairs.assign',
                'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.delete',
            ],
        ],
    ],

];
