## ADDED Requirements

### Requirement: Categorías por deporte y temporada

Cada base de club SHALL tener `categories`, con FK a `sports` y `seasons`, edades opcionales, estado activo y timestamps. La combinación deporte, temporada y nombre SHALL ser única.

#### Scenario: Crear categoría

- **WHEN** se crea una categoría con deporte y temporada existentes
- **THEN** la base guarda ambas referencias y la categoría

#### Scenario: Evitar categoría duplicada

- **WHEN** se intenta repetir el nombre para el mismo deporte y temporada
- **THEN** la restricción unique rechaza el duplicado
