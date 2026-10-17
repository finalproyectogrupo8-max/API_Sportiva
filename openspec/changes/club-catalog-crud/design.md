## Contexto y dependencias

Este change implementa cuatro ABM sobre la base de cada club. Se apoya en las tablas descritas en `docs/modelo-datos-club/02-deportes-y-planteles.md` y en las migrations del change `club-foundation-tables`.

El código asume disponibles en `main` la conexión y el cambio de base de `tenant-connection`, y `club-access-control` con `hasClubPermission()`, `club_users` y el seeder de permisos. No se agregan implementaciones provisionales de esas piezas en este change. Cada modelo declara `$connection = 'tenant'`.

## Estructura

- Modelos: `app/Models/Club/{Sport,Season,Category,Coach}.php`.
- Controllers, Requests y Resources: subcarpetas `Club/`.
- Policies: `app/Policies/Club/`, con `Gate::authorize()` para `viewAny`, `view`, `create`, `update` y `delete`.
- Factories: `database/factories/Club/`.
- Tests: un archivo `tests/Feature/Club/<Model>ApiTest.php` por recurso, usando `InteractsWithTenants`.
- Rutas: `Route::apiResource` dentro de `['auth:sanctum', 'tenant.token']`.

## Contrato común

- `index` valida filtros con `Index<Model>Request`, pagina 20 por defecto y 100 como máximo, usa orden estable y una lista permitida para filtros/orden; responde `Resource::collection()`.
- `store`/`update` usan `Store<Model>Request`/`Update<Model>Request`; nunca toman atributos fuera de `validated()`.
- `show`, `store`, `update` responden el API Resource; `store` devuelve 201, `destroy` devuelve 204.
- Las Policies consultan permission keys del club: `<resource>.view`, `.create`, `.update`, `.delete`.
- Validación responde 422 y los errores siguen el formato JSON común.

## Comportamiento por recurso

| Recurso | Filtros de listado | Baja |
|---|---|---|
| `sports` | `search`, `is_active` | Desactivar (`is_active = false`); no borrar fila |
| `seasons` | `search`, `status` | Borrado real solo si no tiene categorías; si está en uso, 409 con `code: resource_in_use` |
| `categories` | `sport_id`, `season_id`, `is_active` | Desactivar (`is_active = false`); no borrar fila |
| `coaches` | `search`, `status` | Soft delete (`deleted_at`) |

Las relaciones se devuelven por IDs y se incluyen como Resources solo si el controller las cargó. `coaches.club_user_id` es nullable; el email es de contacto, no credencial de login.

## Tests

Cada recurso cubre los diez casos de `docs/guia-abm.md`: listado paginado, show, alta válida e inválida, PATCH parcial, baja, 401, 403, 404 y aislamiento A/B. También se verifican filtros, unicidad, FK, baja por estado/soft delete y conflicto al borrar temporadas en uso. No usar datos personales reales.

## Migración y rollout

El change solo agrega endpoints. Requiere que sus migrations estén presentes y en orden (incluida `club_users` antes de `coaches`) cuando se ejecute sobre una base de club. No se agrega una conexión temporal ni se apunta código de club a `mysql`.
