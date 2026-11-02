# Spec Delta

## ADDED Requirements

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
