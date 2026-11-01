## ADDED Requirements

### Requirement: Convocatoria a un partido

La API SHALL permitir armar la lista de convocados de un partido solo con jugadores del plantel habilitados, sin números de camiseta repetidos, y publicarla (`callups.update`, `callups.publish`, alcance "sus categorías"). Al publicar SHALL devolver la página pública, el mensaje para el grupo y un link personal por jugador.

#### Scenario: Jugador no habilitado

- **WHEN** el profe convoca a un jugador al que le falta un requisito obligatorio
- **THEN** responde 422 y dice qué le falta

#### Scenario: Cambios después de publicar

- **WHEN** se cambia el lugar de un partido con la convocatoria publicada
- **THEN** la convocatoria queda marcada para volver a avisar

### Requirement: Páginas públicas de la convocatoria

La API SHALL exponer sin login la convocatoria publicada y la confirmación con el link personal, mostrando solo nombre e inicial del apellido y número de cada convocado.

#### Scenario: Confirmar asistencia

- **WHEN** la familia abre el link personal y responde "Voy"
- **THEN** la respuesta queda guardada y el profe la ve en la convocatoria

#### Scenario: Token inválido

- **WHEN** se abre un link con un token que no existe
- **THEN** responde 404
