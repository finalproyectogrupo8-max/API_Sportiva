## ADDED Requirements

### Requirement: Emisión de Token de Plataforma en Login

El sistema SHALL emitir un token adicional de plataforma con ability `platform:admin` y vencimiento según `SANCTUM_PLATFORM_TTL` durante el login de un usuario con `is_platform_admin = true`. Para usuarios no administradores, el sistema MUST NOT emitir este token ni incluir sus campos en la respuesta.

#### Scenario: Login exitoso de administrador de plataforma
- **GIVEN** un usuario con `is_platform_admin = true`
- **WHEN** envía sus credenciales correctas a `POST /api/auth/login`
- **THEN** la respuesta HTTP 200 incluye `token`, `expires_at`, `user`, `tenants`, `platform_token` y `platform_expires_at`
- **AND** en la base de datos se crea un token con ability `platform:admin`, `tenant_id = null` y vigencia equivalente a `platform_ttl` minutos

#### Scenario: Login exitoso de usuario regular (no admin)
- **GIVEN** un usuario con `is_platform_admin = false`
- **WHEN** envía sus credenciales correctas a `POST /api/auth/login`
- **THEN** la respuesta HTTP 200 incluye `token`, `expires_at`, `user` y `tenants`
- **AND** los campos `platform_token` y `platform_expires_at` no están presentes en el cuerpo JSON
- **AND** no se crea ningún token con ability `platform:admin` para ese usuario

### Requirement: Exigencia de Token de Plataforma en Rutas Administrativas

Las rutas bajo `/api/admin/*` SHALL exigir autenticación con un token que posea la ability `platform:admin` y pertenezca a un usuario con `is_platform_admin = true`.

#### Scenario: Petición a /api/admin/* con token de plataforma válido
- **WHEN** un administrador envía una petición a `/api/admin/tenants` utilizando el Bearer token de plataforma
- **THEN** responde HTTP 200

#### Scenario: Petición a /api/admin/* con token pre-tenant o de club
- **WHEN** un usuario (incluso siendo admin) envía una petición a `/api/admin/tenants` utilizando su token pre-tenant o de club
- **THEN** responde HTTP 403 con `{ "message": "No tenés permiso para esta acción.", "code": "forbidden" }`

#### Scenario: Petición con ability platform:admin en usuario no admin
- **WHEN** un usuario con `is_platform_admin = false` presenta un token con ability `platform:admin`
- **THEN** responde HTTP 403 con `{ "message": "No tenés permiso para esta acción.", "code": "forbidden" }`

### Requirement: Aislamiento del Token de Plataforma

El token de plataforma MUST NOT permitir la navegación operativa en endpoints de club ni la invocación del selector de club.

#### Scenario: Uso de token de plataforma en endpoint de club
- **WHEN** se envía una petición a `/api/me` con el token de plataforma
- **THEN** responde HTTP 403 con `{ "code": "tenant_required" }`

#### Scenario: Uso de token de plataforma en selección de club
- **WHEN** se envía una petición a `POST /api/auth/select-tenant` con el token de plataforma
- **THEN** responde HTTP 403 con `{ "code": "forbidden" }`

