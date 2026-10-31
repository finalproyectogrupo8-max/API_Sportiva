## Why

El modelo del club necesita registrar jugadores antes de implementar sus operaciones. La tabla debe seguir el esquema multi-tenant por base de datos y dejar las relaciones de temporada/categoría para el historial de asignaciones, en lugar de guardar una categoría actual directamente en `players`.

## What Changes

- Agregar una migration de club para `players` en `database/migrations/tenant/`.
- Definir ficha técnica con campos personales, deportivos, estado, timestamps y baja lógica según el modelo de datos y la guía ABM.
- Mantener fuera del alcance de esta etapa el endpoint CRUD; se propondrá en un change posterior una vez revisada y creada la tabla.

## Capabilities

### New Capabilities
- `club-players-table`: esquema de jugadores en la base de cada club.

### Modified Capabilities
- Ninguna.

## Impact

- Migration nueva de la base del club; no altera la base central.
- El esquema se aplicará en cada base de club mediante el mecanismo de migraciones de tenant cuando esté disponible.
- El contrato OpenAPI no cambia en esta etapa.

## Fuera de alcance

- ABM HTTP de jugadores, permisos y Resources.
- Asignaciones de jugadores a categorías (`player_category_memberships`).
- Datos de tutores, domicilio, salud u otros datos personales adicionales no aprobados expresamente.
