# club-seasons-crud Specification

## Purpose
ABM de temporadas en la base de cada club: endpoints, validación, permisos `seasons.*`, tipo de baja y aislamiento entre clubes (club-catalog-crud, TIZ-14).

## Requirements

### Requirement: ABM de temporadas

La API SHALL exponer `apiResource` de temporadas bajo `auth:sanctum` y `tenant.token`. SHALL validar con Form Requests, autorizar con `seasons.*`, responder con `SeasonResource` y paginar listados (20 por defecto, 100 máximo).

#### Scenario: Crear temporada

- **WHEN** se crea una temporada con fechas válidas y estado admitido
- **THEN** responde 201 con `SeasonResource`

#### Scenario: Rango de fechas inválido

- **WHEN** `end_date` es anterior a `start_date`
- **THEN** responde 422 con error de validación

#### Scenario: Borrar temporada sin categorías

- **WHEN** se borra una temporada no referenciada
- **THEN** responde 204 y elimina la fila

#### Scenario: Borrar temporada en uso

- **WHEN** se intenta borrar una temporada referenciada por categorías
- **THEN** responde 409 con `code: resource_in_use`

#### Scenario: Aislamiento entre clubes

- **WHEN** un usuario del club A consulta una temporada que solo existe en el club B
- **THEN** recibe 404 y los datos del club B no cambian
