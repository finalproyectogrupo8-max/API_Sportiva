## Why

El club tiene que gestionar sus jugadores, armar el plantel de cada categoría por temporada y asignar profes a cada categoría. Además, el recorrido del partido (habilitación y convocatoria) necesita los contactos de cada jugador para avisarles. Las tablas `players`, `player_category_memberships` y `category_coaches` ya existen; falta exponerlas.

## What Changes

- ABM de jugadores (`/api/players`) con filtros, alcance "sus categorías" y baja lógica que cierra las asignaciones activas.
- Tabla nueva `player_contacts` y sus endpoints (`/api/players/{player}/contacts`, `/api/player-contacts/{contact}`) con un solo contacto principal.
- Plantel por categoría (`/api/categories/{category}/memberships`, `/api/memberships/{membership}` y `/end`): una asignación activa por deporte, pase de categoría con `close_previous` y número de camiseta único entre los activos.
- Profes por categoría (`/api/categories/{category}/coaches`, `/api/category-coaches/{categoryCoach}/end`).
- Permisos nuevos `player_contacts.view` y `player_contacts.update` en el catálogo.
- `birth_date` obligatoria en el alta de jugadores.

## Capabilities

### New Capabilities
- `club-players-crud`: ABM de jugadores y sus contactos.
- `club-squads`: plantel por categoría y profes por categoría.

### Modified Capabilities
- Ninguna.

## Impact

- Migración de club `player_contacts`; `tenants:migrate` la aplica a cada club y sincroniza los permisos.
- Código en `app/Models/Club`, `app/Http/{Controllers,Requests,Resources}/Club`, `routes/api.php` y `tests/Feature/Club`.
- Cambia el contrato OpenAPI.

## Fuera de alcance

- Foto del jugador y documentos (TIZ-29).
- Requisitos de habilitación (TIZ-31 y TIZ-32).
