# Plan de implementación de los ABM del club

[← Índice del modelo](README.md)

Paso a paso para quien implemente los ABM de la base del club, sea un dev o un agente. Está escrito para seguirse en orden: cada paso dice qué hacer, cómo verificarlo y qué no tocar.

> **Si sos un agente:** leé este documento entero antes de escribir código. Las secciones [1](#1-quién-hace-qué) y [2](#2-qué-tiene-que-existir-antes) definen tu frontera: si un paso necesita algo de la sección 2 que todavía no existe, **frená y avisá**, no lo construyas vos.

## Por dónde empezar: estado actual

No supongas el estado: verificalo, porque este documento puede quedar atrás del código.

1. **¿Qué está aprobado?** Un change está **aprobado** cuando el PR de su propuesta está mergeado en `main` (la misma convención que `platform-auth`, PR #10). Si su carpeta en `openspec/changes/` existe solo en una rama sin mergear, todavía está en revisión: no lo apliques.
2. **¿Qué está implementado?** `openspec list --json` muestra los changes en curso y las tareas completadas. Los que pasaron por `openspec/changes/archive/` ya están terminados.
3. **¿Qué existe en el código?** Los comandos de verificación de la [sección 2](#2-qué-tiene-que-existir-antes) (C1–C6).
4. **Qué hacer, según lo que encontraste:**

| Situación | Qué hacer |
|---|---|
| `tenant-connection` no está mergeado | Esperar la aprobación. Mientras tanto, se puede escribir la propuesta de `club-access-control` (paso 1) |
| `tenant-connection` aprobado, con tareas pendientes | `/opsx:apply tenant-connection` |
| C1–C4 existen y `club-access-control` no está propuesto | `/opsx:propose club-access-control` |
| C1–C6 existen | Seguir con la siguiente ola de la [sección 4](#4-orden-de-implementación-olas) que no tenga change |
| Algo de este documento contradice al código | Gana el código. Avisá y corregí el documento en el mismo PR |

Documentos que hay que leer antes, en este orden:

1. [AGENTS.md](../../AGENTS.md): stack, comandos y convenciones.
2. [README del modelo](README.md): tablas, decisiones y diferencias con el borrador.
3. [guia-abm.md](../guia-abm.md): cómo se escribe un ABM (rutas, Requests, Resources, Policies, los 10 tests y el checklist de comandos).
4. [guia-bases-de-datos.md](../guia-bases-de-datos.md): migraciones de club y cómo se aplican.
5. El documento del módulo que vayas a implementar (`01-…` a `08-…`).

## 1. Quién hace qué

| Trabajo | Responsable | Change de OpenSpec |
|---|---|---|
| Login, selección de club, tokens atados al club | Hecho (mergeado) | `platform-auth` (le quedan tareas del ABM de clubes) |
| Conexión `tenant`, cambio de base por request y en jobs, servicio `TenantDatabase` (crear, migrar y conectar bases de club), permisos de MySQL local para `club_%`, soporte de tests multi-club | **Dev de ABM** | [`tenant-connection`](../../openspec/changes/tenant-connection/proposal.md) (propuesto, pendiente de aprobación) |
| Módulo 1 (`club_users`, roles y permisos) y la resolución de permisos (`hasClubPermission`, `permissions[]` en `/me`) | **Dev de ABM** (ver [D10](#3-decisiones-que-hay-que-cerrar-antes-de-migrar)) | `club-access-control` (a proponer) |
| ABM de los módulos 2 a 8 | **Dev de ABM** | Uno por ola (sección 4) |
| Alta de clubes y usuarios, panel de admin, `ProvisionTenant`, `tenants:create`, `tenants:migrate`, conexión `provisioning`, worker de la cola, permisos de MySQL en Laravel Cloud | **Tiziano** | `platform-admin-and-provisioning`. Usa `TenantDatabase` de `tenant-connection` y el seeder de roles de `club-access-control` |

### Archivos que el dev de ABM **no** toca

Son de Tiziano. Tocarlos genera conflictos y rompe la frontera entre los changes:

- `ProvisionTenant`, los comandos `tenants:*`, los endpoints `/api/admin/*` y su controller.
- El servicio `queue` (worker) en `compose.yaml`.
- `database/migrations/` (la base central) y los modelos `User`, `Tenant` y `TenantMembership`.

Y Tiziano no toca lo que entrega `tenant-connection`: `config/database.php` (conexión `tenant`), `EnsureTenantContext`, `App\Tenancy\TenantDatabase`, `docker/mysql/init/02-club-dbs.sh` y `Tests\Concerns\InteractsWithTenants`. Si alguno necesita cambiar algo del otro, se acuerda y se anota en el change.

## 2. Qué tiene que existir antes

Los ABM de club **no se pueden probar** sin esto: el test 10 de la guía ABM (aislamiento entre clubes) necesita dos bases de club reales. C1 a C4 los entrega `tenant-connection`; C5 y C6, `club-access-control`. Ninguno depende del alta de clubes de Tiziano.

| # | Pieza | Qué necesita el dev de ABM | Cómo verificar que existe |
|---|---|---|---|
| C1 | Conexión `tenant` | Que los modelos de club usen `protected $connection = 'tenant';` y apunten a la base del club del token | `grep -n "'tenant' =>" config/database.php` |
| C2 | Cambio de conexión por request | Que `tenant.token` apunte la conexión a la base del club y la desconecte al terminar | `grep -n "TenantDatabase" app/Http/Middleware/EnsureTenantContext.php` |
| C3 | Migraciones de club | La carpeta `database/migrations/tenant/` y `TenantDatabase::migrate()`, que la usan los tests | `ls database/migrations/tenant` |
| C4 | Soporte de tests multi-club | `Tests\Concerns\InteractsWithTenants`: `tenantA()`, `tenantB()`, `clubToken()`, `inTenant()` | `ls tests/Concerns` |
| C5 | Permisos | `hasClubPermission()` 🟡 y el seeder de roles y permisos del club 🟡 al que cada ABM agrega sus claves | `grep -rn "hasClubPermission" app/` |
| C6 | `club_users` | La tabla y la forma de obtener el `club_user` del request actual 🟡 (lo necesitan `requested_by`, `performed_by`, `recorded_by`…) | `ls database/migrations/tenant \| grep club_users` |

**C1 a C4 bloquean todo.** C5 y C6 bloquean desde la ola 1 los tests de permisos (caso 8) y, desde la ola 2, las tablas que referencian `club_users`. Los nombres con 🟡 se definen en `club-access-control`.

**Sin el alta de clubes de Tiziano** se puede desarrollar y testear todo (los tests crean sus propias bases `club_test_*`). Lo único que falta es probar a mano desde Swagger contra un club local, que necesita `tenants:create`.

## 3. Decisiones que hay que cerrar antes de migrar

Se cierran **en el change de OpenSpec de la ola** que las necesita (en el `design.md`), y después se actualiza el documento del módulo. Cada una tiene una recomendación; si el equipo elige otra cosa, se corrige el doc.

| # | Decisión | Recomendación | Ola |
|---|---|---|---|
| D1–D9 | Las 9 [diferencias con el borrador](README.md#diferencias-con-el-borrador-y-con-el-repo) | La opción "en este doc" de cada una | 1 (se revisan todas juntas) |
| D10 | Quién hace el módulo 1 (identidad y roles del club) | **Dev de ABM**, en `club-access-control`: todos los ABM lo necesitan para los permisos. El alta de clubes de Tiziano llama a su seeder de roles. Además, hay que resolver la [diferencia 4](README.md#diferencias-con-el-borrador-y-con-el-repo): `tenant_memberships` ya tiene una columna `role` en la base central | Antes de la ola 1 |
| D11 | Qué responde `DELETE` sobre un catálogo que está en uso (por ejemplo, un deporte con categorías) | 409 con un `code` propio. La FK `RESTRICT` lo protege igual, pero sin manejarlo responde 500 | 1 |
| D12 | Catálogos: baja lógica (`deleted_at`) o desactivar (`is_active`) | `is_active` para `sports`; `deleted_at` para el resto, según el doc de cada módulo | 1 |
| D13 | Cómo se genera `orders.order_number` | Secuencia por club con prefijo de año (`2027-0001`), generada en una transacción | 4 |
| D14 | Signo de la cantidad en `inventory_movements` de tipo `adjustment` | Ver [04-inventario](04-inventario.md#inventory_movements) | 4 |
| D15 | ¿Hace falta `players.document_number`? Retención de evaluaciones físicas y auditoría | Definirlo con el equipo antes de la ola 2 (datos personales de menores) | 2 y 4 |

## 4. Orden de implementación (olas)

El orden sigue las FK: una tabla se implementa cuando ya existen las tablas a las que apunta. Cada ola es **un change de OpenSpec y un PR**.

| Ola | Tablas | Depende de | Por qué en este orden |
|---|---|---|---|
| −1 | Infraestructura: conexión `tenant`, `TenantDatabase`, permisos de MySQL, tests multi-club (`tenant-connection`) | Nada | Entrega C1–C4: sin esto no se puede probar ningún ABM |
| 0 | Módulo 1: `club_users`, `roles`, `permissions`, pivotes (`club-access-control`) | Ola −1 | Lo referencian casi todas las tablas, y todos los ABM lo necesitan para los permisos. Entrega C5 y C6 |
| 1 | `sports`, `seasons`, `storage_locations`, `materials`, `apparel_items`, `facilities` | Ola 0 | Catálogos sin FK: sirven para estrenar la guía ABM con código real |
| 2 | `categories`, `players`, `coaches`, `facility_components` | Ola 1 | Primeras FK simples |
| 3 | `player_category_memberships`, `category_coaches`, `matches`, `trainings` | Ola 2 | Asignaciones y calendario |
| 4 | Pedidos (`orders` + líneas + `order_status_history`), inventario, `player_attendance`, evaluaciones físicas, reparaciones y mantenimiento | Ola 3 | Son **procesos** con estados y transacciones, no ABM puros. Se pueden repartir en varios changes |
| 5 | `notifications`, `audit_logs` | Según lo que se quiera auditar | Transversales |

### Qué tipo de endpoint lleva cada tabla

No todo es un `apiResource`. Los endpoints y permisos son una **propuesta**: se confirman en el change de cada ola, siguiendo [guia-abm §4](../guia-abm.md#4-rutas) (rutas) y [§9](../guia-abm.md#9-autorización-policies-y-permisos) (permisos `<recurso>.<acción>`).

| Tabla | Tipo | Endpoints propuestos | Permisos | Baja |
|---|---|---|---|---|
| `sports` | ABM | `apiResource('sports')` | `sports.view/create/update/delete` | `is_active` (D12) |
| `seasons` | ABM | `apiResource('seasons')` | `seasons.*` | Borrado real si no tiene categorías (D11) |
| `storage_locations` | ABM | `apiResource('storage-locations')` | `storage_locations.*` | Soft delete |
| `materials` | ABM | `apiResource('materials')` | `materials.*` | Soft delete |
| `apparel_items` | ABM | `apiResource('apparel-items')` | `apparel_items.*` | Soft delete |
| `facilities` | ABM | `apiResource('facilities')` | `facilities.*` | Soft delete |
| `categories` | ABM con filtros `sport_id` y `season_id` | `apiResource('categories')` | `categories.*` | `is_active` |
| `players` | ABM | `apiResource('players')` | `players.*` | Soft delete |
| `coaches` | ABM | `apiResource('coaches')` | `coaches.*` | Soft delete |
| `facility_components` | ABM anidado | `facilities.components` con `->shallow()` | `facilities.update` | Borrado real |
| `player_category_memberships` | ABM anidado + acción | `categories.memberships` shallow; `POST /memberships/{m}/end` | `categories.manage_players` | No se borra: se cierra con `end_date` |
| `category_coaches` | ABM anidado | `categories.coaches` shallow | `categories.manage_coaches` | Se cierra con `end_date` |
| `matches` | ABM | `apiResource('matches')`; el resultado se carga con `PATCH` | `matches.*` | Soft delete |
| `trainings` | ABM | `apiResource('trainings')` | `trainings.*` | Soft delete |
| `player_attendance` | Proceso | `PUT /trainings/{t}/attendance` y `PUT /matches/{m}/attendance` (carga la lista completa, idempotente) | `attendance.view/record` | No se borra |
| `orders` + líneas | Proceso | `apiResource('orders')` (solo editable en `draft`) + `POST /orders/{o}/submit`, `/approve`, `/reject`, `/deliveries` | `orders.view/create/update/delete/approve/deliver` | Soft delete; solo en `draft` |
| `order_status_history` | Interna | `GET /orders/{o}/history` (solo lectura) | `orders.view` | — |
| `inventory_balances` | Solo lectura | `GET /inventory/balances` | `inventory.view` | — |
| `inventory_movements` | Libro (sin update ni delete) | `GET` y `POST /inventory/movements`, `POST /inventory/transfers` | `inventory.view/adjust/transfer` | Nunca |
| `physical_evaluations` + mediciones | ABM con hijos en el mismo payload | `apiResource('physical-evaluations')` | `physical_evaluations.*` | Soft delete |
| `repair_requests` | ABM + acciones | `apiResource('repair-requests')` + `/assign`, `/resolve` | `repairs.*`, `repairs.assign` | Soft delete |
| `maintenance_records` | ABM | `apiResource('maintenance-records')` | `maintenance.*` | Borrado real |
| `notifications` | Del usuario actual | `GET /notifications`, `POST /notifications/{n}/read` | Sin permiso: cada usuario ve las suyas | — |
| `audit_logs` | Interna | Sin endpoints en el alcance inicial | — | Nunca |

Las URLs usan `kebab-case` (`storage-locations`) y las claves JSON y los permisos, `snake_case` (`storage_locations.view`). Confirmalo en el change de la ola 1, que es el primero que lo usa.

## 5. Paso a paso

### Paso 0: preparar y chequear dependencias

```bash
git checkout main && git pull
git checkout -b docs/club-abm-ola-1        # para la propuesta
docker compose up -d
```

Verificá C1 a C6 con los comandos de la [sección 2](#2-qué-tiene-que-existir-antes) y anotá cuáles faltan.

### Paso 1: proponer el change de la ola (se puede hacer ya)

No necesita la conexión `tenant`: es diseño.

1. Orden: `tenant-connection` (ya propuesto: revisarlo, aprobarlo y aplicarlo con `/opsx:apply tenant-connection`), después `/opsx:propose club-access-control` (ola 0) y después `/opsx:propose club-catalogs` (ola 1). La propuesta de una ola se puede escribir mientras se implementa la anterior. Para las siguientes: `club-squads` (2), `club-calendar` (3), y uno por proceso en la ola 4 (`club-orders`, `club-inventory`, `club-attendance`, `club-physical-evaluations`, `club-maintenance`).
2. En el `design.md`: las decisiones de la [sección 3](#3-decisiones-que-hay-que-cerrar-antes-de-migrar) que le tocan a la ola, los endpoints y permisos de la [sección 4](#qué-tipo-de-endpoint-lleva-cada-tabla), y las dependencias C1–C6 que todavía faltan.
3. En los `specs/`: un requirement por recurso, con escenarios para los 10 casos de la guía ABM.
4. `openspec validate <change> --strict`.
5. **Revisión del equipo y aprobación.** Sin aprobación no se implementa.
6. Si una decisión cambia el modelo, actualizá el documento del módulo en el mismo PR de la propuesta.

### Paso 2: implementar (solo con el change aprobado **y** C1–C4 en `main`)

Si C1–C4 no están en `main`, frená acá. No crees una conexión `tenant` provisoria, no apuntes los modelos de club a `mysql` y no corras las migraciones de club contra `sportiva_platform`.

1. `git checkout main && git pull && git checkout -b feat/club-catalogs`.
2. `/opsx:apply club-catalogs`.
3. Por cada recurso, el [checklist de guia-abm §12](../guia-abm.md#12-paso-a-paso-con-comandos-checklist), con estas precisiones:
   - La migración va en `database/migrations/tenant/` y sigue el documento del módulo: tipos, nulls, índices, `UNIQUE`, `CHECK` y reglas de FK.
   - El modelo va en `app/Models/Club/`, con `$connection = 'tenant'`.
   - El grupo de rutas de club usa los middlewares reales (hoy `['auth:sanctum', 'tenant.token']`), no los provisorios de la guía.
   - Los permisos se agregan al seeder de C5.
   - Los tests usan el soporte de C4 e incluyen el caso 10 (aislamiento entre clubes).
4. Marcá cada tarea en `tasks.md` solo cuando tengas la evidencia (los tests pasando).

### Paso 3: verificar y abrir el PR

```bash
docker compose exec backend php artisan tenants:migrate
docker compose exec backend vendor/bin/pint --test
docker compose exec backend php artisan test
docker compose exec backend php artisan scramble:export   # commitear docs/api/openapi.json
openspec validate club-catalogs --strict
```

En el PR: el link al change, qué decisiones se cerraron y la salida real de los tests. Después del merge, `/opsx:archive` con la aprobación del equipo.

## 6. Reglas que no se negocian

- No se agrega `tenant_id` ni `club_id` a las tablas del club, y el club nunca se toma del body ni de la URL: sale del token.
- No se usa `USE <base>`: el cambio de base lo hace el middleware (C2).
- Nada de FK hacia la base central: `global_user_id` es una referencia lógica.
- Las migraciones de club son compatibles hacia atrás (expand → migrate → contract), porque un deploy puede dejar clubes con versiones distintas.
- No se implementa sin el change aprobado. Si el alcance cambia, primero se actualizan los artefactos.
- Nada de datos personales reales en factories, seeders ni tests.

## 7. Plantilla para delegar una ola a un agente

```text
Objetivo: implementar la ola <N> (<change>) de docs/modelo-datos-club/plan-implementacion.md.
Leé primero: AGENTS.md, docs/modelo-datos-club/plan-implementacion.md, docs/guia-abm.md
y los documentos de los módulos <lista>.
Change de OpenSpec: openspec/changes/<change> (aprobado el <fecha>, por <quién>).
Alcance: las tablas <lista>, con los endpoints y permisos del design.md del change.
Archivos permitidos: database/migrations/tenant/, app/Models/Club/, app/Http/{Controllers,Requests,Resources}/Club/,
app/Policies/Club/, database/factories/Club/, el seeder de permisos del club, routes/api.php (grupo de club),
tests/Feature/Club/, docs/api/openapi.json, docs/modelo-datos-club/ y el tasks.md del change.
Prohibido: los archivos de la sección 1 ("no toca"), tocar la base central, push, merge, deploy,
y crear una conexión tenant provisoria si C1–C4 no existen (en ese caso, frená y reportá).
Verificación: pint --test, php artisan test (los 10 casos por recurso), scramble:export
y openspec validate <change> --strict. Reportá la salida real.
Entregable: la rama con los commits, tasks.md actualizado y un resumen de las decisiones tomadas.
```
