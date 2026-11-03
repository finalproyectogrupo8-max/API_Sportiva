## Why

`club-access-control` dejó los roles estándar y la asignación de roles desde el panel, pero un club no puede armar roles a su medida, por ejemplo un profe que además entrega la indumentaria. Tampoco hay nada que impida dejar un club sin administrador. Ver [docs/roles-y-permisos.md](../../../docs/roles-y-permisos.md) §5 y §6 (TIZ-12).

## What Changes

- Endpoints del club, con token de club y permisos `roles.*`:
  - `GET /api/permissions`: grilla de permisos.
  - `GET`, `POST`, `PATCH` y `DELETE` de `/api/roles`.
  - `POST /api/roles/{role}/duplicate`.
- El mismo contrato para el admin de plataforma, bajo `/api/admin/tenants/{tenant}/...`.
- Reglas:
  - Los roles estándar no se editan ni se borran (403 `role_is_system`).
  - No se borra un rol asignado (409 `role_in_use`).
  - Un usuario del club no puede dar permisos ni alcances que no tiene.
- **Último administrador:** el panel no deja sacar el rol `admin` ni desactivar al último Administrador del club activo (409 `last_admin`).
- `ApiErrorException` para errores de negocio con `code` desde servicios.

## Capabilities

### New Capabilities
- `club-custom-roles`: roles custom del club y la regla del último administrador.

### Modified Capabilities
- Ninguna con spec vigente.

## Impact

- Código en `app/Club/Roles`, `app/Http/Controllers/{Club,Admin}`, `app/Policies/Club/RolePolicy`, `routes/api.php` y tests.
- Contrato OpenAPI regenerado.
- Front: sección de roles en el detalle del club del panel.

## Fuera de alcance

- Gestión del equipo desde el club (`/api/club-users`) e invitaciones por mail. Dependen de la infraestructura de mails (TIZ-21 y TIZ-23).
- Auditoría de altas, ediciones y bajas de roles: va a `audit_logs` del club en la ola 5.
