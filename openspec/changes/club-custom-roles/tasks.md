## 1. Roles custom

- [x] 1.1 `ManageClubRoles`: crear, editar, borrar y duplicar, con validación contra el catálogo y sin escalada.
- [x] 1.2 `RolePolicy` y `RoleController` (club), con las rutas `/api/permissions` y `/api/roles`.
- [x] 1.3 `TenantRoleController` (admin) con el mismo contrato.
- [x] 1.4 `ClubRoleResource` con permisos y `PermissionModuleResource`.

## 2. Reglas

- [x] 2.1 `ApiErrorException` y los códigos `role_is_system`, `role_in_use` y `last_admin`.
- [x] 2.2 Último administrador en `PATCH /api/admin/tenants/{tenant}/memberships/{membership}`.

## 3. Cierre

- [x] 3.1 Tests de club, admin y último administrador.
- [x] 3.2 Pint, PHPUnit y `scramble:export`.
