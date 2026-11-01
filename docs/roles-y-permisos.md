# Roles y permisos del club

> **Estado:** decidido en TIZ-10. **Implementado en la API (TIZ-11, `club-access-control`):** catálogo, roles estándar, sincronización, permisos con alcance, `/api/me` y roles al dar acceso desde `/admin`. **Roles custom y último administrador (TIZ-12, `club-custom-roles`):** endpoints del club y del admin de plataforma (§7). **Pendiente:** `/api/club-users` e invitaciones (dependen de los mails), menú por permiso (FRO-13) y pantalla "Equipo del club" (FRO-14).

Este documento define **quién puede hacer qué dentro de un club**: el catálogo de permisos, los roles estándar, los roles custom y cómo se asignan. El modelo de tablas está en [modelo-datos-club/01](modelo-datos-club/01-identidad-y-autorizacion.md).

## 1. Resumen

- **El catálogo de permisos es fijo y vive en el código** (`config/club-permissions.php`). Se organiza por **módulo → recurso → acciones**, y se sincroniza a la tabla `permissions` de cada club. Desde el panel no se crean permisos: el código tiene que saber chequearlos.
- **Cada club arranca con roles estándar** (Administrador, Coordinador, Profe, Preparador físico, Utilero / Encargado, Mantenimiento). Son **fijos**: no se editan ni se borran.
- **Los roles custom** se crean desde el panel con una **grilla de checks** (módulo × acción). Para partir de un estándar, se usa **Duplicar**.
- **Un usuario puede tener varios roles** en el club, y los permisos **se suman**. Ejemplo: un profe que además se encarga de los pedidos de indumentaria tiene **Profe + Utilero**, o un rol custom "Profe con indumentaria".
- **Alcance:** algunos permisos se dan sobre **todo el club** (`all`), sobre **sus deportes** (`sport`, TIZ-35) o solo sobre **sus categorías** (`own`). Por ejemplo, el coordinador de fútbol ve y gestiona todo lo de fútbol y nada de básquet; un profe ve solo las categorías que dirige; y el coordinador general ve todo el club.
- **Quién gestiona los roles:** el **admin de plataforma** desde `/admin`, cuando prepara el club, y el **Administrador del club** desde "Equipo del club". Los dos usan las mismas acciones del backend.
- **El admin de plataforma no es un rol de club.** Es `users.is_platform_admin` y solo ve `/admin`.

## 2. Catálogo de permisos

