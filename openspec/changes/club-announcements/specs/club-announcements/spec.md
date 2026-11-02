# Spec Delta

## Purpose

Comunicados por mail del club a jugadores, tutores, profes y staff: borradores, audiencia según los datos del club, vista previa, envío sin duplicados e historial con el estado de cada destinatario (club-announcements, TIZ-24).

## ADDED Requirements

### Requirement: Borradores de comunicados

La API SHALL permitir crear, listar, ver, editar y borrar comunicados con asunto, cuerpo en Markdown y audiencia (`announcements.send` para escribir, `announcements.view` para leer). Un comunicado nace en `draft` y SHALL poder editarse o borrarse solo mientras sigue en `draft`. El listado SHALL paginar (20 por defecto, 100 como máximo).

#### Scenario: Crear un borrador

- **WHEN** el coordinador crea un comunicado con asunto, cuerpo y la categoría Sub-15 como audiencia
- **THEN** responde 201 con el comunicado en `draft`

#### Scenario: Editar un comunicado enviado

- **WHEN** se intenta editar o borrar un comunicado que ya no está en `draft`
- **THEN** responde 409 y el comunicado no cambia

#### Scenario: Datos inválidos

- **WHEN** se crea un comunicado sin asunto o con una audiencia vacía
- **THEN** responde 422 con los errores por campo

### Requirement: Audiencia del comunicado

La audiencia SHALL combinar:

- categorías, con jugadores, tutores o los dos;
- los profes de esas categorías;
- el staff con ciertos roles;
- jugadores sueltos;
- usuarios del club sueltos.

El jugador recibe el mail en su contacto con relación `self`. Los tutores lo reciben en sus demás contactos con email. Se toman solo los jugadores activos en la categoría.

#### Scenario: Categoría con jugadores y tutores

- **WHEN** la audiencia es Sub-15 con jugadores y tutores
- **THEN** los destinatarios son los contactos con email de los jugadores activos de Sub-15, tanto el propio jugador como sus tutores

#### Scenario: Profes de la categoría

- **WHEN** la audiencia incluye a los profes de Sub-15
- **THEN** reciben el mail los profes asignados a Sub-15 que tienen email

### Requirement: Vista previa de la audiencia

La API SHALL exponer `POST /api/announcements/preview-audience`. Con una audiencia, devuelve la cantidad de direcciones distintas que recibirían el mail, la cantidad de personas incluidas sin email y una muestra de hasta 10 nombres. No envía nada.

#### Scenario: Ver a cuántos llega

- **WHEN** el profe pide la vista previa de su categoría con 18 jugadores, 15 con email y uno de ellos hermano de otro con el mismo tutor
- **THEN** responde la cantidad de direcciones distintas, cuántos quedan sin email y una muestra de nombres, sin mandar mails

### Requirement: Envío del comunicado

La API SHALL exponer `POST /api/announcements/{announcement}/send`. Al enviar, el comunicado pasa a `sending`, la lista de destinatarios queda congelada y se encola un mail por dirección distinta. La comparación ignora mayúsculas y espacios. Cuando terminan todos los envíos, pasa a `sent`. Solo se puede enviar un comunicado en `draft`, y uno que no tiene ningún destinatario con email responde 422.

#### Scenario: Un mail por dirección

- **WHEN** se envía un comunicado a "Sub-15 + tutores" y una madre es tutora de dos jugadores de Sub-15
- **THEN** esa madre recibe un solo mail

#### Scenario: Enviar dos veces

- **WHEN** se pide enviar un comunicado que ya está en `sending` o `sent`
- **THEN** responde 409 y no se manda ningún mail nuevo

#### Scenario: Sin destinatarios

- **WHEN** se envía un comunicado cuya audiencia no tiene ninguna dirección de email
- **THEN** responde 422 y el comunicado sigue en `draft`

#### Scenario: Responder el mail

- **WHEN** un destinatario responde el mail del comunicado
- **THEN** la respuesta va al email del usuario que lo envió

### Requirement: Historial y estado por destinatario

Cada comunicado SHALL mostrar quién lo envió, cuándo, a cuántas direcciones, y cuántas se entregaron o fallaron. La API SHALL listar sus destinatarios con su estado (`pending`, `sent` o `failed`). Un envío que falla SHALL reintentarse antes de quedar en `failed`, y un fallo no frena los demás.

#### Scenario: Envío con un fallo

- **WHEN** se envía un comunicado a 20 direcciones y una falla después de los reintentos
- **THEN** el comunicado queda en `sent`, con 19 entregados y 1 fallido, y el destinatario fallido muestra el error

### Requirement: Alcance de los comunicados

Quien tiene `announcements.send` con alcance limitado SHALL poder incluir en la audiencia solo categorías y jugadores dentro de su alcance, y MUST NOT poder incluir staff por rol ni usuarios del club. Con alcance limitado, `announcements.view` SHALL mostrar solo los comunicados que creó el mismo usuario. Los comunicados de un club MUST NOT ser visibles desde otro.

#### Scenario: Profe a una categoría ajena

- **WHEN** un profe crea o envía un comunicado a una categoría que no es suya
- **THEN** responde 403 y no se guarda ni se envía

#### Scenario: Profe a sus categorías

- **WHEN** un profe envía un comunicado a los tutores de su categoría
- **THEN** se envía normalmente

#### Scenario: Aislamiento entre clubes

- **WHEN** un usuario del club A pide un comunicado del club B
- **THEN** responde 404

### Requirement: Contenido del mail

El mail SHALL usar la plantilla con la marca Sportiva, mostrar el nombre del club, el asunto y el cuerpo en Markdown, y llevar un pie que explique por qué lo recibe y cómo dejar de recibirlos (pidiéndoselo al club). El HTML que venga en el cuerpo MUST mostrarse como texto y no interpretarse.

#### Scenario: HTML en el cuerpo

- **WHEN** el cuerpo de un comunicado incluye `<script>` o un link `javascript:`
- **THEN** el mail lo muestra como texto y el link no es ejecutable
