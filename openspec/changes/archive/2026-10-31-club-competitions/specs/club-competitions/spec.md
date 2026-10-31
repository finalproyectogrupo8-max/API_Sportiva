## ADDED Requirements

### Requirement: Competencias con requisitos

La API SHALL permitir crear, listar, editar, copiar y dar de baja competencias (`competitions.*`) con sus requisitos de habilitación y las categorías que las juegan. Las categorías SHALL ser del deporte de la competencia.

#### Scenario: Alta completa

- **WHEN** el admin crea "Liga Metropolitana" con 4 requisitos y 2 categorías de básquet
- **THEN** responde 201 con los requisitos en orden y las categorías

#### Scenario: Categoría de otro deporte

- **WHEN** se asigna una categoría de fútbol a una competencia de básquet
- **THEN** responde 422 y no se crea nada

#### Scenario: Copiar a la temporada siguiente

- **WHEN** se copia una competencia a otra temporada
- **THEN** la copia tiene los mismos requisitos y ninguna categoría

#### Scenario: Alcance del profe

- **WHEN** un profe consulta una competencia que no juega ninguna de sus categorías
- **THEN** recibe 404
