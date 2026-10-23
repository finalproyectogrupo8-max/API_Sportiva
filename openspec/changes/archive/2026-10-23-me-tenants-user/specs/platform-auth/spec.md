## MODIFIED Requirements

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
