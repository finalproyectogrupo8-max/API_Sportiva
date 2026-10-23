# Proposal: Provisión Real de Tenants (TIZ-05)

## Why

Actualmente, al crear un club a través de `POST /api/admin/tenants` o consola, el registro queda en estado `provisioning` en la base central `sportiva_platform`, pero no se crea su base de datos MySQL dedicada (`club_<slug>`) ni se ejecutan sus migraciones. Esto impide que el selector de club (`select-tenant`) y las operaciones de club funcionen, siendo un bloqueante crítico para la demo.

## What Changes

- Implementar el job en cola `App\Jobs\ProvisionTenant` (`ShouldQueue`) que crea la base de datos del club y ejecuta las migraciones de `database/migrations/tenant/` usando `App\Tenancy\TenantDatabase`, activando el club (`status = active`).
- Despachar `ProvisionTenant` con `->afterCommit()` desde `TenantController@store` dentro de la transacción de creación.
- Encapsular la creación de tenants en una Action compartida (`App\Actions\Admin\CreateTenantAction`) para ser reutilizada por el controlador y por el comando de consola.
- Crear el comando de consola `php artisan tenants:create {slug} {name}` con las mismas reglas de validación de slug que la API.
- Implementar el endpoint administrativo `POST /api/admin/tenants/{tenant}/retry` para reintentar la provisión de clubes en estado `failed`.
- Implementar el comando `php artisan tenants:migrate` para migrar bases de clubes existentes en lote.
- Documentar el uso de `QUEUE_CONNECTION=sync` para desarrollo local y demo en `docs/desarrollo-local.md`.

## Capabilities

### New Capabilities
- `tenant-provisioning`: Alta real, provisión asíncrona/síncrona, migración e idempotencia de bases de datos para clubes deportivos, junto con reintento ante fallos.

## Impact

- Código: nuevo job `App\Jobs\ProvisionTenant`, action `App\Actions\Admin\CreateTenantAction`, comandos `tenants:create` y `tenants:migrate`, endpoint `POST /api/admin/tenants/{tenant}/retry` en `TenantController` y método `retry` en `TenantPolicy`.
- Base de datos: se crean y migran bases MySQL `club_<slug>` correspondientes a cada club.
- API: nueva ruta `/api/admin/tenants/{tenant}/retry` bajo el middleware `['auth:sanctum', 'platform.admin']`.
- Contrato: actualización de `docs/api/openapi.json`.
- **BREAKING**: Ninguno para consumidores existentes.

## Fuera de alcance

- Separación de usuarios de MySQL en Laravel Cloud con usuario de provisión restringido (definido para deploy en nube).
- Servicio worker daemon permanente en `compose.yaml` (para desarrollo local y demo se utiliza `QUEUE_CONNECTION=sync`).
