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

### Requirement: Rama del plantel

Al sumar un jugador al plantel de una categoría de rama `female` o `male`, la API SHALL responder 422 con un mensaje que nombre la rama si el género del jugador es el opuesto. Una categoría `mixed` o sin rama, un jugador sin género cargado o de género `other` MUST NOT bloquear la asignación.

#### Scenario: Jugador en la rama opuesta

- **WHEN** se suma un jugador de género `male` a una categoría de rama `female`
- **THEN** responde 422 con el error en `player_id` y el jugador no se suma

#### Scenario: Categoría mixta

- **WHEN** se suma un jugador de género `male` a una categoría de rama `mixed`
- **THEN** se suma normalmente

#### Scenario: Sin dato

- **WHEN** se suma un jugador sin género cargado a una categoría de rama `female`, o un jugador de cualquier género a una categoría sin rama
- **THEN** se suma normalmente
