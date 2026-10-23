# Proposal: Bloqueo de Activación de Clubes sin Base Provisionada (TIZ-06)

## Why

Actualmente, un administrador de plataforma puede realizar una petición `PATCH /api/admin/tenants/{tenant}` enviando `status = active` sobre un club que se encuentra en estado `provisioning` o `failed`. Esto provoca que el club figure como activo en la base central `sportiva_platform` a pesar de que su base de datos física (`club_<slug>`) nunca fue creada ni migrada, rompiendo subsiguientes operaciones como el selector de club (`select-tenant`) y la navegación operativa.

El único mecanismo legítimo para que un club en `provisioning` o `failed` pase a `active` debe ser a través del proceso de provisión (`ProvisionTenant`), garantizando que la base de datos existe con sus 32 tablas antes de habilitar el acceso.

## What Changes

- En `UpdateTenantRequest`, implementar la validación de transiciones de estado para `status`:
  - `suspended -> active`: permitido.
  - `active -> suspended`: permitido.
  - `provisioning -> active`: rechazado con HTTP 422, error en el campo `status`, con mensaje descriptivo en español.
  - `failed -> active`: rechazado con HTTP 422, error en el campo `status`, con mensaje descriptivo en español.
  - `active -> active` o `suspended -> suspended` (sin cambio real): permitido (responde 200 sin registrar cambios en auditoría).
  - Actualización de otros campos (como `name`) sin enviar `status` en un club `provisioning` o `failed`: permitido (responde 200).
  - Enviar `status = suspended` sobre un club en `provisioning` o `failed`: permitido (la guía no lo prohíbe).
- Mantener intactos los flujos de `ProvisionTenant` y el endpoint `POST /api/admin/tenants/{tenant}/retry`, que no dependen de `UpdateTenantRequest`.

## Capabilities

### New Capabilities
- `tenant-activation-guard`: Restricción estricta de transiciones a estado `active` en la API administrativa, impidiendo la activación de clubes sin provisión comprobada de base de datos.

## Impact

- Código: `app/Http/Requests/Admin/UpdateTenantRequest.php`.
- API: peticiones `PATCH /api/admin/tenants/{tenant}` con `status = active` sobre clubes en `provisioning` o `failed` ahora devuelven 422 Unprocessable Content.
- Auditoría: solicitudes rechazadas con 422 no generan registros en `audit_logs`.
- Tests: nuevos casos de validación de transiciones en `tests/Feature/Admin/TenantAdminTest.php`.

