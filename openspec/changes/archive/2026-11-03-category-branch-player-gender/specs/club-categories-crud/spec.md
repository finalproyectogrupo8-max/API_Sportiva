# Spec Delta

## ADDED Requirements

### Requirement: Rama de la categoría

La categoría SHALL tener una rama opcional (`female`, `male` o `mixed`) que se carga en el alta y la edición, se devuelve en la respuesta (`null` si no tiene) y se puede usar como filtro en el listado. Cambiar la rama a `female` o `male` MUST responder 422 si la categoría tiene jugadores activos del género opuesto.

#### Scenario: Categorías femenina y masculina

- **WHEN** el coordinador crea "Sub 21 femenino" con rama `female` y "Sub 21 masculino" con rama `male` en el mismo deporte y temporada
- **THEN** las dos se crean y cada una devuelve su rama

#### Scenario: Filtrar por rama

- **WHEN** se listan las categorías con `branch=female`
- **THEN** responde solo las categorías de rama femenina

#### Scenario: Categoría existente sin rama

- **WHEN** se lee o se edita una categoría creada antes de este cambio sin mandar `branch`
- **THEN** responde con `branch` en `null` y la edición funciona como antes

#### Scenario: Rama inválida

- **WHEN** se crea una categoría con `branch=women`
- **THEN** responde 422 con el error en `branch`

#### Scenario: Cambio de rama que contradice el plantel

- **WHEN** se cambia a `female` una categoría que tiene un jugador activo de género `male`
- **THEN** responde 422 y la rama no cambia
