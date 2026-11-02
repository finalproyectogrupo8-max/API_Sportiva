# Spec Delta

## ADDED Requirements

### Requirement: Género del jugador

El jugador SHALL tener un género opcional (`female`, `male` u `other`) que se carga en el alta y la edición, se devuelve en la respuesta (`null` si no tiene) y se puede usar como filtro en el listado. Cambiar el género a `female` o `male` MUST responder 422 si el jugador tiene una asignación activa en una categoría de la rama opuesta.

#### Scenario: Alta con género

- **WHEN** se crea un jugador con `gender=female`
- **THEN** se guarda y la respuesta devuelve `gender: female`

#### Scenario: Filtrar por género

- **WHEN** se listan los jugadores con `gender=male`
- **THEN** responde solo los jugadores de género masculino

#### Scenario: Jugador existente sin género

- **WHEN** se lee o se edita un jugador creado antes de este cambio sin mandar `gender`
- **THEN** responde con `gender` en `null` y la edición funciona como antes

#### Scenario: Cambio de género que contradice sus categorías

- **WHEN** se cambia a `male` el género de una jugadora que está activa en una categoría de rama `female`
- **THEN** responde 422 y el género no cambia
