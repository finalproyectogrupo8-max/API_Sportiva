## Why

Los módulos del club necesitan sus tablas base para deportes, temporadas, categorías y profesores. Esas entidades pertenecen a cada club y deben existir en cada base operativa, manteniendo el esquema común definido por el modelo de datos.

## What Changes

- Agregar migrations de club para `sports`, `seasons`, `categories` y `coaches`.
- Incorporar las restricciones e índices del modelo propuesto, incluyendo las referencias de categorías y la asociación opcional de profesores con usuarios del club.
- Registrar una spec independiente por tabla para dejar trazabilidad de sus columnas y relaciones.

## Capabilities

### New Capabilities
- `club-sports`: catálogo de deportes por club.
- `club-seasons`: temporadas deportivas por club.
- `club-categories`: categorías relacionadas con un deporte y una temporada.
- `club-coaches`: profesores del club y su asociación opcional con un usuario del club.

### Modified Capabilities
- Ninguna.

## Impact

- Nuevas migrations en `database/migrations/tenant/`; no se modifica la base central.
- `categories` depende de `sports` y `seasons`.
- `coaches.club_user_id` referencia `club_users`; su migration debe ejecutarse después de la migration que cree `club_users`.
- No se agregan endpoints, modelos ni cambios al contrato OpenAPI en este change.

## Fuera de alcance

- ABM HTTP, permisos, factories, Resources y Policies.
- Asignaciones de jugadores o profesores a categorías.
- Integrar o ejecutar las migrations contra una base de club.
