## 1. Base de datos y modelos

- [ ] 1.1 Migración: `tenant_id` nullable (FK, cascade) en `personal_access_tokens`
- [ ] 1.2 Modelo `PersonalAccessToken` propio y registro en `AppServiceProvider`
- [ ] 1.3 `HasApiTokens` en `User`
- [x] 1.4 Sacar `is_platform_admin` de `#[Fillable]`
- [ ] 1.5 Resolver la pregunta abierta sobre `suspended` y alinear migración, guía y código

## 2. Sanctum

- [ ] 2.1 `config/sanctum.php`: `expiration`, `token_prefix` `sportiva_`, sin `stateful` ni guard `web`

## 3. Auth

- [ ] 3.1 `LoginRequest` y `SelectTenantRequest`
- [ ] 3.2 `AuthController`: `login`, `selectTenant`, `me`, `logout`
- [ ] 3.3 Rutas bajo `/api/auth` con `throttle` en login
- [ ] 3.4 Comando `platform:create-admin`

## 4. ABM de clubes

- [x] 4.1 `database_name` derivado (`club_<slug>`); quitarlo de Store/Update
- [x] 4.2 `slug` inmutable en `UpdateTenantRequest`
- [ ] 4.3 `TenantResource`, `IndexTenantRequest`, paginación 20/100
- [ ] 4.4 `TenantPolicy` y `Gate::authorize`
- [ ] 4.5 Estados unificados

## 5. Tests

- [ ] 5.1 Auth: login OK y fallido, `select-tenant` con y sin membresía, token `pre-tenant` rechazado donde corresponda, vencimiento
- [ ] 5.2 ABM: 401, 403, CRUD, 422, `database_name` derivado, `slug` inmutable
- [ ] 5.3 Los 10 tests mínimos de `docs/guia-abm.md`

## 6. Cierre

- [ ] 6.1 `scramble:export` y commit de `docs/api/openapi.json`
- [ ] 6.2 Actualizar README (ya hay autenticación)
- [ ] 6.3 `pint --test`, `php artisan test` y `openspec validate platform-auth --strict`