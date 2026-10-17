## Why

Los clubes necesitan administrar sus deportes, temporadas, categorías y profesores desde la API. Las tablas ya tienen migrations propuestas en `club-foundation-tables`; falta exponer ABM seguros, validados y con respuestas tipadas, siguiendo `docs/guia-abm.md`.

## What Changes

- Agregar `Route::apiResource` para `/api/sports`, `/api/seasons`, `/api/categories` y `/api/coaches`.
- Implementar modelos, controllers, Form Requests, API Resources, Policies, factories y tests de cada recurso.
- Aplicar paginación, filtros permitidos, autorización por permisos de club y las reglas de baja definidas en el modelo.
- Regenerar `docs/api/openapi.json`.

## Capabilities

### New Capabilities
- `club-sports-crud`: ABM de deportes.
- `club-seasons-crud`: ABM de temporadas.
- `club-categories-crud`: ABM de categorías.
- `club-coaches-crud`: ABM de profesores.

### Modified Capabilities
- Ninguna.

## Impact

- Código bajo `app/Models/Club`, `app/Http/{Controllers,Requests,Resources}/Club`, `app/Policies/Club`, `database/factories/Club`, `routes/api.php` y `tests/Feature/Club`.
- Rutas protegidas por `auth:sanctum` y `tenant.token`; los modelos usan conexión `tenant`.
- Depende de `tenant-connection` (conexión, middleware, tests multi-club) y `club-access-control` (permisos, `hasClubPermission`, `club_users`).
- Cambia el contrato OpenAPI.

## Fuera de alcance

- Crear/aplicar las migrations, ya registradas en `club-foundation-tables`.
- Asignar jugadores o profesores a categorías (`player_category_memberships`, `category_coaches`).
- Alta/provisión de bases, cambios a la base central y cambios al frontend.
