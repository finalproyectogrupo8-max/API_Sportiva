# Tasks

## 1. Base central y modelos

- [x] 1.1 Agregar `users.is_active` con default `true` y la migración de `audit_logs`; verificado con migraciones y tests de administración.
- [x] 1.2 Resolver el modelo de roles: el enum central quedó supersedido por roles y permisos en la base de cada club (`club-access-control`); `tenant_memberships` conserva únicamente el estado de acceso.
- [x] 1.3 Crear el modelo `AuditLog` de solo inserción y verificar que persista actor, acción, recurso, cambios, IP y fecha sin permitir mutaciones.

## 2. ABM de usuarios

- [x] 2.1 Implementar rutas y `Admin/UserController` para listar, crear, ver y editar usuarios con Policy, Form Requests y `UserResource`; verificar status codes, autorización y respuestas sin credenciales en `tests/Feature/Admin/UserAdminTest.php`.
- [x] 2.2 Validar email único, contraseña mínima de 12 caracteres, filtros `search`/`is_active`, paginación 20..100 y rechazo de `is_platform_admin`; cubrir alta, filtros, validación, paginación y protección de campos en `UserAdminTest.php`.
- [x] 2.3 Implementar desactivación/reactivación con rechazo de auto-desactivación; verificar ambos casos y sus respuestas en `UserAdminTest.php`.

## 3. Membresías de club

- [x] 3.1 Implementar alta y actualización de membresías bajo `/api/admin/tenants/{tenant}/memberships`, con binding acotado al club, Policy, Requests y Resource; verificar creación, duplicados, roles, club eliminado y aislamiento de URL en `tests/Feature/Admin/TenantMembershipAdminTest.php`.
- [x] 3.2 Permitir cambiar roles, deportes y `is_active` sin aceptar cambios de `user_id` o `tenant_id`; verificar respuestas y persistencia con tests de actualización parcial.

## 4. Autenticación y revocación de tokens

- [x] 4.1 Rechazar login y requests con token de usuarios inactivos; verificar 401 y mensaje genérico en `tests/Feature/Auth/AuthFlowTest.php` y los tests de contexto de tenant.
- [x] 4.2 Revocar todos los tokens al desactivar un usuario o cambiar su contraseña, y solo los tokens del club al desactivar una membresía; verificar revocación selectiva y tokens de otros clubes en tests de auth y membresías.

## 5. Auditoría administrativa

- [x] 5.1 Implementar el servicio transaccional de auditoría con campos permitidos y sin credenciales ni tokens; verificar eventos de alta, cambio y baja en `tests/Feature/Admin/AuditLogTest.php`.
- [x] 5.2 Integrar la auditoría en los ABM de usuarios, membresías y clubes. El PR #20 agregó consultas de solo lectura; no hay rutas para modificar o borrar registros. Verificar atomicidad y lectura en `AuditLogTest.php` y `ClubActivityTest.php`.

## 6. Contrato y validación final

- [x] 6.1 Actualizar `docs/guia-abm.md` con las convenciones confirmadas del ABM central: roles de club, desactivación, auditoría y revocación de tokens.
- [x] 6.2 Regenerar `docs/api/openapi.json` y verificar las rutas de usuarios, membresías y auditoría; `ApiDocsContractTest` compara las rutas API registradas con el contrato.
- [x] 6.3 Ejecutar `vendor/bin/pint --test` y los tests dirigidos de usuarios, membresías, auditoría y auth; CI del PR #20 quedó verde y Pint local pasa.
- [x] 6.4 Ejecutar `php artisan test` y `openspec validate platform-admin-users --strict`; suite del PR #20 pasó, validación estricta local pasa.
