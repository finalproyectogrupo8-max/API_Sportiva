## Why

Para habilitar a un jugador, cada liga o federación pide papeles distintos según el deporte. El club tiene que configurar sus competencias y qué requisitos pide cada una. Es el primer paso de la habilitación de temporada (la funcionalidad estrella del plan de producto).

## What Changes

- Tablas `competitions`, `category_competitions` y `competition_requirements`, y `matches.competition_id`.
- `apiResource('competitions')` con requisitos y categorías en el mismo payload, y `POST /api/competitions/{id}/copy`.
- Módulo de permisos "Habilitación" con `competitions.*`; el profe y el preparador físico ven las competencias de sus categorías.

## Capabilities

### New Capabilities
- `club-competitions`: competencias del club y sus requisitos de habilitación.

### Modified Capabilities
- Ninguna.

## Impact

- Migración de club y sincronización de permisos con `tenants:migrate`.
- Cambia el contrato OpenAPI.
- Base de TIZ-32 (requisitos por jugador y cálculo de habilitado).
