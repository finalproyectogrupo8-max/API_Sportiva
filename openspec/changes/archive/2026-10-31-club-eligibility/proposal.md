## Why

Es la funcionalidad estrella del plan de producto: saber en todo momento qué jugadores están habilitados para jugar y qué le falta a cada uno. Las competencias y sus requisitos (TIZ-31) y los documentos (TIZ-29) ya existen.

## What Changes

- Tabla `player_requirements` con el estado de cada requisito por jugador.
- Cálculo de habilitado: requisitos de las competencias de sus categorías activas, sin los "solo menores" si es mayor; habilitado si tiene aprobados y vigentes todos los obligatorios.
- Grilla por categoría, resumen del club y requisitos de un jugador.
- Presentar (con documento si el requisito lo pide), aprobar (con vencimiento) y rechazar (con motivo).
- Comando diario `club:expire-requirements`.
- Permisos `player_requirements.view`, `.update` y `.approve`.

## Capabilities

### New Capabilities
- `club-eligibility`: habilitación de jugadores por temporada.

### Modified Capabilities
- Ninguna.

## Impact

- Migración de club y tarea programada.
- Cambia el contrato OpenAPI. Base de la convocatoria (TIZ-33), que solo convoca habilitados.
