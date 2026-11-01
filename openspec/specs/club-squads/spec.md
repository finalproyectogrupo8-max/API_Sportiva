# club-squads Specification

## Purpose
Plantel de cada categoría (una asignación activa por deporte, pase de categoría, número de camiseta) y profes por categoría, que definen el alcance "sus categorías" (club-squads, TIZ-15).

## Requirements

### Requirement: Plantel por categoría

La API SHALL permitir sumar jugadores al plantel de una categoría (`categories.manage_players`), cambiar su número y cerrar la asignación. Un jugador SHALL tener una sola asignación activa por deporte, y el número de camiseta SHALL ser único entre los activos de la categoría.

#### Scenario: Pase de categoría

- **WHEN** se suma a Sub-17 un jugador activo en Sub-15 del mismo deporte con `close_previous: true`
- **THEN** la asignación de Sub-15 pasa a `ended` con la fecha de inicio de la nueva y el historial se conserva

#### Scenario: Dos categorías del mismo deporte

- **WHEN** se suma a un jugador a otra categoría del mismo deporte sin `close_previous`
- **THEN** responde 422

#### Scenario: Número repetido

- **WHEN** se asigna un número que ya usa otro jugador activo de la categoría
- **THEN** responde 422 en `shirt_number`

### Requirement: Profes por categoría

La API SHALL permitir asignar profes a una categoría con un tipo (`head_coach`, `assistant`, `physical_trainer`) y cerrar la asignación (`categories.manage_coaches`). Las asignaciones vigentes SHALL definir el alcance "sus categorías" del profe.

#### Scenario: Asignar un profe

- **WHEN** el coordinador asigna un profe a una categoría
- **THEN** el profe pasa a ver el plantel de esa categoría
