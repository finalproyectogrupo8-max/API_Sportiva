# club-sports-crud Specification

## Purpose
ABM de deportes en la base de cada club: endpoints, validación, permisos `sports.*`, tipo de baja y aislamiento entre clubes (club-catalog-crud, TIZ-14).

## Requirements

### Requirement: ABM de deportes

La API SHALL exponer `apiResource` de deportes bajo `auth:sanctum` y `tenant.token`. SHALL validar datos con Form Requests, autorizar con la Policy `sports.*`, responder con `SportResource` y paginar listados (20 por defecto, 100 máximo). La baja SHALL desactivar el deporte.

#### Scenario: Listar deportes

- **WHEN** un miembro autorizado solicita `GET /api/sports`
- **THEN** recibe `data`, `links` y `meta` paginados con 20 elementos por defecto

#### Scenario: Crear deporte

- **WHEN** se crea un deporte con nombre válido y no repetido
- **THEN** responde 201 con `SportResource`

#### Scenario: Nombre duplicado

- **WHEN** se crea un deporte con un nombre existente en el mismo club
- **THEN** responde 422 con error de validación

#### Scenario: Desactivar deporte

- **WHEN** un usuario con permiso borra un deporte
- **THEN** la fila se conserva y `is_active` pasa a `false`

#### Scenario: Deporte con categorías activas

- **WHEN** un usuario con permiso borra un deporte que tiene categorías activas
- **THEN** responde 409 con `code: resource_in_use` y el deporte sigue activo

#### Scenario: Aislamiento entre clubes

- **WHEN** un usuario del club A consulta un deporte que solo existe en el club B
- **THEN** recibe 404 y los datos del club B no cambian
