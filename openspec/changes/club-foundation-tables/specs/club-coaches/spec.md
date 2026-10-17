## ADDED Requirements

### Requirement: Profesores por club

Cada base de club SHALL tener `coaches` con nombre, apellido, estado, timestamps y baja lógica. Teléfono, email de contacto y vínculo a `club_users` SHALL ser opcionales; el vínculo, si existe, SHALL ser único.

#### Scenario: Crear profesor sin cuenta

- **WHEN** se registra un profesor que no inicia sesión en la plataforma
- **THEN** la base permite `club_user_id` nulo

#### Scenario: Asociar profesor con usuario del club

- **WHEN** se asocia un usuario existente de `club_users`
- **THEN** la base guarda la FK y no permite asociar ese usuario a una segunda ficha de profesor

#### Scenario: Baja lógica

- **WHEN** se da de baja un profesor
- **THEN** la fila conserva sus datos y registra `deleted_at`
