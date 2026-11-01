# club-categories-crud Specification

## Purpose
ABM de categorías en la base de cada club: endpoints, validación, permisos `categories.*`, tipo de baja y aislamiento entre clubes (club-catalog-crud, TIZ-14).

## Requirements

### Requirement: ABM de categorías

La API SHALL exponer `apiResource` de categorías bajo `auth:sanctum` y `tenant.token`. SHALL validar las FK a deporte y temporada, autorizar con `categories.*`, responder con `CategoryResource` y paginar listados (20 por defecto, 100 máximo). La baja SHALL desactivar la categoría.

#### Scenario: Filtrar categorías

- **WHEN** se solicitan categorías con `sport_id` o `season_id`
- **THEN** solo devuelve categorías que coinciden con los filtros validados

#### Scenario: Rechazar referencias inexistentes

- **WHEN** se crea una categoría con un deporte o temporada inexistente
- **THEN** responde 422 con el error del campo correspondiente

#### Scenario: Evitar combinación duplicada

- **WHEN** se repite nombre para el mismo deporte y temporada
- **THEN** responde 422 sin crear otra fila

#### Scenario: Desactivar categoría

- **WHEN** un usuario autorizado borra una categoría
- **THEN** la fila se conserva y `is_active` pasa a `false`

#### Scenario: Aislamiento entre clubes

- **WHEN** un usuario del club A consulta una categoría que solo existe en el club B
- **THEN** recibe 404 y los datos del club B no cambian
