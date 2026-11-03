# Plan de implementación de los ABM del club

[← Índice del modelo](README.md)

Paso a paso para quien implemente los ABM de la base del club, sea un dev o un agente. Está escrito para seguirse en orden: cada paso dice qué hacer, cómo verificarlo y qué no tocar.

> **Si sos un agente:** leé este documento entero antes de escribir código. Las secciones [1](#1-quién-hace-qué) y [2](#2-qué-tiene-que-existir-antes) definen tu frontera: si un paso necesita algo de la sección 2 que todavía no existe, **frená y avisá**, no lo construyas vos.

## Por dónde empezar: estado actual

No supongas el estado: verificalo, porque este documento puede quedar atrás del código.

1. **¿Qué está aprobado?** Un change está **aprobado** cuando el PR de su propuesta está mergeado en `main` (la misma convención que `platform-auth`, PR #10). Si su carpeta en `openspec/changes/` existe solo en una rama sin mergear, todavía está en revisión: no lo apliques.
2. **¿Qué está implementado?** `openspec list --json` muestra los changes en curso y sus tareas. Un change archivado debe tener las tareas verificadas antes de cerrarse; ante una duda, contrastá los archivos y tests con el código actual.
3. **¿Qué existe en el código?** Los comandos de verificación de la [sección 2](#2-qué-tiene-que-existir-antes) (C1–C6).
4. **Qué hacer, según lo que encontraste:**

| Situación | Qué hacer |
|---|---|
| Falta C1–C4 | Esperar la infraestructura de tenant; no implementar ABM de club |
| C1–C4 existen, pero falta C5 o C6 | Resolver primero identidad y permisos de club |
| C1–C6 existen | Seguir con la primera ola pendiente de la [sección 4](#4-orden-de-implementación-olas) |
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
| Login, selección de club, invitaciones y recuperación de contraseña | Hecho en la API | `platform-auth`; la integración de pantallas se verifica en Front-Sportiva |
| Conexión `tenant`, cambio de base por request y jobs, `TenantDatabase`, permisos MySQL y tests multi-club | Hecho; PR #15 tiene CI verde | `tenant-connection` |
| Módulo 1 (`club_users`, roles y permisos) y resolución de permisos (`hasClubPermission`, `permissions[]` en `/me`) | Hecho | `club-access-control` y `club-custom-roles` |
| ABM y procesos de los módulos 2 a 8 | Implementados; ver estado por ola en la sección 4 | Changes de catálogo, planteles, calendario, competencia, pedidos e inventario |
| Alta de clubes y usuarios, `ProvisionTenant`, `tenants:create`, `tenants:migrate`, conexión `provisioning` y worker | Hecho en la rama principal | `platform-admin-and-provisioning`; usa `TenantDatabase` y el seeder de roles del club |

### Archivos que el dev de ABM **no** toca

Son de Tiziano. Tocarlos genera conflictos y rompe la frontera entre los changes:

- `ProvisionTenant`, los comandos `tenants:*`, los endpoints `/api/admin/*` y su controller.
- El servicio `queue` (worker) en `compose.yaml`.
- `database/migrations/` (la base central) y los modelos `User`, `Tenant` y `TenantMembership`.

`tenant-connection` hizo una excepción acordada: `TenantFactory` genera slugs `test_*`, porque en `testing` `TenantDatabase` solo acepta bases `club_test_*`.

Y Tiziano no toca lo que entrega `tenant-connection`: `config/database.php` (conexión `tenant`), `EnsureTenantContext`, `App\Tenancy\TenantDatabase`, `docker/mysql/init/02-club-dbs.sh` y `Tests\Concerns\InteractsWithTenants`. Si alguno necesita cambiar algo del otro, se acuerda y se anota en el change.

## 2. Qué tiene que existir antes

Los ABM de club **no se pueden probar** sin esto: el test 10 de la guía ABM (aislamiento entre clubes) necesita dos bases de club reales. C1 a C4 los entrega `tenant-connection`; C5 y C6, `club-access-control`. Ninguno depende del alta de clubes de Tiziano.

| # | Pieza | Qué necesita el dev de ABM | Cómo verificar que existe |
|---|---|---|---|
| C1 | Hecho y verificado | Conexión `tenant`; los modelos de club usan esa conexión | `config/database.php` y modelos en `app/Models/Club/` |
| C2 | Hecho y verificado | `tenant.token` conecta al club del token y limpia/restaura el contexto al terminar | `app/Http/Middleware/EnsureTenantContext.php` |
| C3 | Hecho y verificado | Migraciones en `database/migrations/tenant/` y `TenantDatabase::migrate()` | `database/migrations/tenant/` y tests de tenancy |
| C4 | Hecho y verificado | Tests multi-club con `tenantA()`, `tenantB()`, `clubToken()` e `inTenant()` | `tests/Concerns/InteractsWithTenants.php` |
| C5 | Hecho y verificado | `hasClubPermission()` y catálogo en `config/club-permissions.php` ([roles y permisos](../roles-y-permisos.md)) | `app/Models/User.php` y `config/club-permissions.php` |
| C6 | Hecho y verificado | `currentClubUser()` y tablas de identidad/roles del club | `app/Models/User.php` y `database/migrations/tenant/` |

**C1 a C6 existen en el código** (`tenant-connection` y `club-access-control`); la CI de tenant-connection pasó y las migraciones de club se ejecutan en los tests multi-club.

**Sin el alta de clubes de Tiziano** se puede desarrollar y testear todo (los tests crean sus propias bases `club_test_*`). Lo único que falta es probar a mano desde Swagger contra un club local, que necesita `tenants:create`.

## 3. Decisiones que hay que cerrar antes de migrar

Se cierran **en el change de OpenSpec de la ola** que las necesita (en el `design.md`), y después se actualiza el documento del módulo. Cada una tiene una recomendación; si el equipo elige otra cosa, se corrige el doc.

| # | Decisión | Recomendación | Ola |
|---|---|---|---|
| D1–D9 | Las 9 [diferencias con el borrador](README.md#diferencias-con-el-borrador-y-con-el-repo) | La opción "en este doc" de cada una | 1 (se revisan todas juntas) |
| D10 | Quién hace el módulo 1 (identidad y roles del club) | **Cerrada (`club-access-control`):** los roles y permisos viven en la base del club; `tenant_memberships` solo controla el acceso al club | Antes de la ola 1 |
| D11 | Qué responde `DELETE` sobre un catálogo que está en uso (por ejemplo, un deporte con categorías) | **Cerrada (TIZ-14):** 409 `resource_in_use` para una temporada con categorías y un deporte con categorías activas | 1 |
| D12 | Catálogos: baja lógica (`deleted_at`) o desactivar (`is_active`) | **Cerrada (TIZ-14):** `is_active` para deportes y categorías, borrado real para temporadas sin uso, `deleted_at` para profes | 1 |
| D13 | Cómo se genera `orders.order_number` | **Cerrada (TIZ-18):** secuencia por club con prefijo de año (`2027-0001`), generada en una transacción | 4 |
| D14 | Signo de la cantidad en `inventory_movements` de tipo `adjustment` | **Cerrada (TIZ-17):** cantidad con signo (positivo suma, negativo resta, distinto de cero). En `in`, `out` y `transfer_*` siempre positiva | 4 |
| D15 | ¿Hace falta `players.document_number`? Retención de evaluaciones físicas y auditoría | **Parcial (TIZ-15):** `document_number` queda opcional, solo si la liga lo pide; nada médico en `players.notes` (los aptos van como requisitos y documentos privados, TIZ-29 y TIZ-32). Falta definir la retención con el equipo | 2 y 4 |

## 4. Orden de implementación (olas)

El orden sigue las FK: una tabla se implementa cuando ya existen las tablas a las que apunta. El estado de la tabla siguiente se verificó contra el código el 3 de noviembre de 2026; una ola puede incluir más de un change/PR.

| Ola | Tablas | Depende de | Por qué en este orden |
|---|---|---|---|
| −1 | Infraestructura: conexión `tenant`, `TenantDatabase`, permisos MySQL y tests multi-club | Nada | **Cerrada** (PR #15, CI verde); entrega C1–C4 |
| 0 | `club_users`, `roles`, `permissions`, pivotes y roles custom | Ola −1 | **Cerrada** (`club-access-control`, `club-custom-roles`); entrega C5–C6 |
| 1 | Deportes, temporadas, categorías, profes, depósitos, materiales, indumentaria e instalaciones | Ola 0 | **Implementada**: migraciones y ABM disponibles |
| 2 | Jugadores, contactos, planteles, ramas/géneros, asignaciones de profes y componentes de instalaciones | Ola 1 | **Implementada**; la regla de rama/género valida asignaciones y cambios |
| 3 | Partidos, entrenamientos, asistencia, calendario, competencias, habilitación y convocatorias | Ola 2 | **Implementada**; el aviso de convocatorias por email tiene verificación integrada pendiente |
| 4 | Pedidos, inventario, evaluaciones físicas, reparaciones y mantenimiento | Ola 3 | **Implementada**: procesos con estados, transacciones y permisos |
| 5 | Alertas, notificaciones y auditoría | Según los módulos anteriores | **Implementada**; consultas de actividad son de solo lectura y las notificaciones son personales |

El change `club-announcements` sigue abierto: los jobs están en el código, pero faltan pruebas de idempotencia/fallo y la verificación integrada con worker. `automate-windows-bootstrap` también sigue abierto y vigente mientras el setup de Windows lo documente; su matriz manual de instalación aún no está cerrada.

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
| `orders` + líneas | Proceso | `apiResource('orders')` (solo editable en `draft`) + `POST /orders/{o}/submit`, `/approve`, `/reject`, `/deliveries` | `apparel_orders.*` / `material_orders.*` según `type`: `view/create/update/delete/approve/deliver` ([roles-y-permisos](../roles-y-permisos.md)) | Soft delete; solo en `draft` |
| `order_status_history` | Interna | `GET /orders/{o}/history` (solo lectura) | `apparel_orders.view` / `material_orders.view` | — |
| `inventory_balances` | Solo lectura | `GET /inventory/balances` | `inventory.view` | — |
| `inventory_movements` | Libro (sin update ni delete) | `GET` y `POST /inventory/movements`, `POST /inventory/transfers` | `inventory.view/adjust/transfer` | Nunca |
| `physical_evaluations` + mediciones | ABM con hijos en el mismo payload | `apiResource('physical-evaluations')` | `physical_evaluations.*` | Soft delete |
| `repair_requests` | ABM + acciones | `apiResource('repair-requests')` + `/assign`, `/resolve` | `repairs.*`, `repairs.assign` | Soft delete |
| `maintenance_records` | ABM | `apiResource('maintenance-records')` | `maintenance.*` | Borrado real |
| `notifications` | Del usuario actual | `GET /notifications`, `PATCH /notifications/{n}/read`, `POST /notifications/read-all` | Sin permiso: cada usuario ve las suyas | — |
| `audit_logs` | Solo lectura | `GET /audit-logs` en club y `GET /admin/audit-logs` en plataforma | `audit.view` en club; admin de plataforma | Nunca |

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

1. Consultá `openspec list` y elegí la primera capacidad realmente pendiente de la sección 4. Las olas −1 a 5 ya tienen implementación en el código; los changes abiertos de seguimiento (por ejemplo, anuncios o bootstrap de Windows) no autorizan a reimplementar las tablas ya entregadas.
2. En el `design.md`: las decisiones de la [sección 3](#3-decisiones-que-hay-que-cerrar-antes-de-migrar) que le tocan a la ola, los endpoints y permisos de la [sección 4](#qué-tipo-de-endpoint-lleva-cada-tabla), y las dependencias C1–C6 que todavía faltan.
3. En los `specs/`: un requirement por recurso, con escenarios para los 10 casos de la guía ABM.
4. `openspec validate <change> --strict`.
5. **Revisión del equipo y aprobación.** Sin aprobación no se implementa.
6. Si una decisión cambia el modelo, actualizá el documento del módulo en el mismo PR de la propuesta.

### Paso 2: implementar (solo con el change aprobado **y** C1–C4 en `main`)

Si C1–C4 no están en `main`, frená acá. No crees una conexión `tenant` provisoria, no apuntes los modelos de club a `mysql` y no corras las migraciones de club contra `sportiva_platform`. En el estado verificado el 3 de noviembre de 2026, C1–C6 ya existen.

1. `git checkout main && git pull && git checkout -b feat/<modulo-pendiente>`.
2. `/opsx:apply <change-aprobado>`.
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
