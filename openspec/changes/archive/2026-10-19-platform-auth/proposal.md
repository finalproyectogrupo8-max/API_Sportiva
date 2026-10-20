## Why

Los endpoints `/api/admin/tenants` (PR #9) no se pueden usar: `User` no tiene `HasApiTokens` y no existe ningún endpoint que emita tokens, así que todo responde 401. Además, la revisión de los PR #8 y #9 detectó problemas de seguridad (`database_name` elegido por el cliente, `is_platform_admin` en `#[Fillable]`, Sanctum con configuración por defecto) y de convenciones (sin Resources, sin tests, paginación incorrecta).

## What Changes

- Login con email y contraseña que emite un token "pre-tenant" de vida corta, válido solo para elegir club.
- `select-tenant`: verifica la membresía y emite un token atado al club (`tenant_id` en `personal_access_tokens`).
- `me` y `logout`.
- Comando `platform:create-admin` (no hay registro público de admins).
- `HasApiTokens` en `User`.
- `database_name` lo deriva el servidor (`club_<slug>`) y no es editable; el `slug` es inmutable.
- `is_platform_admin` sale de `#[Fillable]` y se asigna explícitamente.
- Sanctum: vencimiento de tokens, `token_prefix` `sportiva_`, sin dominios stateful ni guard `web`.
- ABM de clubes: `TenantResource`, Form Request para `index`, paginación 20/100, `Gate::authorize` y estados unificados.
- Tests: los 10 mínimos de `docs/guia-abm.md` más los de auth.
- **BREAKING**: `POST /api/admin/tenants` deja de aceptar `database_name`; `PUT/PATCH` deja de aceptar `slug` y `database_name`.

## Capabilities

### New Capabilities
- `platform-auth`: login, selección de club, `me`, `logout`, `platform:create-admin` y configuración de tokens.
- `tenant-admin`: ABM de clubes para administradores de plataforma.

### Modified Capabilities
- Ninguna.

## Impact

- Código: `User`, `Tenant`, `TenantController`, Requests, Resources, `config/sanctum.php`, `routes/api.php`, nuevo `AuthController`, nuevo comando.
- Base central: columna `tenant_id` en `personal_access_tokens`.
- Contrato: `docs/api/openapi.json` se regenera.
- README: deja de decir "no hay autenticación".

## Fuera de alcance

`ProvisionTenant`, la conexión `tenant`, el middleware de tenant, el worker de la cola y los módulos del club.