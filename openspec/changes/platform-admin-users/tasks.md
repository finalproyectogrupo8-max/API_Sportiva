# Tasks

## 1. Base central y modelos

- [ ] 1.1 Agregar `users.is_active` con default `true` y la migración de `audit_logs`; verificar migración y rollback en la base de testing.
- [ ] 1.2 Crear el enum cerrado de roles de membresía con el valor inicial `admin` y cubrir su uso con tests de validación.
- [x] 1.3 Crear el modelo `AuditLog` de solo inserción y verificar que persista actor, acción, recurso, cambios, IP y fecha sin permitir mutaciones.

## 2. ABM de usuarios

- [x] 2.1 Implementar rutas y `Admin/UserController` para listar, crear, ver y editar usuarios con Policy, Form Requests y `UserResource`; verificar status codes, autorización y respuestas sin credenciales en `tests/Feature/Admin/UserAdminTest.php`.
- [x] 2.2 Validar email único, contraseña mínima de 12 caracteres, filtros `search`/`is_active`, paginación 20..100 y rechazo de `is_platform_admin`; cubrir alta, filtros, validación, paginación y protección de campos en `UserAdminTest.php`.
- [x] 2.3 Implementar desactivación/reactivación con rechazo de auto-desactivación; verificar ambos casos y sus respuestas en `UserAdminTest.php`.

## 3. Membresías de club

- [ ] 3.1 Implementar alta y actualización de membresías bajo `/api/admin/tenants/{tenant}/memberships`, con binding acotado al club, Policy, Requests y Resource; verificar creación, duplicados, rol `admin`, club eliminado y aislamiento de URL en `tests/Feature/Admin/TenantMembershipAdminTest.php`.
- [ ] 3.2 Permitir cambiar rol y `is_active` sin aceptar cambios de `user_id` o `tenant_id`; verificar respuestas y persistencia con tests de actualización parcial en `TenantMembershipAdminTest.php`.

## 4. Autenticación y revocación de tokens

- [x] 4.1 Rechazar login y requests con token de usuarios inactivos; verificar 401 y mensaje genérico en `tests/Feature/Auth/AuthFlowTest.php` y los tests de contexto de tenant.
- [ ] 4.2 Revocar todos los tokens al desactivar un usuario o cambiar su contraseña, y solo los tokens del club al desactivar una membresía; verificar revocación selectiva y tokens de otros clubes en tests de auth y membresías.

## 5. Auditoría administrativa

- [ ] 5.1 Implementar el servicio transaccional de auditoría con campos permitidos y sin credenciales ni tokens; verificar eventos de alta, cambio y baja para usuarios, membresías y clubes en `tests/Feature/Admin/AdminAuditLogTest.php`.
- [ ] 5.2 Integrar la auditoría en los ABM de usuarios, membresías y clubes, sin rutas para consultar, modificar o borrar registros; verificar atomicidad ante fallos y ausencia de endpoints de auditoría en `AdminAuditLogTest.php`.

## 6. Contrato y validación final

- [ ] 6.1 Actualizar `docs/guia-abm.md` para reflejar las convenciones confirmadas del ABM central; verificar que describa roles, desactivación, auditoría y revocación de tokens.
- [ ] 6.2 Regenerar `docs/api/openapi.json` con `php artisan scramble:export` y verificar que incluya rutas, validaciones y respuestas de usuarios y membresías.
- [ ] 6.3 Ejecutar `vendor/bin/pint --test` y los tests dirigidos de usuarios, membresías, auditoría y auth; corregir cualquier regresión.
- [ ] 6.4 Ejecutar `php artisan test` y `openspec validate platform-admin-users --strict`; verificar suite completa y validación estricta exitosas.
