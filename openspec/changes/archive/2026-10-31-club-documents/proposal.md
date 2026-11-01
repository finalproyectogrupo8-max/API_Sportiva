## Why

La habilitación de temporada necesita adjuntar el apto médico, el DNI o la ficha de cada jugador, y la ficha y la asistencia necesitan su foto. El club también necesita su escudo. Hoy ninguna tabla guarda archivos.

## What Changes

- Tabla `documents` (polimórfica) y disco privado configurable (`DOCUMENTS_DISK`: `local` en desarrollo, `s3` en producción).
- `GET` y `POST /api/players/{player}/documents`, `GET /api/documents/{id}/download` y `DELETE /api/documents/{id}`.
- Escudo: `POST` y `DELETE /api/club/logo`, `GET /api/public/clubs/{slug}/logo` y `logo_url` en los recursos del club (`tenants.logo_path`).
- Permisos `player_documents.view`, `.upload` y `.delete`, y auditoría de cada acceso a un papel.

## Capabilities

### New Capabilities
- `club-documents`: documentos privados de los jugadores y escudo del club.

### Modified Capabilities
- Ninguna.

## Impact

- Migración de club y migración central. En producción hay que configurar el bucket y `DOCUMENTS_DISK=s3`.
- Cambia el contrato OpenAPI.
