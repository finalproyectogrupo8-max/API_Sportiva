# club-callups Specification

## Purpose
Convocatorias a partidos: armado con jugadores habilitados, publicación, links de WhatsApp y confirmación por link personal (club-callups, TIZ-33).

## Requirements

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

### Requirement: Aviso por mail al publicar la convocatoria

Al publicar la convocatoria, la API SHALL encolar un aviso por mail al contacto principal con email de cada convocado, con los datos del partido y el link personal para confirmar. El envío MUST ser asíncrono: la respuesta de publicar no espera a que salgan los mails. Los convocados sin contacto principal con email no reciben mail y la publicación no falla por eso.

#### Scenario: Publicar avisa a los contactos

- **WHEN** el profe publica una convocatoria con tres jugadores cuyos contactos principales tienen email
- **THEN** se envía un mail a cada contacto, con el partido y el link personal de su jugador

#### Scenario: Convocado sin email

- **WHEN** se publica una convocatoria y un convocado no tiene contacto principal con email
- **THEN** la publicación responde bien, ese convocado no recibe mail y los demás sí

### Requirement: Un mail por dirección

Si la misma dirección de email es el contacto principal de varios convocados del mismo partido, la API SHALL mandarle un solo mail que incluya el link personal de cada uno de esos convocados. La comparación de direcciones MUST ignorar mayúsculas y espacios.

#### Scenario: Tutor con dos hijos convocados

- **WHEN** se publica una convocatoria con dos hermanos cuyo contacto principal es el mismo email
- **THEN** ese email recibe un solo mail con los dos nombres y los dos links personales

### Requirement: Volver a publicar

Al volver a publicar una convocatoria ya publicada, la API SHALL avisar a todos los convocados con un mail que diga que hubo cambios si el partido cambió después de la publicación anterior. Si no hubo cambios, SHALL avisar solo a los convocados que todavía no recibieron el aviso.

#### Scenario: Republicar después de un cambio

- **WHEN** se cambia el horario de un partido con la convocatoria publicada y el profe vuelve a publicar
- **THEN** todos los contactos reciben un mail que avisa que hubo cambios, con los datos nuevos

#### Scenario: Republicar sin cambios

- **WHEN** el profe suma un jugador a una convocatoria publicada y vuelve a publicar sin cambiar el partido
- **THEN** solo el contacto del jugador nuevo recibe el mail

### Requirement: Recordatorio a los pendientes

La API SHALL exponer `POST /api/matches/{match}/callup/remind`, que reenvía el aviso por mail solo a los convocados que siguen sin responder. Requiere el permiso `callups.publish` dentro de su alcance y la convocatoria publicada. Los convocados que ya respondieron MUST NOT recibir el recordatorio.

#### Scenario: Recordar a los pendientes

- **WHEN** el profe pide el recordatorio con dos convocados que confirmaron y uno pendiente
- **THEN** solo el contacto del pendiente recibe el mail

#### Scenario: Convocatoria sin publicar

- **WHEN** se pide el recordatorio de una convocatoria que no está publicada
- **THEN** responde 422 y no se envía ningún mail

#### Scenario: Sin permiso

- **WHEN** un usuario sin `callups.publish` pide el recordatorio
- **THEN** responde 403 y no se envía ningún mail

#### Scenario: Fuera de alcance

- **WHEN** un profe pide el recordatorio de un partido de una categoría que no es suya
- **THEN** responde 404, como el resto de los recursos fuera de alcance, y no se envía ningún mail

### Requirement: Estado del aviso en la convocatoria

La convocatoria SHALL devolver, por cada convocado, si su contacto principal tiene email y la fecha del último aviso por mail enviado (o null si nunca se le mandó), para que el profe sepa a quién avisar por otro medio.

#### Scenario: Ver a quién le llegó el aviso

- **WHEN** el profe abre una convocatoria publicada después de que salieron los mails
- **THEN** cada convocado con email muestra la fecha del aviso y los que no tienen email lo indican

### Requirement: Aviso sin duplicados ante reintentos

Un mismo aviso SHALL enviarse una sola vez por dirección aunque el procesamiento en segundo plano se reintente o se ejecute más de una vez.

#### Scenario: Reintento del envío

- **WHEN** el procesamiento del aviso se ejecuta dos veces para la misma publicación
- **THEN** cada dirección recibe un solo mail
