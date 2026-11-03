## ADDED Requirements

### Requirement: Catálogo de permisos y roles estándar

La API SHALL definir los permisos del club en `config/club-permissions.php` con claves `<recurso>.<acción>`. `club:sync-permissions` SHALL copiar a cada base de club el catálogo y los seis roles estándar (`admin`, `coordinator`, `coach`, `physical_trainer`, `inventory_manager`, `maintenance_manager`) con `is_system = true`, de forma idempotente. El rol `admin` SHALL tener todos los permisos con alcance `all`.

#### Scenario: Alta de un club

- **WHEN** `ProvisionTenant` termina de migrar la base de un club
- **THEN** el club tiene los roles estándar y todos los permisos del catálogo
- **AND** el administrador inicial del alta tiene un `club_user` activo con el rol `admin`

#### Scenario: Club existente

- **WHEN** se corre `tenants:migrate` dos veces sobre un club existente
- **THEN** los roles y permisos quedan sembrados una sola vez
- **AND** cada membresía activa sin `club_user` recibe uno con el rol `admin`

#### Scenario: Catálogo inválido

- **WHEN** un rol estándar referencia un permiso que no existe o pide alcance `own` en un permiso que no lo admite
- **THEN** la resolución del catálogo falla con `LogicException`

### Requirement: Permisos por request

La API SHALL resolver los permisos del usuario en el club del token leyendo la base del club en cada request, sumando los permisos de todos sus roles. Si dos roles dan el mismo permiso con distinto alcance, SHALL ganar `all`. Las policies SHALL usar `hasClubPermission()`.

#### Scenario: Permiso según el rol

- **WHEN** un usuario con el rol `coach` hace `GET /api/categories` y `POST /api/sports`
- **THEN** el primero responde 200 y el segundo 403 con `code: forbidden`

#### Scenario: Sin perfil en el club

- **WHEN** un miembro activo sin `club_user` usa un endpoint del club
- **THEN** responde 403 y `GET /api/me` devuelve `profile: null` y `permissions: []`

#### Scenario: Cambio de roles

- **WHEN** se le cambian los roles a un usuario con un token de club vigente
- **THEN** el siguiente request usa los permisos nuevos

#### Scenario: Usuario deshabilitado en el club

- **WHEN** el `club_user` está `disabled` y la membresía central sigue activa
- **THEN** los endpoints del club y `select-tenant` responden 403 con `code: club_user_disabled`

#### Scenario: Aislamiento entre clubes

- **WHEN** un usuario es `admin` en el club A y `coach` en el club B
- **THEN** con el token de B no puede crear deportes y con el de A sí, y los datos de cada club quedan en su base

### Requirement: Alcance "sus categorías"

Un permiso asignado con alcance `own` SHALL limitar los registros a las categorías donde el usuario tiene una asignación vigente como entrenador. Un registro fuera del alcance SHALL responder 404.

#### Scenario: Profes visibles para un profe

- **WHEN** un usuario con `coaches.view` en alcance `own` lista profes
- **THEN** ve su ficha y la de los profes con asignación vigente en sus categorías
- **AND** el detalle de otro profe responde 404

### Requirement: Perfil y permisos en la sesión

`POST /api/auth/select-tenant` y `GET /api/me` SHALL devolver `profile` (id, `display_name`, `status` y `roles` con `slug` y `name`), `permissions` (claves ordenadas) y `permission_scopes` (`clave => all|own`).

#### Scenario: Usuario con dos roles

- **WHEN** un usuario con los roles `coach` e `inventory_manager` consulta `GET /api/me`
- **THEN** recibe los permisos de los dos roles, con `players.view` en `own` y `apparel_orders.view` en `all`

### Requirement: Roles al dar acceso desde el panel

`POST /api/admin/tenants/{tenant}/memberships` SHALL requerir `roles` (al menos un slug de rol del club) y crear o reactivar el `club_user` en la misma operación. `PATCH` SHALL aceptar `roles` para reemplazar la lista sin reactivar un `club_user` deshabilitado. Los cambios de roles SHALL auditarse como `membership.roles_updated`. `GET /api/admin/tenants/{tenant}/roles` SHALL listar los roles del club.

#### Scenario: Rol inexistente

- **WHEN** se crea una membresía con un rol que no existe en el club
- **THEN** responde 422 en `roles` y no se crea la membresía ni el `club_user`

#### Scenario: Club no activo

- **WHEN** se crea una membresía en un club que todavía se está dando de alta
- **THEN** responde 422 en `roles`
