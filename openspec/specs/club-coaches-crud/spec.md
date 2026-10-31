# club-coaches-crud Specification

## Purpose
ABM de profes en la base de cada club: endpoints, validación, permisos `coaches.*`, tipo de baja y aislamiento entre clubes (club-catalog-crud, TIZ-14).

## Requirements

### Requirement: ABM de profesores

La API SHALL exponer `apiResource` de profesores bajo `auth:sanctum` y `tenant.token`. SHALL validar con Form Requests, autorizar con `coaches.*`, responder con `CoachResource` y paginar listados (20 por defecto, 100 máximo). La baja SHALL ser lógica.

#### Scenario: Crear profesor sin usuario de plataforma

- **WHEN** se crea un profesor sin `club_user_id`
- **THEN** responde 201 y conserva nulo el vínculo opcional

#### Scenario: Vínculo de usuario duplicado

- **WHEN** se intenta vincular un `club_user_id` ya asociado a otro profesor
- **THEN** responde 422 sin crear otra ficha

#### Scenario: Borrar profesor

- **WHEN** un usuario autorizado borra un profesor
- **THEN** responde 204 y registra `deleted_at`

#### Scenario: Aislamiento entre clubes

- **WHEN** un usuario del club A consulta un profesor que solo existe en el club B
- **THEN** recibe 404 y los datos del club B no cambian
