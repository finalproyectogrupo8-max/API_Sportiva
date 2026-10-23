# platform-auth Specification

## Purpose
Login en dos pasos contra la base central: token pre-tenant (15 min, solo para elegir club), token de club (8 h) emitido por `select-tenant`, sesión y logout. Guía funcional: `docs/login-explicado.md`.

## Requirements

### Requirement: Login con token pre-tenant
El sistema SHALL autenticar con email y contraseña contra la base central y emitir un token con ability `tenant:select` que vence en 15 minutos.

#### Scenario: Credenciales válidas
- **WHEN** un usuario envía email y contraseña correctos a `POST /api/auth/login`
- **THEN** responde 200 con el token y la lista de clubes activos donde tiene membresía activa

#### Scenario: Credenciales inválidas
- **WHEN** el email o la contraseña son incorrectos
- **THEN** responde 401 con un mensaje genérico que no revela si el email existe

### Requirement: Selección de club
El sistema SHALL emitir un token atado a un club solo si el usuario tiene una membresía activa en un club con `status` `active`, y SHALL tomar el club del token en los requests posteriores, nunca del cliente. Los errores 403 SHALL incluir un campo `code` (`membership_inactive`, `tenant_inactive` o `tenant_required`).

#### Scenario: Membresía válida
- **WHEN** un usuario con token `pre-tenant` envía el slug de un club donde tiene membresía activa
- **THEN** el token `pre-tenant` se revoca y responde 200 con un token que lleva `tenant_id`

#### Scenario: Sin membresía
- **WHEN** el usuario envía el slug de un club donde no tiene membresía activa
- **THEN** responde 403 con `code = membership_inactive`

### Requirement: Sesión
El sistema SHALL exponer `GET /api/me`, `GET /api/me/tenants` y `POST /api/auth/logout`, y logout SHALL revocar el token usado. `GET /api/me/tenants` SHALL aceptar cualquier token vigente del usuario (pre-tenant, de club o de plataforma) y SHALL devolver el usuario (`id`, `name`, `email`, `is_platform_admin`) junto con sus clubes activos.

#### Scenario: Logout
- **WHEN** un usuario autenticado llama a logout
- **THEN** responde 204 y el token deja de ser válido

#### Scenario: Usuario y clubes con token pre-tenant
- **WHEN** un usuario con token `pre-tenant` llama a `GET /api/me/tenants`
- **THEN** responde 200 con `user` y la lista de clubes activos donde tiene membresía activa

#### Scenario: Usuario con token de plataforma
- **WHEN** un admin de plataforma llama a `GET /api/me/tenants` con su token de plataforma
- **THEN** responde 200 con `user.is_platform_admin = true`

### Requirement: Alta de administradores de plataforma
El sistema SHALL crear administradores de plataforma solo mediante `php artisan platform:create-admin` y MUST NOT ofrecer un registro público.

#### Scenario: Crear admin
- **WHEN** se ejecuta el comando con un email nuevo y una contraseña de al menos 12 caracteres
- **THEN** se crea un usuario con `is_platform_admin = true`

### Requirement: Asignación explícita de is_platform_admin
`is_platform_admin` MUST NOT ser asignable en masa.

#### Scenario: Mass assignment
- **WHEN** se crea o actualiza un usuario con datos que incluyen `is_platform_admin`
- **THEN** el campo no se modifica

### Requirement: Configuración de tokens
Los tokens SHALL tener vencimiento, el prefijo `sportiva_` y MUST NOT depender de cookies ni dominios stateful. Los tokens SHALL usar abilities `tenant:select` y `tenant:operate` según la operación.

#### Scenario: Token vencido
- **WHEN** se usa un token vencido
- **THEN** responde 401