Las claves siguen [guia-abm §9](guia-abm.md#9-autorización-policies-y-permisos): `<recurso>.<acción>`, en inglés y en plural. La columna **Alcance** indica si el permiso admite "sus categorías" (ver [§4](#4-alcance-todo-el-club-o-sus-categorías)).

| Módulo (front) | Recurso | Acciones | Alcance |
|---|---|---|---|
| **Jugadores** (`jugadores`) | `players` | `view`, `create`, `update`, `delete` | sí |
| | `categories` | `manage_players` (armar planteles) | sí |
| | `player_contacts` | `view`, `update` (tutores y contactos, TIZ-15) | sí |
| | `player_documents` | `view`, `upload`, `delete` (apto, ficha, DNI, TIZ-29) | sí, `view` y `upload` |
| **Profes** (`profes`) | `coaches` | `view`, `create`, `update`, `delete` | sí (`view`) |
| | `categories` | `manage_coaches` (asignar profes a categorías) | — |
| **Partidos** (`partidos`) | `matches` | `view`, `create`, `update`, `delete` | sí |
| | `trainings` | `view`, `create`, `update`, `delete` | sí |
| | `attendance` | `view`, `record` | sí |
| **Análisis** (`analisis`) | `physical_evaluations` | `view`, `create`, `update`, `delete` | sí |
| **Proyecto** (`competencia`) | `project` | `view`, `update` | — |
| **Habilitación** (`habilitacion`) | `competitions` (ligas, torneos y sus requisitos, TIZ-31) | `view`, `create`, `update`, `delete` | sí, solo `view` |
| | `player_requirements` (papeles de cada jugador, TIZ-32) | `view`, `update` (presentar), `approve` (aprobar o rechazar) | sí, `view` y `update` |
| **Indumentaria** (`indumentaria`) | `apparel_items` (catálogo de prendas) | `view`, `create`, `update`, `delete` | — |
| | `apparel_orders` | `view`, `create`, `update`, `delete`, `approve`, `deliver` | sí |
| **Materiales** (`materiales`) | `materials` | `view`, `create`, `update`, `delete` | — |
| | `storage_locations` | `view`, `create`, `update`, `delete` | — |
| | `inventory` | `view`, `adjust`, `transfer` | — |
| | `material_orders` | `view`, `create`, `update`, `delete`, `approve`, `deliver` | sí |
| **Canchas** (`canchas`) | `facilities` | `view`, `create`, `update`, `delete` | — |
| | `repairs` | `view`, `create`, `update`, `delete`, `assign` | — |
| | `maintenance` | `view`, `create`, `update`, `delete` | — |
| **Configuración** | `club` (perfil del club) | `update` | — |
| | `sports` | `view`, `create`, `update`, `delete` | — |
| | `seasons` | `view`, `create`, `update`, `delete` | — |
| | `categories` | `view`, `create`, `update`, `delete` | sí, solo `view` (TIZ-36) |
| | `venues` (sedes del club, TIZ-39) | `view`, `create`, `update`, `delete` | — |
| **Equipo del club** | `club_users` | `view`, `invite`, `update`, `disable` | — |
| | `roles` | `view`, `create`, `update`, `delete`, `assign` | — |
| **Comunicados** | `announcements` | `view`, `send` | sí |

Cambios respecto del [plan de implementación](modelo-datos-club/plan-implementacion.md#qué-tipo-de-endpoint-lleva-cada-tabla):

- `orders.*` se separa en **`apparel_orders.*`** y **`material_orders.*`**. Es la misma tabla `orders` (columna `type`), pero así se puede dar "pedir indumentaria" sin dar "pedir materiales". La Policy mira el `type` del pedido.
- Se agregan `project.*`, `club.update`, `club_users.*`, `roles.*` y `announcements.*`, que el plan no tenía.

### Qué módulo del front habilita cada permiso

**Un módulo aparece en el menú si el usuario tiene al menos un permiso de ese módulo.** Para los links y el chequeo rápido, cada módulo tiene una clave principal:

| `ModuleId` (`lib/modules.ts`) | Clave principal |
|---|---|
| `jugadores` | `players.view` |
| `profes` | `coaches.view` |
| `partidos` | `matches.view` |
| `analisis` | `physical_evaluations.view` |
| `competencia` | `project.view` |
| `indumentaria` | `apparel_orders.view` |
| `materiales` | `inventory.view` |
| `canchas` | `facilities.view` |
| Configuración | `categories.view` |
| Equipo del club | `club_users.view` |
| Comunicados | `announcements.view` |

El **Inicio** (dashboard) lo ve todo usuario con acceso al club. Cada tarjeta muestra solo los datos de los módulos que el usuario puede ver.

## 3. Roles estándar

Se cargan en cada club al darlo de alta, con `is_system = true`. Todos se pueden asignar, pero ninguno se edita ni se borra.

| `slug` | Nombre | Para quién |
|---|---|---|
| `admin` | Administrador del club | Comisión directiva o gerente |
| `coordinator` | Coordinador deportivo | Coordina categorías, profes y fixture de todo el club |
| `sport_coordinator` | Coordinador de deporte | Coordina sus deportes asignados: categorías, planteles, profes y partidos (TIZ-35) |
| `coach` | Profe | Entrenador de una o más categorías |
| `physical_trainer` | Preparador físico | Evaluaciones físicas de sus categorías |
| `inventory_manager` | Utilero / Encargado | Materiales, indumentaria y entregas |
| `maintenance_manager` | Mantenimiento | Canchas e instalaciones |

### Matriz de los roles estándar

**Administrador del club: todos los permisos del catálogo, con alcance "todo el club".** Cuando se agrega un permiso nuevo al catálogo, el admin lo recibe automáticamente.

Para el resto: ✓ = todo el club, **(s)** = solo sus categorías, — = nada.

| Recurso | Coordinador | Profe | Preparador físico | Utilero / Encargado | Mantenimiento |
|---|---|---|---|---|---|
| `players` | ✓ ver, crear, editar, eliminar | ver (s) | ver (s) | — | — |
| `categories.manage_players` | ✓ | — | — | — | — |
| `player_contacts` | ✓ ver, editar | ver (s) | — | — | — |
| `player_documents` | ✓ ver, subir, borrar | ver, subir (s) | — | — | — |
| `competitions` | ✓ ver, crear, editar, eliminar | ver (s) | ver (s) | — | — |
| `player_requirements` | ✓ ver, presentar, aprobar | ver, presentar (s) | ver (s) | — | — |
| `coaches` | ✓ ver, crear, editar, eliminar | ver (s) | ver (s) | — | — |
| `categories.manage_coaches` | ✓ | — | — | — | — |
| `matches` | ✓ ver, crear, editar, eliminar | ver, crear, editar (s) | ver (s) | ver | ver |
| `trainings` | ✓ ver, crear, editar, eliminar | ver, crear, editar (s) | ver (s) | ver | ver |
| `callups` (convocatorias, TIZ-33) | ✓ ver, armar, publicar | ver, armar, publicar (s) | ver (s) | ver | — |
| `attendance` | ✓ ver, registrar | ver, registrar (s) | ver (s) | — | — |
| `physical_evaluations` | ✓ ver | ver (s) | ver, crear, editar, eliminar (s) | — | — |
| `project` | ✓ ver, editar | ver | ver | — | — |
| `apparel_items` | ✓ ver | ver | — | ✓ ver, crear, editar, eliminar | — |
| `apparel_orders` | ✓ ver, crear, editar | ver, crear, editar (s) | — | ✓ ver, crear, editar, entregar | — |
| `materials`, `storage_locations` | ✓ ver | — | — | ✓ ver, crear, editar, eliminar | — |
| `inventory` | ✓ ver | — | — | ✓ ver, ajustar, transferir | — |
| `material_orders` | ✓ ver, crear, editar | ver, crear, editar (s) | ver, crear, editar (s) | ✓ ver, crear, editar, entregar | — |
| `facilities` | ✓ ver | ver | ver | ver | ✓ ver, crear, editar, eliminar |
| `repairs` | ✓ ver, crear | ver, crear | ver, crear | ver, crear | ✓ todas, incluida `assign` |
| `maintenance` | ✓ ver | — | — | — | ✓ ver, crear, editar, eliminar |
| `sports`, `seasons`, `categories` | ✓ ver, crear, editar, eliminar | ver `categories` (s) | ver `categories` (s) | — | — |
| `venues` (sedes, TIZ-39) | ✓ ver, crear, editar, eliminar | ver | ver | ver | ver |
| `club.update` | — | — | — | — | — |
| `club_users`, `roles` | — | — | — | — | — |
| `announcements` | ✓ ver, enviar | ver, enviar (s) | — | — | — |

Notas:

- **Coordinador de deporte (`sport_coordinator`):** tiene exactamente los mismos permisos que el Coordinador general (`coordinator`), pero con alcance **sus deportes** (`sport`, TIZ-35) en todos los permisos marcados como con alcance en el catálogo (`players.*`, `categories.manage_players`, `player_contacts.*`, `player_documents.view/upload`, `coaches.view`, `matches.*`, `trainings.*`, `attendance.*`, `callups.*`, `physical_evaluations.view`, `apparel_orders.view/create/update`, `material_orders.view/create/update`, `categories.view`, `competitions.view`, `player_requirements.view/update`, `announcements.*`).
- **Aprobar pedidos** (`apparel_orders.approve`, `material_orders.approve`) queda solo para el Administrador. Si un club quiere que el coordinador apruebe, crea un rol custom.
- `delete` en pedidos solo aplica a pedidos en `draft` (lo valida la Policy, no el permiso).

## 4. Alcance: todo el club, sus deportes o sus categorías

Cada permiso asignado a un rol tiene un **alcance**: `all` (todo el club), `sport` (sus deportes, TIZ-35) o `own` (sus categorías). Se guarda en `role_permissions.scope`. Los recursos marcados como con alcance en el catálogo admiten `sport` y `own`; en los demás, `scope` siempre es `all`.

- **Todo el club (`all`):** sin restricciones de deporte ni categoría.
- **Sus deportes (`sport`):** limitado a los deportes asignados al usuario en `club_user_sports` (asignados desde la plataforma al dar de alta o editar la membresía). El usuario ve y gestiona las categorías, jugadores, profes, partidos, convocatorias, torneos y pedidos pertenecientes a esos deportes. Un usuario con alcance `sport` pero sin deportes asignados ve listas vacías y recibe 404 al buscar registros directos (nunca "todo").
- **Sus categorías (`own`):** son las categorías donde el usuario tiene una asignación vigente como entrenador: `coaches.club_user_id = <club_user>` y `category_coaches` sin `end_date` o con `end_date` futura.

| Recurso | Con alcance `sport` o `own`, el usuario ve o modifica... |
|---|---|
| `players` | Los jugadores con membresía vigente en sus deportes / categorías |
| `categories.manage_players` | Los planteles de sus deportes / categorías |
| `player_contacts` | Los contactos de los jugadores de sus deportes / categorías |
| `coaches` | Su propia ficha y los profes de sus deportes / categorías |
| `categories.view` | Las categorías de sus deportes asignados o donde es profe |
| `competitions.view` | Las competencias de sus deportes o que juega alguna de sus categorías |
| `matches`, `trainings`, `attendance` | Los partidos y entrenamientos de sus deportes / categorías |
| `physical_evaluations` | Las evaluaciones de los jugadores de sus deportes / categorías |
| `apparel_orders`, `material_orders` | Los pedidos para sus deportes / categorías o pedidos pedidos por él (`requested_by`) |
| `announcements` | Comunicados de sus deportes / categorías |

Reglas:

- **Jerarquía y varios roles:** `all` (peso 3) > `sport` (peso 2) > `own` (peso 1). Si dos roles dan el mismo permiso con distinto alcance, **gana el más amplio** (por ejemplo, `sport` + `own` da `sport`; `sport` + `all` da `all`).
- **Sin asignaciones:** un usuario con alcance `sport` sin deportes, o con `own` sin categorías, ve listas vacías, no un error 500 ni "todo".
- **Selector de deportes en el front:** `GET /api/me` devuelve la lista de deportes asignados al usuario en `sports` (`[{id, name}]`) para alimentar el selector de deporte del front.
- **En el backend:** `hasClubPermission('players.view')` responde si tiene el permiso (con cualquier alcance) y `clubPermissionScope('players.view')` devuelve `all`, `sport`, `own` o `null`. Con alcance reducido, el controlador y los modelos aplican `ClubAccess::scopedCategoryIds($permission)`; la comprobación responde **404**, no 403, para no revelar que el registro existe.

## 5. Roles custom

- Se crean desde el panel con **nombre**, **descripción** y la **grilla de checks**. El `slug` se genera a partir del nombre.
- **La grilla** tiene una sección por módulo, una fila por recurso y una columna por acción. En los recursos con alcance, cada check marcado tiene un selector "Todo el club / Sus categorías". Las acciones que el recurso no tiene no se muestran.
- **Duplicar** un rol (estándar o custom) crea un custom con los mismos permisos y el nombre "Copia de …". Es la forma de modificar un rol estándar.
- **Editar** un custom aplica en la siguiente request de cada usuario que lo tenga. Los permisos se leen de la base en cada request, no del token.
- **Borrar** un custom asignado a alguien responde **409** (`code: role_in_use`): primero hay que sacárselo a esos usuarios.
- **Sin escalada:** quien crea o edita un rol solo puede dar permisos (y alcances) que él mismo tiene. Hoy el único rol estándar con `roles.*` es el Administrador, que tiene todo, pero la regla protege un futuro custom con `roles.create`.
- **Permisos nuevos:** cuando el catálogo agrega un permiso, los roles custom **no** lo reciben automáticamente. El Administrador decide si se los agrega.

## 6. Asignación de roles a usuarios

- **El acceso al club** sigue siendo la membresía central (`tenant_memberships` activa) más `club_users.status = active`. **Lo que puede hacer** sale solo de los roles del club.
- Al dar acceso a un usuario (desde `/admin` o con una invitación desde "Equipo del club"), **se eligen uno o más roles**. No se puede dar acceso sin al menos un rol.
- **Último administrador:** no se le puede sacar el rol `admin` al último usuario activo que lo tiene, ni deshabilitarlo (**409**, `code: last_admin`). Un club nunca queda sin administrador.
- **El alta del club** (`ProvisionTenant`) crea los roles estándar, sincroniza los permisos y le asigna `admin` al primer usuario que se indica.

### Quién gestiona

| Desde | Quién | Puede |
|---|---|---|
| `/admin` → club | Admin de plataforma | Ver, crear, editar y borrar roles custom de **cualquier** club; asignar roles a los usuarios del club |
| Equipo del club (FRO-14) | Usuario con `roles.*` y `club_users.*` en ese club (estándar: Administrador del club) | Lo mismo, solo en su club |

Los dos caminos llaman a las mismas Actions (`App\Club\Actions\Roles\...`). Cambian la ruta, el middleware y cómo se resuelve el club.

## 7. Endpoints (a implementar en TIZ-11 y TIZ-12)

Club actual (token con club, middleware `tenant.token`):

| Método y ruta | Permiso |
|---|---|
| `GET /api/permissions`: catálogo para armar la grilla (módulos, recursos, acciones, si admite alcance) | `roles.view` |
| `GET /api/roles`, `GET /api/roles/{role}` | `roles.view` |
| `POST /api/roles` | `roles.create` |
| `PATCH /api/roles/{role}`: 403 `role_is_system` si es estándar | `roles.update` |
| `DELETE /api/roles/{role}`: 403 `role_is_system`, 409 `role_in_use` | `roles.delete` |
| `POST /api/roles/{role}/duplicate` | `roles.create` |
| `PUT /api/club-users/{clubUser}/roles`: reemplaza la lista completa, idempotente. **Pendiente**, con la gestión del equipo | `roles.assign` |
| `GET /api/club`: perfil del club (sigla, ciudad, fundación, color) con jugadores activos y deportes (TIZ-13) | Cualquier miembro |
| `PATCH /api/club`: editar el nombre y el perfil. `slug` y `status` los maneja la plataforma (TIZ-13) | `club.update` |

`{role}` es el `slug` del rol (por ejemplo `/api/roles/coach`).

Admin de plataforma, mismo contrato bajo `/api/admin/tenants/{tenant}/...`: `permissions`, `roles` y `roles/{role}/duplicate`. En un club que no está activo, crear, editar o borrar responde 409 `tenant_inactive`. Los roles de un miembro se asignan con `roles` en `POST` y `PATCH /api/admin/tenants/{tenant}/memberships` (ya implementado, igual que `GET /api/admin/tenants/{tenant}/roles`).

Payload de un rol:

```json
{
  "name": "Profe con indumentaria",
  "description": "Profe que además gestiona los pedidos de ropa de su categoría",
  "permissions": [
    { "name": "players.view", "scope": "own" },
    { "name": "apparel_orders.view", "scope": "all" },
    { "name": "apparel_orders.deliver", "scope": "all" }
  ]
}
```

`select-tenant` y `GET /api/me` siguen devolviendo `permissions[]` (claves). Se agrega `permission_scopes` (`{ "players.view": "own" }`) para que el front ajuste textos y filtros. **La autoridad es siempre la API**: el front solo esconde lo que la API igual rechazaría con 403.

## 8. Cambios de esquema

**Base del club** (migración nueva en `database/migrations/tenant/`, no se editan las mergeadas):

| Tabla | Cambio |
|---|---|
| `roles` | `+ slug VARCHAR(80) UNIQUE` (clave estable: `admin`, `coach`…; en custom, generado del nombre), `+ is_system BOOLEAN DEFAULT false`. `name` pasa a ser el nombre visible (sigue `UNIQUE`) |
| `permissions` | `+ module VARCHAR(40)` (para agrupar la grilla) |
| `role_permissions` | `+ scope VARCHAR(10) DEFAULT 'all'` (`all` / `own`) |

**Base central:**

| Columna | Decisión |
|---|---|
| `users.is_platform_admin` | Se queda como **única** marca de admin de plataforma |
| `users.role` (`general` / `admin`, PR #41) | **Se elimina** (migración `drop column`), junto con el campo `role` de `UserResource` y el `Fillable`. Duplicaba `is_platform_admin` |
| `tenant_memberships.role` y el enum `MembershipRole` | **Se eliminan.** La membresía solo dice "tiene acceso a este club". El rol vive en la base del club. Resuelve la [diferencia 4](modelo-datos-club/README.md#diferencias-con-el-borrador-y-con-el-repo). El formulario de membresías de `/admin` pasa a elegir roles del club |

## 9. Sincronización del catálogo

- **`config/club-permissions.php`** es la fuente de verdad: módulos, recursos, acciones, si admiten alcance y la matriz de los roles estándar de [§3](#matriz-de-los-roles-estándar).
- **`php artisan club:sync-permissions`** corre en **todas** las bases de club (como `tenants:migrate`) y es idempotente:
  1. Crea los permisos nuevos y borra los que ya no están en el catálogo (sus asignaciones se van por `CASCADE`).
  2. Crea los roles estándar que falten y **resincroniza sus permisos** con la matriz.
  3. No toca los roles custom, salvo para quitarles permisos borrados.
- Corre en el alta del club (`ProvisionTenant`) y en cada deploy, después de `tenants:migrate`.
- **Cada ABM nuevo** agrega sus permisos al catálogo y a la matriz de los roles estándar en el mismo PR (como pide guia-abm §9).

## 10. Fuera de alcance

- **Permisos por registro individual** (por ejemplo, "este profe puede editar este jugador puntual"): solo existen los dos alcances `all` y `own`.
- **Roles de plataforma** distintos de `is_platform_admin`, como soporte o solo lectura.
- **Auditoría de cambios de roles:** se registra en `audit_logs` cuando exista la ola 5. Mientras tanto, `AuditLogger` registra la asignación desde `/admin`.
