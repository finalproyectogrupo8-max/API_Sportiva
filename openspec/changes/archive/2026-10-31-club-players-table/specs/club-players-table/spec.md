## ADDED Requirements

### Requirement: Registro de jugadores por club

Cada base operativa SHALL tener una tabla `players` con `id`, `first_name`, `last_name`, `status` y timestamps. También SHALL permitir `birth_date`, `document_number`, `registration_number`, `preferred_position`, `dominant_side` y `notes` nulos. La tabla SHALL incluir `deleted_at` para baja lógica.

#### Scenario: Crear el esquema de jugadores

- **WHEN** se ejecutan las migraciones de club
- **THEN** cada base de club recibe la tabla `players` con el mismo esquema y sus migrations quedan registradas en esa base

#### Scenario: Datos opcionales ausentes

- **WHEN** se registra un jugador sin fecha de nacimiento, número de registro, posición, lateralidad u observaciones
- **THEN** el esquema permite guardar esos campos, incluido `document_number`, como `NULL`

#### Scenario: Baja lógica

- **WHEN** una aplicación marca un jugador como eliminado
- **THEN** la fila conserva sus datos y registra la baja en `deleted_at`

### Requirement: Aislamiento y asignaciones de jugadores

La tabla `players` MUST NOT incluir `tenant_id`, `club_id` ni `category_id`. El aislamiento depende de la base del club y las asignaciones con historial pertenecen a `player_category_memberships`.

#### Scenario: Esquema sin columnas de club o categoría

- **WHEN** se crea la tabla `players`
- **THEN** no contiene columnas `tenant_id`, `club_id` ni `category_id`
