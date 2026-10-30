## ADDED Requirements

### Requirement: Roles custom del club

La API SHALL permitir crear, editar, borrar y duplicar roles del club con nombre, descripción y una lista de permisos del catálogo con alcance `all` u `own`. Lo SHALL permitir desde el club (`/api/roles`, permisos `roles.*`) y desde el panel de plataforma (`/api/admin/tenants/{tenant}/roles`). `GET /api/permissions` SHALL devolver la grilla agrupada por módulo y recurso.

#### Scenario: Crear un rol custom

- **WHEN** el Administrador del club crea "Profe con indumentaria" con `players.view` en `own` y `apparel_orders.deliver` en `all`
- **THEN** responde 201 con slug `profe_con_indumentaria`
- **AND** un usuario con ese rol recibe exactamente esos permisos en `/api/me`

#### Scenario: Rol estándar

- **WHEN** se intenta editar o borrar un rol estándar
- **THEN** responde 403 con `code: role_is_system`

#### Scenario: Duplicar

- **WHEN** se duplica el rol `coach`
- **THEN** se crea el rol custom "Copia de Profe" con los mismos permisos y alcances

#### Scenario: Rol asignado

- **WHEN** se borra un rol custom que tiene usuarios
- **THEN** responde 409 con `code: role_in_use`

#### Scenario: Permiso inválido

- **WHEN** se crea un rol con un permiso fuera del catálogo o con `own` en un permiso que no lo admite
- **THEN** responde 422 en ese elemento de `permissions`

#### Scenario: Sin escalada

- **WHEN** un usuario con `roles.create` y `players.view` en `own` crea un rol con `players.view` en `all` o con un permiso que no tiene
- **THEN** responde 422 y no se crea el rol

#### Scenario: Aislamiento entre clubes

- **WHEN** se crea un rol en el club A
- **THEN** el club B no lo ve y `GET /api/roles/{slug}` en B responde 404

### Requirement: Último administrador del club

Un club activo SHALL conservar al menos un Administrador del club activo. Quitarle el rol `admin` o desactivar la membresía del último SHALL responder 409 con `code: last_admin`.

#### Scenario: Único administrador

- **WHEN** el admin de plataforma le cambia los roles a `coordinator` o desactiva al único Administrador del club activo
- **THEN** responde 409 con `code: last_admin` y no cambia nada

#### Scenario: Hay otro administrador

- **WHEN** existe otro Administrador del club con membresía activa
- **THEN** el cambio se aplica
