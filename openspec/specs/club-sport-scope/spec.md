# club-sport-scope Specification

## Purpose

Limitar el acceso de coordinadores deportivos a las categorías, jugadores y profes de los deportes que tienen asignados en un club.

## Requirements

### Requirement: Asignar deportes a un integrante

La API SHALL permitir asignar `sport_ids` a un integrante con el rol `sport_coordinator` al crearlo o actualizarlo, desde el panel de plataforma o el equipo del club. Los identificadores deben corresponder a deportes del mismo club.

#### Scenario: Asignación válida

- **WHEN** se asigna a un coordinador uno o más deportes existentes del club
- **THEN** los deportes quedan asociados y se devuelven en su perfil

#### Scenario: Deporte inexistente

- **WHEN** se intenta asociar un deporte inexistente
- **THEN** responde 422 y no persiste una asignación parcial

### Requirement: Filtrar recursos por deportes asignados

El permiso con alcance `sport` SHALL limitar categorías, jugadores y profes a los deportes asignados al usuario. Si no tiene deportes, las listas quedan vacías y los detalles fuera del alcance responden 404. Al combinar roles, SHALL aplicarse el alcance más amplio (`all` > `sport` > `own`).

#### Scenario: Acceso dentro y fuera del alcance

- **WHEN** el coordinador consulta recursos de deportes asignados y no asignados
- **THEN** ve los primeros y recibe 404 para detalles de los segundos

#### Scenario: Sin deportes asignados

- **WHEN** el coordinador no tiene deportes asignados
- **THEN** obtiene listas vacías y 404 al consultar un detalle

#### Scenario: Alcance más amplio

- **WHEN** un usuario reúne roles con alcances `all` y `sport`
- **THEN** prevalece `all`
