# Tasks: Bloqueo de Activación de Clubes sin Base Provisionada (TIZ-06)

## 1. Validación en Form Request

- [x] 1.1 Implementar método `withValidator` en `App\Http\Requests\Admin\UpdateTenantRequest`.
- [x] 1.2 Interceptar transición hacia `status = active` cuando el tenant en ruta posea estado `provisioning` o `failed`.
- [x] 1.3 Emitir error en campo `status` con mensaje en español coherente con la API.

## 2. Pruebas Automatizadas

- [x] 2.1 Agregar tests de rechazo 422 para `provisioning -> active` y `failed -> active` verificando persistencia de estado original y ausencia de log de auditoría.
- [x] 2.2 Agregar tests de aceptación 200 para `suspended -> active` (con auditoría) y `active -> suspended`.
- [x] 2.3 Agregar test para `active -> active` sin log de auditoría y actualización de nombre sin status en club `provisioning`.
- [x] 2.4 Verificar no regresión en `TenantProvisioningTest` (`store` con provisión y `retry`).

## 3. Verificación de Contrato y Estilo

- [x] 3.1 Regenerar contrato OpenAPI con `php artisan scramble:export` comprobando ausencia de nuevos warnings.
- [x] 3.2 Ejecutar suite completa con `php artisan test`.
- [x] 3.3 Validar formato de código con `vendor/bin/pint --test`.

