## ADDED Requirements

### Requirement: Deportes por club

Cada base de club SHALL tener `sports` con nombre único, `is_active` default true y timestamps. La baja del catálogo se expresa desactivando el deporte.

#### Scenario: Inicializar el catálogo

- **WHEN** se ejecutan las migrations del club
- **THEN** se crea `sports` con nombre único y activo por defecto

#### Scenario: Evitar nombres duplicados

- **WHEN** se intenta insertar dos deportes con el mismo nombre
- **THEN** la restricción unique rechaza el duplicado
