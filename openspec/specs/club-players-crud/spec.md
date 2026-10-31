# club-players-crud Specification

## Purpose
ABM de jugadores y de sus tutores y contactos en la base de cada club: alcance "sus categorías", baja lógica y contacto principal (club-squads, TIZ-15).

## Requirements

### Requirement: ABM de jugadores

La API SHALL exponer `apiResource('players')` bajo `auth:sanctum` y `tenant.token`, con permisos `players.*`, paginación (20 por defecto, 100 máximo) y filtros `search`, `category_id` y `status`. El alta SHALL exigir `birth_date`. La baja SHALL ser lógica y cerrar las asignaciones activas del jugador.

#### Scenario: Alta sin fecha de nacimiento

- **WHEN** un usuario con `players.create` crea un jugador sin `birth_date`
- **THEN** responde 422 con error en `birth_date`

#### Scenario: Baja de un jugador

- **WHEN** un usuario con `players.delete` borra un jugador con una asignación activa
- **THEN** el jugador queda con `deleted_at` y la asignación pasa a `ended`

#### Scenario: Alcance del profe

- **WHEN** un profe con `players.view` en alcance "sus categorías" consulta un jugador de otra categoría
- **THEN** recibe 404

#### Scenario: Aislamiento entre clubes

- **WHEN** un usuario del club A consulta un jugador que solo existe en el club B
- **THEN** recibe 404 y los datos del club B no cambian

### Requirement: Contactos del jugador

La API SHALL permitir listar, crear, editar y borrar contactos de un jugador con `player_contacts.view` y `player_contacts.update`. Cada jugador SHALL tener a lo sumo un contacto principal, y cada contacto SHALL tener teléfono o mail.

#### Scenario: Primer contacto

- **WHEN** se crea el primer contacto de un jugador
- **THEN** queda como principal

#### Scenario: Cambiar el principal

- **WHEN** se marca otro contacto como principal
- **THEN** el anterior deja de serlo
