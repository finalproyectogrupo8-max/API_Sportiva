# Proposal: conexión a la base de cada club

## Why

Ningún ABM de club se puede programar ni probar: no existe la conexión `tenant`, el middleware `tenant.token` valida el club del token pero no cambia de base, y no hay bases de club para correr el test de aislamiento entre clubes que exige `docs/guia-abm.md`. El trabajo se reparte en dos: este change entrega la conexión y el soporte de tests, para que los ABM arranquen ya. El alta de clubes (`ProvisionTenant`, `tenants:create`, `tenants:migrate`, panel de admin) queda en `platform-admin-and-provisioning`, a cargo de Tiziano, y se apoya en lo que entrega este change.

## What Changes

- Conexión `tenant` en `config/database.php`: el mismo host y usuario que `mysql`, sin base fija.
- Un servicio único que apunta la conexión `tenant` a la base de un club, valida el nombre de la base y descarta la conexión anterior (`DB::purge`). Lo usan el middleware, los jobs de club, los tests y, después, el alta de clubes.
- Operaciones de base de club en el mismo servicio: crear la base, aplicar las migraciones de `database/migrations/tenant/` y borrarla. `ProvisionTenant` y los comandos `tenants:*` las van a usar en lugar de escribir las suyas.
- `tenant.token` (`EnsureTenantContext`): después de validar el token, la membresía y el estado del club, apunta la conexión `tenant` a la base del club. Al terminar el request, la conexión vuelve a no apuntar a ninguna base.
- Carpeta `database/migrations/tenant/`, vacía salvo un `.gitkeep`. Las tablas las agregan los changes de los módulos.
- Soporte de tests multi-club: un trait que crea dos clubes de prueba (`club_test_a` y `club_test_b`) con sus bases migradas, permite actuar como un usuario de cada uno y limpia las tablas entre tests.
- Guard en los tests: la conexión `tenant` solo puede apuntar a bases `club_test_*` en `testing`.
- MySQL local y CI: el usuario de la app recibe permisos sobre `club\_%` (crear, usar y borrar bases de club). Requiere recrear el volumen local una vez.
- ADR 0002: multi-tenancy con implementación propia (sin `stancl/tenancy`).
- Documentación: la guía de bases de datos, la de ABM, la de jobs y el plan de implementación de los ABM, al día con lo que existe.

## Capabilities

### New Capabilities

- `tenant-connection`: resolución de la base del club en cada request y en los jobs, aislamiento entre clubes, operaciones sobre la base de un club y el soporte de tests multi-club.

### Modified Capabilities

- `local-dev-environment`: el usuario de la app puede crear y usar bases de club en el MySQL local.

## Impact

- Código: `config/database.php`, `EnsureTenantContext`, un servicio nuevo en `app/Support/` (o `app/Tenancy/`), `tests/TestCase.php` y un trait nuevo en `tests/Concerns/`.
- Infraestructura local: `docker/mysql/init/` (un script nuevo). Quien ya tiene el volumen creado tiene que recrearlo (`docker compose down -v`, que **borra** la base local) para que el permiso se aplique.
- CI: sin cambios en el workflow; el volumen es nuevo en cada corrida, así que el script se aplica solo.
- API: sin endpoints nuevos ni cambios en el contrato OpenAPI. Las respuestas de `GET /api/me` no cambian.
- Producción (Laravel Cloud): la conexión `tenant` usa las mismas variables `DB_*`. Los permisos del usuario de MySQL en el cluster y la separación entre el usuario de operación y el de provisión siguen siendo una decisión de `platform-admin-and-provisioning`.
- Coordinación: `platform-admin-and-provisioning` (Tiziano) deja de incluir la conexión `tenant` y el middleware, y usa el servicio de este change.

## Fuera de alcance

- `ProvisionTenant`, `tenants:create`, `tenants:migrate`, la conexión `provisioning`, el worker de la cola y el panel de admin (`platform-admin-and-provisioning`).
- Las tablas del club, incluidas las de identidad y roles (`club_users`, `roles`, `permissions`): van en el change `club-access-control`, el siguiente del plan.
- `hasClubPermission()` y el contenido de `permissions[]` en `select-tenant` y `/me`.
- Credenciales de MySQL propias por club.
