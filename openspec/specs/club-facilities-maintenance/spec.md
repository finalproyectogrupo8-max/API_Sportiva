# club-facilities-maintenance Specification

## Purpose

Administrar instalaciones del club, reportes de reparación y registros de mantenimiento preventivo o correctivo.

## Requirements

### Requirement: Administrar instalaciones y componentes

La API SHALL permitir consultar y administrar instalaciones y sus componentes bajo autenticación de club y permisos correspondientes. El componente solo puede asociarse a la instalación a la que pertenece.

#### Scenario: Consultar una instalación

- **WHEN** un usuario autorizado lista las instalaciones
- **THEN** recibe resultados paginados con sus componentes

#### Scenario: Componente de otra instalación

- **WHEN** se intenta asociar a una instalación un componente perteneciente a otra
- **THEN** responde 422 y la asociación no cambia

### Requirement: Reportar y asignar reparaciones

Un usuario autorizado SHALL poder crear un reporte asociado a una instalación, opcionalmente a un componente, con título, descripción y prioridad. Un responsable de mantenimiento SHALL poder asignar el reporte a un integrante del club.

#### Scenario: Reporte de reparación

- **WHEN** un miembro reporta un problema válido
- **THEN** se crea con estado `open`

#### Scenario: Asignación

- **WHEN** mantenimiento asigna un reporte abierto a un integrante
- **THEN** pasa a `assigned` y se notifica a la persona asignada

### Requirement: Resolver reparaciones

Mantenimiento SHALL poder resolver un reporte asignado registrando descripción, fecha y costo opcional. La resolución SHALL crear un registro de mantenimiento asociado; si hay un componente, debe actualizarse su condición e inspección. No se puede resolver un reporte sin asignar ni resolverlo dos veces.

#### Scenario: Resolución válida

- **WHEN** el responsable resuelve un reporte asignado
- **THEN** el reporte queda `resolved`, se crea el registro de mantenimiento y se notifica a quien lo reportó

#### Scenario: Resolución no permitida

- **WHEN** se intenta resolver un reporte sin asignar o ya resuelto
- **THEN** responde 409 y no crea otro registro

### Requirement: Administrar registros de mantenimiento

La API SHALL exponer el ABM de `maintenance-records` con permisos de mantenimiento, paginación y filtros por instalación y fecha. Los registros creados al resolver una reparación SHALL quedar vinculados a su solicitud.

#### Scenario: Filtrar registros

- **WHEN** un usuario autorizado filtra registros por instalación o rango de fechas
- **THEN** recibe únicamente los registros coincidentes del club actual

### Requirement: Aislamiento por club

Las instalaciones, reparaciones y registros SHALL limitarse a la base del club del token.

#### Scenario: Acceso a datos de otro club

- **WHEN** un usuario intenta leer o modificar un recurso de otro club
- **THEN** responde 404
