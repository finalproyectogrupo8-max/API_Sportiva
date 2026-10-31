## Why

El club arma el fixture, planifica entrenamientos y toma asistencia. Es el paso del recorrido del partido que viene después del plantel (TIZ-15) y antes de la convocatoria (TIZ-33). Las tablas `matches`, `trainings` y `player_attendance` ya existen.

## What Changes

- `apiResource('matches')` y `apiResource('trainings')` con filtros por categoría, rango de fechas y estado. El resultado se carga con `PATCH`.
- `GET` y `PUT /api/matches/{match}/attendance` y `/api/trainings/{training}/attendance`: el plantel con la asistencia cargada y la carga del lote completo, idempotente.
- `GET /api/calendar?from&to`: partidos y entrenamientos juntos.
- Migración: `matches` suma `meet_at`, `maps_url`, `uniform` y `what_to_bring` para el aviso de la convocatoria.

## Capabilities

### New Capabilities
- `club-calendar`: partidos, entrenamientos, asistencia y calendario del club.

### Modified Capabilities
- Ninguna.

## Impact

- Migración de club; la aplica `tenants:migrate`.
- Código en `app/Models/Club`, `app/Http/{Controllers,Requests,Resources}/Club`, `routes/api.php` y `tests/Feature/Club`.
- Cambia el contrato OpenAPI.
