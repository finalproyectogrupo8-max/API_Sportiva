# Spec Delta

## ADDED Requirements

### Requirement: Coordinador de deporte de solo lectura

Un usuario cuyo único acceso relevante es `sport_coordinator` SHALL poder consultar temporadas, categorías y competencias; categorías y competencias SHALL limitarse a sus deportes asignados. SHALL NOT poder crear, editar o eliminar esos datos ni acceder a otros módulos del club mediante la API.

#### Scenario: Consultar las tres secciones permitidas

- **WHEN** un coordinador de deporte consulta temporadas, categorías y competencias
- **THEN** la API permite las lecturas y filtra categorías y competencias por sus deportes asignados

#### Scenario: Sin deportes asignados

- **WHEN** un coordinador de deporte consulta categorías o competencias y no tiene deportes asignados
- **THEN** recibe listas vacías y los detalles fuera de alcance responden 404

#### Scenario: Intentar modificar una sección permitida

- **WHEN** un coordinador de deporte intenta crear, editar o eliminar una temporada, categoría o competencia
- **THEN** la API responde 403 con `code: forbidden`

#### Scenario: Intentar acceder a otro módulo

- **WHEN** un coordinador de deporte solicita datos o ejecuta una acción de jugadores, partidos, inventario, equipo del club u otro módulo distinto de temporadas, categorías y competencias
- **THEN** la API responde 403 con `code: forbidden`

#### Scenario: Solapas del frontend

- **WHEN** una persona inicia sesión en `Front-Sportiva` con el rol `sport_coordinator`
- **THEN** el menú muestra únicamente las solapas Temporadas, Categorías y Competencias
- **AND** no muestra solapas de otros módulos del club
