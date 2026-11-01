## 1. Almacenamiento

- [x] 1.1 Migración `documents`, modelo, alias `player` y disco `DOCUMENTS_DISK`.
- [x] 1.2 `DocumentStore` (guardar y borrar) con rutas separadas por club.

## 2. API

- [x] 2.1 Documentos del jugador: listar, subir, descargar y borrar, con alcance "sus categorías".
- [x] 2.2 Foto del jugador en `PlayerResource` (`photo_document_id`).
- [x] 2.3 Escudo del club (`tenants.logo_path`, `logo_url`, endpoint público).
- [x] 2.4 Permisos `player_documents.*` y auditoría.

## 3. Cierre

- [x] 3.1 Tests (subida, validación, reemplazo de foto, alcance, aislamiento, escudo).
- [x] 3.2 Documentación (modelo 10, roles, `.env.example`), `openapi.json`, Pint y PHPUnit.
