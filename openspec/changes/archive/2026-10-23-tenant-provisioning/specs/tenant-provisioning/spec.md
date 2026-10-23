# Spec Delta: Provisión de Tenants

## Purpose

Define el proceso de provisión, migración, activación y reintento de bases de datos dedicadas para los clubes de la plataforma.

## ADDED Requirements

### Requirement: Provisión de base de datos de club
Al crear un club mediante `POST /api/admin/tenants` o mediante la consola, el sistema SHALL registrar el club y encolar el job `ProvisionTenant`, el cual SHALL crear la base de datos `club_<slug>`, aplicar todas las migraciones de club y establecer el estado en `active`.

#### Scenario: Provisión exitosa en modo síncrono
- **WHEN** un administrador crea un club con slug válido en entorno con cola síncrona
- **THEN** responde 201 con el recurso del club, el estado en la base es `active` y la base de datos del club contiene todas las tablas de `database/migrations/tenant/`

#### Scenario: Falla de provisión
- **WHEN** ocurre un error irrecuperable durante la creación o migración de la base del club
- **THEN** el club queda en estado `failed` y el error se registra en los logs del sistema sin exponer credenciales

#### Scenario: Idempotencia de provisión
- **WHEN** el job de provisión se ejecuta nuevamente sobre un club ya activo o con tablas ya existentes
- **THEN** el job completa exitosamente sin duplicar tablas ni alterar datos existentes

### Requirement: Reintento de provisión de club
El endpoint `POST /api/admin/tenants/{tenant}/retry` SHALL permitir reintentar la provisión de un club que se encuentra en estado `failed`, estableciendo su estado en `provisioning` y re-encolando el job `ProvisionTenant`.

#### Scenario: Reintento de club fallido
- **WHEN** un administrador de plataforma invoca `POST /api/admin/tenants/{tenant}/retry` sobre un club con `status = failed`
- **THEN** responde con el recurso del club, registra auditoría del cambio de estado y despacha la provisión nuevamente

#### Scenario: Intento de reintento sobre club no fallido
- **WHEN** se invoca `POST /api/admin/tenants/{tenant}/retry` sobre un club cuyo estado no es `failed`
- **THEN** responde 409 Conflict con código `tenant_not_failed`

#### Scenario: Sin autorización de administrador
- **WHEN** un usuario que no es administrador de plataforma invoca el endpoint de reintento
- **THEN** responde 403 Forbidden con código `forbidden`

### Requirement: Creación por consola
El comando `tenants:create` SHALL validar el slug con las mismas reglas que la API (`^[a-z0-9_]+$`, máximo 50 caracteres y único) y disparar el job de provisión de la misma forma que el controlador.

#### Scenario: Slug inválido en comando
- **WHEN** se ejecuta `tenants:create` con caracteres no permitidos o slug duplicado
- **THEN** informa el error de validación por consola y termina con código de fallo
