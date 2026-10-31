## Decisiones

- **Descarga por la API, sin URLs firmadas.** El front es un BFF: el navegador no habla con la API ni con el bucket. La API valida el permiso, devuelve el archivo y el front lo reenvía.
- **Datos sensibles:** los papeles piden `player_documents.view` y cada descarga y borrado queda en `audit_logs` (`document.downloaded`, `document.deleted`). La foto de la ficha la ve quien ve al jugador y no se audita.
- **Borrar** elimina el archivo del disco y deja la fila con `deleted_at`.
- **Una foto por jugador:** la nueva reemplaza a la anterior.
- **Escudo en la base central** (`tenants.logo_path`), servido sin token: no es sensible y se muestra antes de entrar al club.
- **Alias `player`** en `documentable_type` (`Relation::morphMap`), para no guardar nombres de clase.
