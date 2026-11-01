# 10. Documentos

[← Índice](README.md)

Archivos del club: la foto de la ficha y los papeles del jugador (apto médico, DNI, ficha federativa, autorización del tutor). Una sola tabla que se cuelga de cualquier entidad (TIZ-29). Un apto médico es un **dato de salud** (Ley 25.326): los archivos son privados y cada acceso a un papel queda en `audit_logs`.

## `documents`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | |
| `documentable_type` | VARCHAR(50) | no | | Alias de la entidad (`player`) |
| `documentable_id` | BIGINT UNSIGNED | no | | |
| `kind` | VARCHAR(30) | no | | `photo` / `medical` / `id_document` / `federation_card` / `authorization` / `other` |
| `original_name` | VARCHAR(255) | no | | |
| `disk` | VARCHAR(30) | no | | Disco donde está el archivo |
| `path` | VARCHAR(500) | no | | `clubs/<club>/<entidad>/<id>/<uuid>.<ext>` |
| `mime` | VARCHAR(100) | no | | |
| `size` | INT UNSIGNED | no | | Bytes |
| `uploaded_by` | BIGINT UNSIGNED | sí | FK → `club_users.id`, `ON DELETE SET NULL` | |
| `created_at`, `updated_at` | TIMESTAMP | sí | | |
| `deleted_at` | TIMESTAMP | sí | | Al borrar se elimina el archivo y la fila queda con `deleted_at` |

- Índice: `(documentable_type, documentable_id, kind)`.
- Una sola foto por jugador: la nueva reemplaza a la anterior.

## Cómo se guarda y se descarga

- **Disco:** `config('filesystems.documents_disk')`, variable `DOCUMENTS_DISK`. En desarrollo, `local` (privado, `storage/app/private`). En producción, `s3` (bucket compatible): el disco de Laravel Cloud se borra en cada deploy.
- **Sin URL pública:** el archivo se baja con `GET /api/documents/{id}/download`, que valida el permiso y el alcance. El front lo reenvía (patrón BFF); el navegador nunca habla con la API ni con el bucket.
- **Permisos:** la foto la ve quien ve al jugador (`players.view`); los papeles piden `player_documents.view`. Subir, `player_documents.upload`; borrar, `player_documents.delete`.
- **Límites:** PDF, JPG, PNG, WEBP o HEIC hasta 10 MB; la foto, solo imagen y hasta 5 MB.

## Escudo del club

No va en esta tabla: se muestra antes de entrar al club, así que la ruta vive en la base central (`tenants.logo_path`). Se sube con `POST /api/club/logo` (`club.update`, PNG, JPG o WEBP de hasta 1 MB) y se sirve sin token en `GET /api/public/clubs/{slug}/logo`. Los recursos del club exponen `logo_url`.
