# Design: Provisión Real de Tenants (TIZ-05)

## Arquitectura y Componentes

### 1. Job `App\Jobs\ProvisionTenant`
- **Contrato:** Implementa `ShouldQueue`, utiliza los traits `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels`.
- **Parámetros y Payload:** Solo recibe `int $tenantId` en el constructor. No almacena modelos Eloquent ni credenciales en el payload serializado.
- **Resiliencia y Timeouts:**
  - `$tries = 3`
  - `$backoff = [10, 60]`
  - `$timeout = 60` (estrictamente menor que `retry_after = 90` s).
- **Flujo de Ejecución (`handle`):**
  1. Busca el club mediante `Tenant::find($this->tenantId)`. Si no existe o tiene soft delete (`trashed()`), termina silenciosamente sin error.
  2. Si el club ya está `status = active`, finaliza (idempotencia).
  3. Ejecuta `TenantDatabase::create($tenant)` (`CREATE DATABASE IF NOT EXISTS`).
  4. Ejecuta `TenantDatabase::migrate($tenant)` (`migrate --database=tenant --path=database/migrations/tenant --force`).
  5. Actualiza el club a `status = active`.
  6. Si ocurre una excepción: marca el club como `status = failed` y loguea el error saneado.
- **Saneamiento de Logs:** El mensaje de error es saneado con regex para ocultar posibles contraseñas o credenciales en cadenas de conexión antes de loguear con contexto (`tenant_id`, `slug`).

### 2. Creación Unificada (`CreateTenantAction`)
Para evitar duplicación de lógica entre el controlador de la API y el comando CLI:
- Clase `App\Actions\Admin\CreateTenantAction`.
- Ejecuta una transacción de base central que:
  1. Crea el registro `Tenant` con `status = provisioning` y `database_name = 'club_'.$slug`.
  2. Si se proporciona un `AuditLogger`, registra la acción `tenant.created`.
  3. Despacha `ProvisionTenant::dispatch($tenant->id)->afterCommit()`.
  4. Retorna el modelo `Tenant`.

### 3. Decisión de Auditoría (`AuditLogger`)
- **Job de fondo (`ProvisionTenant`):** No registra filas en `audit_logs`. La especificación de auditoría (`admin-audit-log`) establece que el log audita *acciones ejecutadas por administradores en `/api/admin/*`*. Los cambios de estado automáticos realizados por procesos del sistema se registran en los logs de aplicación (`Log::info` / `Log::error`). Esto mantiene la integridad de la auditoría y evita actores nulos artificiales.
- **Reintento administrativo (`retry`):** Como es disparado por un administrador autenticado vía `POST /api/admin/tenants/{tenant}/retry`, **SÍ** se audita mediante `AuditLogger::recordUpdated($tenant)` registrando el cambio de estado de `failed` a `provisioning`.

### 4. Endpoint de Reintento (`POST /api/admin/tenants/{tenant}/retry`)
- **Ruta:** `POST /api/admin/tenants/{tenant}/retry` dentro del grupo protegido `['auth:sanctum', 'platform.admin']` con prefijo `admin`.
- **Autorización:** `Gate::authorize('retry', $tenant)` validado por `TenantPolicy@retry`.
- **Condición de Estado y Código HTTP:** Solo se permite reintentar si `$tenant->status === 'failed'`.
  - Si el club no está en `failed`, responde `409 Conflict` con código `tenant_not_failed` usando `ApiError::conflict()`.
- **Transición:** Cambia `status = provisioning`, registra auditoría y despacha `ProvisionTenant::dispatch($tenant->id)->afterCommit()`.
- **Respuesta:** `TenantResource` con código 200 (o 200 con el estado actualizado).

### 5. Comandos Artisan
- `tenants:create {slug} {name} {--sync}`: Valida el slug con `regex:/^[a-z0-9_]+$/`, máximo 50 caracteres y unicidad en `tenants`. Utiliza `CreateTenantAction`.
- `tenants:migrate {--tenant=} {--fresh} {--force}`: Itera sobre los clubes activos (`status = active`) y ejecuta `TenantDatabase::migrate($tenant, $fresh)`. No se detiene ante el primer fallo y retorna `exit code` 1 si hubo algún fallo.
