# Spec Delta

## ADDED Requirements

### Requirement: Asociar posiciones deportivas a un jugador

El alta y edición de jugadores SHALL aceptar `position_ids` para asociar cero o más posiciones existentes del club. Al enviar el campo en una edición, la lista SHALL reemplazar las asociaciones actuales; al omitirlo, SHALL conservarlas. La respuesta SHALL incluir las posiciones asociadas y el deporte de cada una.

#### Scenario: Alta con varias posiciones de un deporte

- **WHEN** se crea un jugador con `position_ids` de arquero y defensor, ambas del deporte fútbol
- **THEN** responde 201 con las dos posiciones y el deporte fútbol asociado al jugador

#### Scenario: Asociar posiciones de distintos deportes

- **WHEN** se crea o edita un jugador con posiciones válidas de más de un deporte
- **THEN** se conservan todas las asociaciones, agrupables por el deporte de cada posición

#### Scenario: Posición inexistente o de otro club

- **WHEN** se crea o edita un jugador con una posición que no pertenece a la base del club actual o no existe
- **THEN** responde 422 en `position_ids` y no modifica las asociaciones

#### Scenario: Reemplazar o conservar asociaciones

- **WHEN** se edita un jugador enviando `position_ids`
- **THEN** las asociaciones se reemplazan por la lista recibida, y una lista vacía las elimina
- **AND** si se omite `position_ids`, las asociaciones existentes no cambian

#### Scenario: Compatibilidad con la posición de texto histórica

- **WHEN** se lee o edita un jugador que tiene `preferred_position` cargado antes de este cambio
- **THEN** el valor histórico se conserva y se sigue devolviendo sin convertirlo automáticamente en una posición del catálogo
