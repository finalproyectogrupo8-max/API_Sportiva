# Proposal: Token Dedicado de Plataforma para el Panel de Administración (TIZ-07)

## Why

Actualmente, los administradores de plataforma operan sobre las rutas `/api/admin/*` utilizando el token de autenticación inicial emitido en el login (token pre-tenant con ability `tenant:select`). Dicho token posee un TTL corto de 15 minutos (`SANCTUM_PRE_TENANT_TTL`), diseñado únicamente para que un usuario elija un club en el selector.
En consecuencia, un administrador que gestione clubes o usuarios en el panel `/admin` ve caducada su sesión a los 15 minutos de forma abrupta.
Asimismo, el token pre-tenant no fue concebido para tareas de administración de plataforma, mezclando permisos y capacidades de scopes operativos distintos.

Es necesario emitir un token dedicado de plataforma (`platform_token`) con ability específica `platform:admin` y un TTL configurable más extenso (480 minutos por defecto, `SANCTUM_PLATFORM_TTL`), exigiendo dicha ability en todas las rutas administrativas `/api/admin/*`.

## What Changes

- Configuración: agregar `SANCTUM_PLATFORM_TTL=480` en `.env.example` y `'platform_ttl' => (int) env('SANCTUM_PLATFORM_TTL', 480)` en `config/sanctum.php`.
- Login: `AuthController@login` detecta si el usuario posee `is_platform_admin = true`. En caso afirmativo, emite además del token pre-tenant un token de plataforma con ability `platform:admin`, sin `tenant_id` y con expiración calculada a partir de `sanctum.platform_ttl`.
- Respuesta de Login: `LoginResource` incluye condicionalmente los campos `platform_token` y `platform_expires_at` únicamente para administradores de plataforma. Para usuarios estándar, estos campos quedan completamente omitidos de la respuesta JSON.
- Protección de Rutas Admin: el middleware del grupo `/api/admin/*` pasa a ser `['auth:sanctum', 'abilities:platform:admin', 'platform.admin']`, asegurando que tokens pre-tenant o tokens de club no puedan operar sobre endpoints de administración.
- Restricción de Alcance del Token de Plataforma: el token de plataforma carece de `tenant_id` (por lo que es rechazado con 403 `tenant_required` en rutas de club) y carece de ability `tenant:select` (siendo rechazado con 403 `forbidden` en `POST /api/auth/select-tenant`).
- Revocación: el logout revoca únicamente el token utilizado en la petición. La desactivación del usuario o el cambio de contraseña desde `UserController` revoca todos los tokens, incluyendo el token de plataforma.

## Capabilities

### New Capabilities
- `platform-admin-token`: Emisión, validación y ciclo de vida de tokens de acceso dedicados para la administración de la plataforma Sportiva.

## Impact

- Código: `config/sanctum.php`, `.env.example`, `app/Http/Controllers/Auth/AuthController.php`, `app/Http/Resources/Auth/LoginResource.php`, `routes/api.php`.
- API: la respuesta de `POST /api/auth/login` contiene opcionalmente `platform_token` y `platform_expires_at` para admins. Las rutas `/api/admin/*` exigen el token con ability `platform:admin`.
- Tests: nueva suite en `tests/Feature/Auth/PlatformAdminTokenTest.php`.

