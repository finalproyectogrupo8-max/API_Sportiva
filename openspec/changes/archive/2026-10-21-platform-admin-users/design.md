# Design

## Context

La API ya protege `/api/admin/*` con Sanctum y el alias `platform.admin`; el ABM de clubes usa `TenantPolicy`, Form Requests y API Resources. `User` y `TenantMembership` pertenecen a la base central, y la tabla de membresías ya impone unicidad por usuario y club. El login no comprueba estado del usuario y `EnsureTenantContext` revalida la membresía, pero no el estado del usuario. Ver [proposal.md](./proposal.md) y las capabilities de este change para el comportamiento esperado.

## Goals / Non-Goals

**Goals**

- Aplicar las convenciones del ABM de plataforma a usuarios y membresías.
- Hacer atómicas las acciones administrativas y su registro de auditoría.
- Revocar credenciales de forma acotada al usuario o al club afectado.

**Non-Goals**

- Provisionar o migrar bases de clubes.
- Incorporar roles operativos del club distintos de `admin`.
- Cambiar la duración o las abilities de los tokens de administrador de plataforma.
- Agregar consulta de auditoría o restauración de clubes dados de baja.

## Decisions

1. **Conservar el middleware existente.** Las rutas nuevas usarán el grupo `auth:sanctum` y el alias `platform.admin`, que ya protege el ABM de clubes. No se renombra el alias en este cambio.

2. **Mantener usuarios y membresías en la base central.** Las migraciones nuevas se ejecutan en la base central. `users.is_active` tendrá default `true`; la reactivación restaura el acceso de login, pero no recupera tokens revocados.

3. **Restringir las operaciones públicas del ABM.** Usuarios expondrá `index`, `store`, `show` y `update`, sin `destroy`; la baja será una transición de `is_active`. Membresías expondrá alta anidada y actualización parcial. La resolución de una membresía siempre estará acotada al club de la URL; no se aceptarán cambios de `user_id` ni `tenant_id`.

4. **Usar inicialmente un enum cerrado de un solo rol.** La API aceptará solo `admin` hasta que el change de acceso por roles del club defina roles adicionales. Esto evita persistir valores no reconocidos por la autorización posterior.

5. **Definir la contraseña inicial desde el ABM.** La creación y el cambio exigirán al menos 12 caracteres. El hash se generará con el cast existente de `User`; los Resources y los datos de auditoría nunca incluirán el valor ni el hash.

6. **Revocar tokens en la misma operación que cambia el acceso.** Desactivar un usuario eliminará todos sus tokens. Desactivar una membresía eliminará solo los tokens del usuario cuyo `tenant_id` corresponde a ese club. Cambiar la contraseña eliminará todos los tokens del usuario. Las revocaciones se ejecutarán transaccionalmente con el cambio de estado.

7. **Guardar la auditoría en la base central y en la transacción de negocio.** Un servicio de auditoría insertará un registro por acción exitosa sobre usuarios, membresías y clubes. Guardará actor, acción, tipo e id del recurso, campos modificados, IP y fecha; el JSON de cambios tendrá una lista explícita de campos permitidos y excluirá credenciales. Si falla la escritura del log, la operación de negocio también se revierte. El modelo de auditoría será solo de inserción y no tendrá rutas API.

8. **Mantener el contrato de auditoría consistente en el ABM de clubes existente.** Las operaciones actuales de alta, cambio y baja de clubes también pasarán por el servicio transaccional de auditoría.

## Risks / Trade-offs

- **La auditoría pasa a ser requisito de disponibilidad para las mutaciones administrativas** → escribir el evento en la misma transacción evita cambios sin registro y permite detectar fallos; el servicio no debe capturar ni silenciar errores.
- **El rol inicial `admin` puede no coincidir con futuros roles de club** → mantenerlo en un enum y tratar la incorporación de valores como un cambio explícito de contrato.
- **La desactivación revoca sesiones activas** → se prioriza la revocación inmediata; reactivar la cuenta requiere iniciar sesión otra vez.
- **El log puede crecer sin límite** → definir retención y exportación en un cambio posterior, sin borrar o modificar registros desde esta API.
- **Las credenciales actuales de administradores de plataforma no cambian en esta entrega** → el TTL vigente de 15 minutos y la ausencia de una ability específica permanecen sin cambios y requieren una decisión separada.

## Migration Plan

1. Agregar `users.is_active` con default `true` y crear `audit_logs` en la base central; no se agregan migraciones a las bases de clubes.
2. Desplegar el código que escribe auditoría en una transacción junto con cada operación administrativa.
3. Verificar el contrato OpenAPI generado y ejecutar los tests de usuarios, membresías, auditoría y autenticación.
4. Para rollback, revertir primero el código que consulta/escribe las nuevas estructuras y luego las migraciones centrales. El rollback de `audit_logs` elimina su historial, por lo que debe respaldarse antes de ejecutarlo.
