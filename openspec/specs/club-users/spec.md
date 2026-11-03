# club-users Specification

## Purpose

Administrar el equipo de cada club con roles y deportes, e invitar a personas nuevas sin habilitar el registro público.

## Requirements

### Requirement: Consultar el equipo del club

`GET /api/club-users` SHALL devolver por defecto solo integrantes activos y los campos mínimos `id` y `display_name`. Con `team=1`, solo quien tenga `club_users.view` puede consultar el equipo completo con email, estado, roles y deportes.

#### Scenario: Selector de integrantes

- **WHEN** un miembro consulta el listado sin `team=1`
- **THEN** recibe solo personas activas con identificador y nombre

#### Scenario: Listado administrativo del equipo

- **WHEN** un usuario con `club_users.view` consulta `?team=1`
- **THEN** recibe los integrantes con roles, deportes y estado

### Requirement: Invitar e incorporar integrantes

Quien tenga `club_users.invite` SHALL poder sumar un integrante indicando email y roles. Para una persona sin cuenta existente, `name` es obligatorio. Si no se envía una contraseña, el sistema crea la cuenta inactiva, la membresía del club y un enlace de invitación para elegirla; si se envía una contraseña válida, crea la cuenta activa sin invitación. Una persona con cuenta existente se incorpora sin crear otra cuenta ni enviar invitación.

#### Scenario: Invitar a una persona nueva

- **WHEN** un administrador del club incorpora un email nuevo con nombre y roles válidos
- **THEN** responde 201, crea el perfil invitado y envía el email de invitación

#### Scenario: Incorporar una cuenta existente

- **WHEN** se incorpora un email con cuenta existente
- **THEN** se reutiliza esa cuenta y no se envía una invitación nueva

#### Scenario: Reenviar invitación

- **WHEN** se reenvía una invitación a una cuenta que aún no la aceptó
- **THEN** se envía un enlace nuevo; una cuenta que no está invitada responde 409

### Requirement: Actualizar roles, deportes y acceso

Quien tenga `club_users.update` SHALL poder cambiar roles y deportes dentro de sus propios permisos. Quien tenga `club_users.disable` SHALL poder deshabilitar o reactivar el acceso del integrante. El último administrador activo MUST NOT perder su rol ni ser deshabilitado.

#### Scenario: Cambiar roles o estado

- **WHEN** se actualizan roles, deportes o estado con permisos suficientes
- **THEN** el equipo y el acceso al club reflejan los cambios

#### Scenario: Evitar escalada de permisos

- **WHEN** el solicitante intenta asignar un rol con permisos o alcance superiores a los propios
- **THEN** responde 403 y no modifica el integrante

#### Scenario: Proteger al último administrador

- **WHEN** se intenta quitar el rol o deshabilitar al último administrador activo
- **THEN** responde 409 con `code: last_admin`

### Requirement: Aislamiento entre clubes

Los integrantes, roles y deportes SHALL limitarse al club del token y MUST NOT modificarse desde otro club.

#### Scenario: Consultar equipo de otro club

- **WHEN** un usuario opera con el token de un club distinto
- **THEN** no obtiene ni modifica los integrantes del club ajeno
