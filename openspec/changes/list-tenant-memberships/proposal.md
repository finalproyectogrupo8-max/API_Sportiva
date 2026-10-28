# Listar las membresías de un club

## Why

El panel de admin del front (FRO-7) muestra los miembros de un club en su detalle y permite agregarlos o desactivarlos. La API ya permite crear (`POST`) y modificar (`PATCH`) membresías, pero no había forma de listarlas.

## What Changes

- Nuevo `GET /api/admin/tenants/{tenant}/memberships`, solo para admins de plataforma: membresías del club, paginadas (20 por defecto, 100 como máximo), con el usuario (`id`, `name`, `email`, ...) en cada fila. Filtro opcional `is_active`.
- Nuevo `GET /api/admin/users/{user}/memberships`, solo para admins de plataforma: los clubes de un usuario (sus membresías con el club en cada una), sin clubes dados de baja. Mismos filtros y paginación. El front lo usa para no ofrecer clubes donde el usuario ya es miembro.
- `POST /api/admin/tenants` acepta un `admin` opcional (`name`, `email`, `password`): crea ese usuario y lo asocia al club como administrador en la misma transacción que el club. Si algo no valida, no se crea nada.
- Nuevo servicio `queue` en `compose.yaml` (worker de la cola) para que el alta del club sea asíncrona también en local, como en producción.
- `TenantMembershipResource` incluye `user` y `tenant` cuando la relación está cargada. Cambio aditivo: `POST` y `PATCH` no cambian.

## Impact

- `routes/api.php`, `TenantMembershipController`, `UserMembershipController`, `IndexTenantMembershipRequest`, `TenantMembershipPolicy`, `TenantMembershipResource`, `docs/api/openapi.json`.
- Tests en `tests/Feature/Admin/TenantMembershipAdminTest.php`.
- Front: detalle del club en `/admin/clubes/[id]` (FRO-7).
