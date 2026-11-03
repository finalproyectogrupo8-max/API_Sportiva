# admin-audit-log Specification

## Purpose

Proporciona un registro confiable e inmutable de las acciones administrativas sobre usuarios, membresías y clubes.

## Requirements

### Requirement: Registro de acciones administrativas

Toda alta, cambio o baja exitosa realizada mediante `/api/admin/*` SHALL registrar quién ejecutó la acción, qué acción ocurrió, el recurso afectado, los campos modificados, la IP y la fecha.

#### Scenario: Alta auditada

- **WHEN** un administrador crea un usuario
- **THEN** existe un registro `user.created` con el actor y el usuario creado

#### Scenario: Cambio auditado

- **WHEN** un administrador actualiza un club o una membresía
- **THEN** existe un registro con la acción, el actor, el recurso y los campos modificados

#### Scenario: Baja auditada

- **WHEN** un administrador desactiva un usuario o una membresía, o da de baja un club
- **THEN** existe un registro que identifica la acción y el recurso afectado

#### Scenario: Acción fallida

- **WHEN** una acción administrativa falla y su transacción se revierte
- **THEN** no queda registro de auditoría de esa acción

### Requirement: Exclusión de datos sensibles

Los registros MUST NOT contener contraseñas, hashes de contraseñas, tokens ni otros secretos de autenticación.

#### Scenario: Cambio de contraseña

- **WHEN** un administrador cambia la contraseña de un usuario
- **THEN** el registro indica que la contraseña cambió sin incluir su valor ni su hash

#### Scenario: Revocación de tokens

- **WHEN** una acción revoca tokens de un usuario o membresía
- **THEN** el registro no contiene los valores de los tokens

### Requirement: Consulta de auditoría de solo lectura

Los registros de auditoría SHALL ser de solo inserción. Los administradores de plataforma SHALL poder consultarlos mediante `GET /api/admin/audit-logs`; los usuarios del club con `audit.view` SHALL poder consultar la actividad de su propio club mediante `GET /api/audit-logs`. La API MUST NOT permitir modificar ni borrar registros.

#### Scenario: Consulta de actividad de plataforma

- **WHEN** un administrador de plataforma filtra el registro por tipo de recurso, actor o fecha
- **THEN** recibe una lista paginada de acciones con actor, recurso, cambios y fecha

#### Scenario: Consulta de actividad del club

- **WHEN** un miembro con `audit.view` consulta o filtra la actividad del club
- **THEN** recibe solo eventos de ese club con el actor y los cambios relevantes

#### Scenario: Sin permiso de auditoría del club

- **WHEN** un miembro sin `audit.view` consulta la actividad del club
- **THEN** responde 403

#### Scenario: Modificación o borrado

- **WHEN** un cliente intenta modificar o borrar un registro de auditoría
- **THEN** no existe una ruta que permita la operación y el modelo rechaza esas mutaciones
