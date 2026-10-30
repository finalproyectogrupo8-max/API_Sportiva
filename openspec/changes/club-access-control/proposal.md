## Why

Hoy ningún usuario puede usar los endpoints del club: las policies piden `hasClubPermission()`, que no existía, y deportes, temporadas, categorías y profes responden 403 siempre. TIZ-10 definió roles estándar, roles custom, alcance "sus categorías" y la eliminación de los roles centrales ([docs/roles-y-permisos.md](../../../docs/roles-y-permisos.md)). Este change lo implementa: es la ola 0 del [plan de implementación](../../../docs/modelo-datos-club/plan-implementacion.md) y entrega C5 y C6.

## What Changes

- Catálogo de permisos y matriz de roles estándar en `config/club-permissions.php`, con `PermissionCatalog` que lo valida.
- `club:sync-permissions`: sincroniza permisos y roles estándar en cada base de club y crea el `club_user` admin de las membresías activas que no lo tienen. Corre en `ProvisionTenant` y después de cada `tenants:migrate`.
- Modelos `ClubUser`, `Role` y `Permission` (conexión `tenant`) y migración de club: `roles.slug`, `roles.is_system`, `permissions.module` y `role_permissions.scope`.
- `User::clubAccess()`, `hasClubPermission()`, `clubPermissionScope()` y `currentClubUser()`, calculados una vez por request desde la base del club.
- `EnsureTenantContext` rechaza un `club_user` deshabilitado (`club_user_disabled`).
- `select-tenant` y `GET /api/me` devuelven `profile`, `permissions` y `permission_scopes` reales.
- **BREAKING (admin):** las membresías dejan de tener `role`. `POST` y `PATCH /api/admin/tenants/{tenant}/memberships` reciben `roles` (slugs de roles del club) y la respuesta trae `roles`. Nuevo `GET /api/admin/tenants/{tenant}/roles`.
- **BREAKING:** se eliminan `users.role` (y `user.role` de las respuestas) y `tenant_memberships.role`, junto con el enum `MembershipRole`.
- Alcance "sus categorías" aplicado en `GET /api/coaches` y en show, update y delete de profes.

## Capabilities

### New Capabilities
- `club-access-control`: perfil local del club, roles, permisos con alcance y su resolución por request.

### Modified Capabilities
- Ninguna con spec vigente. `list-tenant-memberships` y `platform-admin-users` (sin archivar) cambian `role` por `roles`.

## Impact

- Base central: migración que elimina `users.role` y `tenant_memberships.role`.
- Bases de club: migración nueva y datos sembrados por `club:sync-permissions`.
- Front: el formulario de membresías de `/admin` tiene que mandar `roles` y mostrar los roles del club.
- Contrato OpenAPI regenerado.

## Fuera de alcance

- ABM de roles custom y endpoints del club para asignar roles (`/api/roles`, `/api/club-users/{id}/roles`): TIZ-12.
- Menú y rutas del front por permiso: FRO-13. Pantalla "Equipo del club": FRO-14.
