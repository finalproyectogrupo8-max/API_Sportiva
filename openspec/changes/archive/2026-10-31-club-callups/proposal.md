## Why

Es la estrella 2 del plan de producto: el profe convoca entre los habilitados, comparte por WhatsApp y cada jugador confirma con un toque. Hoy eso se hace a mano en grupos de WhatsApp.

## What Changes

- Tabla `match_callups` y columnas de publicación en `matches`.
- `GET`, `PUT /api/matches/{match}/callup` y `POST .../publish`: armar la lista (solo plantel y habilitados), publicar y ver respuestas, con el mensaje para el grupo y los links de WhatsApp por jugador.
- Páginas públicas sin login: la convocatoria, la confirmación con link personal y el `.ics` del partido.
- Marca `updated_after_publish` si cambia la fecha, la hora o el lugar después de publicar.
- Permisos `callups.view`, `.update` y `.publish`. `FRONTEND_URL` para los links.

## Capabilities

### New Capabilities
- `club-callups`: convocatorias a partidos con confirmación por link.

### Modified Capabilities
- Ninguna.

## Impact

- Migración de club y variable `FRONTEND_URL` (la misma que usa la recuperación de contraseña).
- Cambia el contrato OpenAPI.
- El aviso por mail queda para cuando exista la infraestructura de mail (TIZ-21).
