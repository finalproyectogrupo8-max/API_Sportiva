# 1. Identidad local y autorización

[← Índice](README.md)

Representa a los usuarios de la plataforma **dentro de este club** y qué pueden hacer. La autenticación (email y contraseña) y el permiso de entrar al club (`tenant_memberships`) viven en la base central; acá vive el perfil local y los roles del club.

> Qué permisos existen, los roles estándar, los roles custom y el alcance "sus categorías" están en [roles-y-permisos.md](../roles-y-permisos.md). Las columnas marcadas *(TIZ-10)* entran con una migración nueva en TIZ-11.

```mermaid
erDiagram
    club_users ||--o{ club_user_roles : tiene
    roles ||--o{ club_user_roles : asigna
    roles ||--o{ role_permissions : incluye
    permissions ||--o{ role_permissions : otorga

    club_users {
        bigint id PK
        bigint global_user_id UK "ref. lógica a sportiva_platform.users.id"
        varchar display_name
        varchar status "active | disabled"
    }
    roles {
        bigint id PK
        varchar slug UK
        varchar name UK
        boolean is_system
        varchar description
    }
    permissions {
        bigint id PK
        varchar name UK "ej. apparel_orders.approve"
        varchar module
        varchar description
    }
    club_user_roles {
        bigint club_user_id PK,FK
        bigint role_id PK,FK
        timestamp created_at
    }
    role_permissions {
        bigint role_id PK,FK
        bigint permission_id PK,FK
        varchar scope "all | own"
    }
```

## `club_users`

Perfil local de un usuario global en este club. Se crea cuando el usuario recibe una membresía en el club.

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | Perfil local |
| `global_user_id` | BIGINT UNSIGNED | no | UK | `sportiva_platform.users.id`. Referencia lógica, **no** FK entre bases |
| `display_name` | VARCHAR(120) | no | | Nombre visible dentro del club |
| `status` | VARCHAR(20) | no | | `active` / `disabled` |
| `created_at`, `updated_at` | TIMESTAMP | sí | | |

- `status = disabled` bloquea las operaciones en el club aunque la membresía central siga activa. El middleware valida las dos cosas.
- No lleva `deleted_at`: un usuario que se va del club queda `disabled`, porque pedidos, movimientos y auditoría lo referencian.

## `roles`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | |
| `slug` | VARCHAR(80) | no | UK | Clave estable. Estándar: `admin`, `coordinator`, `coach`, `physical_trainer`, `inventory_manager`, `maintenance_manager`. Custom: generada del nombre *(TIZ-10)* |
| `name` | VARCHAR(80) | no | UK | Nombre visible |
| `is_system` | BOOLEAN | no | default `false` | Rol estándar: no se edita ni se borra *(TIZ-10)* |
| `description` | VARCHAR(255) | sí | | |
| `created_at`, `updated_at` | TIMESTAMP | sí | | |

## `permissions`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | |
| `name` | VARCHAR(120) | no | UK | Clave estable: `players.view`, `apparel_orders.approve`, `inventory.adjust`. El borrador la llama `key` (ver [diferencia 5](README.md#diferencias-con-el-borrador-y-con-el-repo)) |
| `module` | VARCHAR(40) | no | | Módulo para agrupar la grilla de checks *(TIZ-10)* |
| `description` | VARCHAR(255) | sí | | |

- Las claves de permiso son las que usan las policies y `Gate::authorize`, y las que devuelve `permissions[]` en `select-tenant` y `/api/me`.

## `club_user_roles`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `club_user_id` | BIGINT UNSIGNED | no | PK, FK → `club_users.id` | |
| `role_id` | BIGINT UNSIGNED | no | PK, FK → `roles.id` | |
| `created_at` | TIMESTAMP | sí | | Fecha de asignación |

## `role_permissions`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `role_id` | BIGINT UNSIGNED | no | PK, FK → `roles.id` | |
| `permission_id` | BIGINT UNSIGNED | no | PK, FK → `permissions.id` | |
| `scope` | VARCHAR(10) | no | default `all` | `all` (todo el club) / `own` (sus categorías) *(TIZ-10)* |

## Reglas

- Las dos tablas pivote usan `ON DELETE CASCADE` hacia `roles` y `permissions`: borrar un rol borra sus asignaciones, no datos de negocio.
- Los permisos y los roles estándar salen de `config/club-permissions.php` y los sincroniza `php artisan club:sync-permissions` en todas las bases (alta del club y cada deploy). Ver [roles-y-permisos §9](../roles-y-permisos.md#9-sincronización-del-catálogo).
- Nunca se autoriza con un rol o permiso enviado por el cliente: siempre se leen de esta base, para el `club_user` del token.
