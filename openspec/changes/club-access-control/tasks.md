## 1. Esquema

- [x] 1.1 Migración de club: `roles.slug`, `roles.is_system`, `permissions.module`, `role_permissions.scope`.
- [x] 1.2 Migración central: eliminar `users.role` y `tenant_memberships.role`; borrar `MembershipRole`.

## 2. Catálogo y sincronización

- [x] 2.1 `config/club-permissions.php` con el catálogo y los roles estándar de docs/roles-y-permisos.md.
- [x] 2.2 `PermissionCatalog` (validación, roles resueltos y grilla).
- [x] 2.3 `SyncClubPermissions` y comando `club:sync-permissions`.
- [x] 2.4 Llamarlo desde `ProvisionTenant` y `tenants:migrate`; crear el `club_user` admin de las membresías sin perfil.

## 3. Resolución de permisos

- [x] 3.1 Modelos `ClubUser`, `Role` y `Permission`.
- [x] 3.2 `ClubAccess` y `User::clubAccess()`, `hasClubPermission()`, `clubPermissionScope()`, `currentClubUser()`.
- [x] 3.3 `EnsureTenantContext` rechaza `club_user` deshabilitado.
- [x] 3.4 Sacar `method_exists` de las 4 policies.
- [x] 3.5 Alcance `own` en profes.

## 4. Auth y admin

- [x] 4.1 `profile`, `permissions` y `permission_scopes` en `select-tenant` y `/api/me`.
- [x] 4.2 Membresías con `roles`; `GET /api/admin/tenants/{tenant}/roles`; auditoría `membership.roles_updated`.
- [x] 4.3 Sacar `role` de `UserResource` y del `UserSeeder`.

## 5. Tests y cierre

- [x] 5.1 Tests de permisos (caso 8) y aislamiento (caso 10) en `tests/Feature/Club/ClubPermissionsTest.php`.
- [x] 5.2 Tests del catálogo, de membresías con roles y de la provisión con admin inicial.
- [x] 5.3 Pint, PHPUnit y `scramble:export`.
