# club-calendar Specification

## Purpose
Partidos, entrenamientos, asistencia y calendario de cada club, con alcance "sus categorías" (club-calendar, TIZ-16).

## Requirements

### Requirement: Partidos y entrenamientos

La API SHALL exponer `apiResource('matches')` y `apiResource('trainings')` con permisos `matches.*` y `trainings.*`, filtros por categoría, rango de fechas y estado, y alcance "sus categorías". La baja SHALL ser lógica.

#### Scenario: Cargar el resultado

- **WHEN** se edita un partido con `our_score` y `opponent_score` sin mandar `status`
- **THEN** el partido queda en `played`

#### Scenario: Alcance del profe

- **WHEN** un profe consulta un partido de una categoría que no es suya
- **THEN** recibe 404

### Requirement: Asistencia

La API SHALL devolver el plantel de un partido o entrenamiento con la asistencia cargada (`attendance.view`) y SHALL guardar el lote completo (`attendance.record`) sin duplicar registros.

#### Scenario: Repetir la carga

- **WHEN** se manda dos veces la asistencia del mismo evento con estados distintos
- **THEN** queda un registro por jugador con el último estado

#### Scenario: Jugador de otra categoría

- **WHEN** se carga asistencia de un jugador que no está en el plantel de la categoría
- **THEN** responde 422

### Requirement: Calendario

La API SHALL devolver partidos y entrenamientos juntos y ordenados entre `from` y `to` (hasta 62 días), respetando el permiso y el alcance de cada uno.

#### Scenario: Semana del profe

- **WHEN** un profe pide el calendario de la semana
- **THEN** recibe los partidos y entrenamientos de sus categorías, ordenados por fecha
