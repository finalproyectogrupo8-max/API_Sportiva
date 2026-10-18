## ADDED Requirements

### Requirement: Temporadas por club

Cada base de club SHALL tener `seasons` con nombre, fechas de inicio y fin, estado y timestamps. La base SHALL impedir que `end_date` sea anterior a `start_date`.

#### Scenario: Crear temporada con rango válido

- **WHEN** se guarda una temporada con fecha final igual o posterior a la inicial
- **THEN** la base acepta el registro

#### Scenario: Rechazar rango invertido

- **WHEN** se guarda una temporada con fecha final anterior a la inicial
- **THEN** la restricción CHECK rechaza el registro
