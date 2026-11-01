## ADDED Requirements

### Requirement: Documentos privados del jugador

La API SHALL permitir subir, listar, descargar y borrar documentos de un jugador (PDF, JPG, PNG, WEBP o HEIC, hasta 10 MB) con `player_documents.*` y alcance "sus categorías". Los archivos SHALL guardarse en un disco privado y descargarse solo por la API. Cada descarga o borrado de un papel SHALL quedar en la auditoría.

#### Scenario: Descarga de un apto médico

- **WHEN** un usuario con `player_documents.view` descarga el apto de un jugador visible
- **THEN** recibe el archivo y queda un registro `document.downloaded`

#### Scenario: Profe fuera de su alcance

- **WHEN** un profe pide un documento de un jugador de otra categoría
- **THEN** recibe 404

#### Scenario: Foto nueva

- **WHEN** se sube una foto a un jugador que ya tenía una
- **THEN** la anterior se borra y la ficha apunta a la nueva

### Requirement: Escudo del club

La API SHALL permitir al administrador del club (`club.update`) subir y borrar el escudo (PNG, JPG o WEBP hasta 1 MB) y SHALL servirlo sin token para clubes activos.

#### Scenario: Escudo en el selector

- **WHEN** el club tiene escudo
- **THEN** `GET /api/me/tenants` devuelve su `logo_url` y esa URL responde la imagen
