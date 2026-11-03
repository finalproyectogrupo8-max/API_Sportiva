# support-tickets Specification

## Purpose

Recibir consultas públicas de contacto y permitir que administradores de plataforma las gestionen como tickets.

## Requirements

### Requirement: Envío público de una consulta

La API SHALL aceptar consultas sin autenticación en `POST /api/tickets`, validar nombre, email, asunto y mensaje, y devolver 201 con el identificador creado. `club_name` SHALL ser opcional; el cliente MUST NOT asignar estado, responsable, notas administrativas ni fecha de resolución.

#### Scenario: Consulta válida

- **WHEN** una persona envía los campos obligatorios y un mensaje de al menos diez caracteres
- **THEN** responde 201 con `data.id` y crea el ticket con estado `new`

#### Scenario: Campos inválidos o administrados por el servidor

- **WHEN** falta un campo obligatorio, el email no es válido, el mensaje es demasiado corto o el request intenta asignar campos administrativos
- **THEN** responde 422 con errores de validación y no crea el ticket

### Requirement: Protección contra spam

La API SHALL ignorar sin persistir un envío cuyo campo honeypot `website` esté completo, y SHALL limitar las solicitudes a cinco por hora por email e IP.

#### Scenario: Honeypot completo

- **WHEN** se envía una consulta válida con `website` no vacío
- **THEN** responde 201 con `data.id = 0` y no crea un ticket

#### Scenario: Exceso de solicitudes

- **WHEN** un email desde una IP supera cinco envíos en una hora
- **THEN** la siguiente solicitud responde 429

#### Scenario: Ruta de contacto antigua

- **WHEN** un cliente envía `POST /api/contacto`
- **THEN** responde 404; el formulario actual usa `POST /api/tickets`

### Requirement: Gestión administrativa de tickets

Solo un administrador de plataforma SHALL poder listar, consultar y actualizar tickets mediante `/api/admin/tickets`. El listado SHALL paginar y permitir filtrar por estado. Al resolver un ticket, el sistema SHALL guardar quién lo atendió y cuándo.

#### Scenario: Listado y filtro

- **WHEN** un administrador consulta los tickets o filtra por estado
- **THEN** recibe una lista paginada con los resultados correspondientes

#### Scenario: Resolver una consulta

- **WHEN** un administrador cambia el estado a `resolved`
- **THEN** se guardan `resolved_at` y `handled_by`; al cambiarlo luego a otro estado se limpia `resolved_at`

#### Scenario: Sin permisos de plataforma

- **WHEN** una persona no administradora consulta o actualiza tickets administrativos
- **THEN** responde 403
