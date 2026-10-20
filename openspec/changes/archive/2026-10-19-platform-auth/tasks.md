## 1. Base de datos y modelos

- [x] 1.1 Migración: `tenant_id` nullable (FK, cascade) en `personal_access_tokens`
- [x] 1.2 Modelo `PersonalAccessToken` propio y registro en `AppServiceProvider`
- [x] 1.3 `HasApiTokens` en `User`
- [x] 1.4 Sacar `is_platform_admin` de `#[Fillable]`
- [ ] 1.5 Resolver la pregunta abierta sobre `suspended` y alinear migración, guía y código

## 2. Sanctum

- [x] 2.1 `config/sanctum.php`: `expiration`, `token_prefix` `sportiva_`, sin `stateful` ni guard `web`

## 3. Auth

- [x] 3.1 `LoginRequest` y `SelectTenantRequest`
- [x] 3.2 `AuthController`: `login`, `selectTenant`, `me`, `logout`
- [x] 3.3 Rutas bajo `/api/auth` con `throttle` en login
- [x] 3.4 Comando `platform:create-admin`

## 4. ABM de clubes

- [x] 4.1 `database_name` derivado (`club_<slug>`); quitarlo de Store/Update
- [x] 4.2 `slug` inmutable en `UpdateTenantRequest`
- [x] 4.3 `TenantResource`, `IndexTenantRequest`, paginación 20/100
- [x] 4.4 `TenantPolicy` y `Gate::authorize`
- [x] 4.5 Estados unificados

## 5. Tests

- [x] 5.1 Auth: login OK y fallido, `select-tenant` con y sin membresía, token `pre-tenant` rechazado donde corresponda, vencimiento
- [x] 5.2 ABM: 401, 403, CRUD, 422, `database_name` derivado, `slug` inmutable
- [x] 5.3 Los 10 tests mínimos de `docs/guia-abm.md`

## 6. Cierre

- [x] 6.1 `scramble:export` y commit de `docs/api/openapi.json`
- [x] 6.2 Actualizar README (ya hay autenticación)
- [ ] 6.3 `pint --test`, `php artisan test` y `openspec validate platform-auth --strict` (Pint pasó; tests bloqueados por permisos MySQL y CLI OpenSpec no disponible)

Pendiente para el change siguiente:
- Auditoría de acciones del ABM de admin
- `sort` con lista blanca, `restore`, mover el ABM a `Admin/`, nombre del middleware (`platform-admin`)