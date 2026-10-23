# Tasks: Provisión Real de Tenants (TIZ-05)

## 1. Action y Job de Provisión

- [ ] 1.1 Crear el Job `App\Jobs\ProvisionTenant` (`ShouldQueue`) con `tries = 3`, `backoff = [10, 60]`, `timeout = 60` e inyección de `int $tenantId`.
- [ ] 1.2 Implementar en `ProvisionTenant@handle` la creación (`TenantDatabase::create`), migración (`TenantDatabase::migrate`) y activación (`status = active`) de forma idempotente.
- [ ] 1.3 Implementar manejo de fallos en `ProvisionTenant` pasando el estado a `failed` y logueando sin secretos.
- [ ] 1.4 Crear la Action `App\Actions\Admin\CreateTenantAction` compartida entre el controlador y los comandos de consola.

## 2. Controlador de Admin y Endpoint de Reintento

- [ ] 2.1 Refactorizar `Admin\TenantController@store` para utilizar `CreateTenantAction` y despachar `ProvisionTenant::dispatch($tenant->id)->afterCommit()`.
- [ ] 2.2 Agregar `TenantPolicy@retry` restringido a administradores de plataforma (`is_platform_admin`).
- [ ] 2.3 Implementar `POST /api/admin/tenants/{tenant}/retry` en `TenantController` validando estado `failed` (respondiendo 409 Conflict si no está en `failed`) y auditando la acción.
- [ ] 2.4 Registrar la ruta en `routes/api.php` y actualizar `ApiError` con `tenant_not_failed`.

## 3. Comandos de Consola

- [ ] 3.1 Implementar `php artisan tenants:create {slug} {name}` validando el slug según `StoreTenantRequest` y reutilizando `CreateTenantAction`.
- [ ] 3.2 Implementar `php artisan tenants:migrate` para migrar bases de clubes activos en lote de forma resiliente.

## 4. Documentación y Entorno

- [ ] 4.1 Documentar el uso de `QUEUE_CONNECTION=sync` para local y demo en `docs/desarrollo-local.md`.
- [ ] 4.2 Actualizar `docs/guia-bases-de-datos.md` (§7 y §9) marcando las capacidades implementadas como `EXISTE HOY`.
- [ ] 4.3 Regenerar contrato OpenAPI con `php artisan scramble:export`.

## 5. Tests y Verificación

- [ ] 5.1 Implementar suite completa en `tests/Feature/Admin/TenantProvisioningTest.php` cubriendo alta exitosa, 32 tablas creadas, falla simulada, idempotencia, retry, select-tenant y comandos.
- [ ] 5.2 Asegurar que todos los tests limpien las bases `club_test_*` creadas y no dejen bases huérfanas.
- [ ] 5.3 Ejecutar y verificar linter `vendor/bin/pint --test` y suite completa de tests de la aplicación.
