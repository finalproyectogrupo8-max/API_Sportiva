# Proposal

## Why

El panel de administración de plataforma solo puede gestionar clubes. No permite crear usuarios ni asociarlos a un club, por lo que hoy deben cargarse manualmente en la base para que puedan iniciar sesión y elegir un club. Además, `docs/guia-abm.md` exige auditar cada acción del ABM de administración, pero actualmente no hay registro de esas acciones.

## What Changes

- Agregar un ABM de usuarios bajo `/api/admin/users` para crear, listar, ver y editar usuarios; la baja será una desactivación (`is_active`), no un borrado.
- Agregar gestión de membresías bajo `/api/admin/tenants/{tenant}/memberships` para asociar usuarios a clubes con un rol, cambiar el rol y activar o desactivar la membresía.
- Registrar en la base central las acciones administrativas sobre usuarios, membresías y clubes.
- Revocar tokens al desactivar un usuario o una membresía, y al cambiar la contraseña del usuario.
- Mantener `is_platform_admin` fuera de la API; su asignación seguirá disponible solo mediante `platform:create-admin`.
- Resolver pendientes heredados de `platform-auth`: el `restore` opcional de clubes dados de baja y el nombre del middleware.

## Capabilities

### New Capabilities

- `platform-users`: ABM y desactivación de usuarios de la plataforma.
- `tenant-memberships`: asociación de usuarios a clubes con un rol y estado activo.
- `admin-audit-log`: registro inmutable de acciones del panel de administración.

### Modified Capabilities

- Ninguna.

## Impact

- Código: nuevos controllers, Form Requests, Resources y Policies bajo `Admin/`; modelo y servicio de auditoría; `User` con estado activo; y validación de usuario activo durante el login y el acceso al contexto del club.
- Base central: columna `is_active` en `users` y tabla `audit_logs`.
- API: nuevas rutas de usuarios y membresías; revocación de tokens ante los cambios indicados.
- Contrato: regeneración de `docs/api/openapi.json`.
- **BREAKING**: ninguno para consumidores existentes.

## Fuera de alcance

Provisionamiento de clubes, comandos de migración de sus bases, usuarios y roles dentro de cada club (`club_users`), módulos del club y el panel de frontend.
