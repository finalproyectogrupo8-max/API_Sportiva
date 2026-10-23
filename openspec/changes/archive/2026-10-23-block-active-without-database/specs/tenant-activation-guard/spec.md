## ADDED Requirements

### Requirement: Restricción de Activación de Clubes no Provisionados

La API administrativa MUST rechazar cualquier cambio de `status` vía `PATCH /api/admin/tenants/{tenant}` (a `active` o a `suspended`) si el club se encuentra en estado `provisioning` o `failed`. El estado de esos clubes solo lo cambia el job `ProvisionTenant`. Si se permitiera pasarlos a `suspended`, después se podrían activar con `suspended → active` sin que exista la base.

#### Scenario: Intento de activar un club en aprovisionamiento
- **GIVEN** un club en estado `provisioning`
- **WHEN** un administrador de plataforma envía `PATCH /api/admin/tenants/{id}` con `{ "status": "active" }`
- **THEN** la API responde `422 Unprocessable Content`
- **AND** el cuerpo de la respuesta contiene un error de validación en la clave `status` con mensaje en español
- **AND** el estado del club en la base central permanece inalterado (`provisioning`)
- **AND** no se registra ninguna entrada en `audit_logs`

#### Scenario: Intento de activar un club en estado fallido
- **GIVEN** un club en estado `failed`
- **WHEN** un administrador de plataforma envía `PATCH /api/admin/tenants/{id}` con `{ "status": "active" }`
- **THEN** la API responde `422 Unprocessable Content`
- **AND** el cuerpo de la respuesta contiene un error de validación en la clave `status`
- **AND** el estado del club en la base central permanece inalterado (`failed`)
- **AND** no se registra ninguna entrada en `audit_logs`

#### Scenario: Intento de suspender un club sin base
- **GIVEN** un club en estado `provisioning` o `failed`
- **WHEN** un administrador de plataforma envía `PATCH /api/admin/tenants/{id}` con `{ "status": "suspended" }`
- **THEN** la API responde `422 Unprocessable Content` con un error de validación en la clave `status`
- **AND** el estado del club permanece inalterado

#### Scenario: Cambio de nombre de un club sin base
- **GIVEN** un club en estado `provisioning`
- **WHEN** un administrador de plataforma envía `PATCH /api/admin/tenants/{id}` con `{ "name": "Nuevo nombre" }`
- **THEN** la API responde `200 OK` y el estado sigue en `provisioning`

#### Scenario: Reactivación de un club suspendido
- **GIVEN** un club en estado `suspended`
- **WHEN** un administrador de plataforma envía `PATCH /api/admin/tenants/{id}` con `{ "status": "active" }`
- **THEN** la API responde `200 OK`
- **AND** el estado del club en la base central se actualiza a `active`
- **AND** se crea un registro de auditoría con acción `tenant.updated`

#### Scenario: Suspensión de un club activo
- **GIVEN** un club en estado `active`
- **WHEN** un administrador de plataforma envía `PATCH /api/admin/tenants/{id}` con `{ "status": "suspended" }`
- **THEN** la API responde `200 OK`
- **AND** el estado del club en la base central se actualiza a `suspended`

#### Scenario: Idempotencia en actualización a mismo estado activo
- **GIVEN** un club en estado `active`
- **WHEN** un administrador de plataforma envía `PATCH /api/admin/tenants/{id}` con `{ "status": "active" }`
- **THEN** la API responde `200 OK`
- **AND** no se genera registro de auditoría por ausencia de cambios reales

