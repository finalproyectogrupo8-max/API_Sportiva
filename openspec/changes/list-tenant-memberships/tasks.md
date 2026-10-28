## 1. Implementación

- [x] 1.1 `IndexTenantMembershipRequest` (`is_active`, `per_page`, `page`) y `viewAny` en `TenantMembershipPolicy`.
- [x] 1.2 `TenantMembershipController@index` y la ruta `GET admin/tenants/{tenant}/memberships`.
- [x] 1.3 `user` en `TenantMembershipResource` con `whenLoaded`.
- [x] 1.4 Tests: listado con datos del usuario, aislamiento entre clubes, filtro y paginación, club inexistente o dado de baja, 401 y 403.
- [x] 1.5 `UserMembershipController@index` y la ruta `GET admin/users/{user}/memberships`, con `tenant` en el resource y sus tests.
- [x] 1.6 `admin` opcional en `StoreTenantRequest` y `CreateTenantAction` (usuario + membresía en la misma transacción), con tests.
- [x] 1.7 Servicio `queue` en `compose.yaml` y guías actualizadas.
- [x] 1.8 Regenerar el contrato con `php artisan scramble:export`.
