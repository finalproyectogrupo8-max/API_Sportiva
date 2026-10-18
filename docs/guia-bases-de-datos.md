# Guía para devs: bases de datos y migraciones

Esta guía explica la base central, la base de cada club, cómo se escriben y aplican las migraciones, el alta de clubes (con su job), el panel de administración y los comandos del día a día.

> **Cómo leerla.** Cada sección dice si lo que describe **✅ EXISTE HOY** en `main` o si está **🟡 PLANIFICADO**: diseñado y acordado, pero todavía no implementado. Los nombres de los comandos y endpoints planificados pueden cambiar un poco al implementarse. La fuente de verdad final es el código y las specs de OpenSpec.
>
> - Versión del documento: 17 de octubre de 2026.
> - Estado de `main` al escribirla: `28e7df1`.
> - Stack: Laravel 13, PHP 8.5, MySQL 8.4, Docker Compose en local y Laravel Cloud en producción.
> - Documentos relacionados: [arquitectura-multi-tenant.md](arquitectura-multi-tenant.md), [desarrollo-local.md](desarrollo-local.md), [ADR 0001](adr/0001-stack-y-arquitectura.md) y `docs/integracion-api-laravel.md` §5.6, en el repo Front-Sportiva.

## Índice

1. [Resumen en un minuto](#1-resumen-en-un-minuto)
2. [Qué vive en cada base y por qué](#2-qué-vive-en-cada-base-y-por-qué)
3. [Situación actual en main](#3-situación-actual-en-main)
4. [Los tres conceptos que hacen funcionar todo](#4-los-tres-conceptos-que-hacen-funcionar-todo)
5. [Cómo se diferencian las migraciones](#5-cómo-se-diferencian-las-migraciones)
6. [Cómo se aplican las migraciones a cada club](#6-cómo-se-aplican-las-migraciones-a-cada-club)
7. [Alta de un club: el job ProvisionTenant](#7-alta-de-un-club-el-job-provisiontenant)
8. [Qué pasa en un request del día a día](#8-qué-pasa-en-un-request-del-día-a-día)
9. [Panel de administración de plataforma](#9-panel-de-administración-de-plataforma)
10. [Reglas para escribir migraciones](#10-reglas-para-escribir-migraciones)
11. [Comandos](#11-comandos)
12. [Preguntas frecuentes](#12-preguntas-frecuentes)
13. [Qué falta y en qué orden](#13-qué-falta-y-en-qué-orden)

---

## 1. Resumen en un minuto

- Hay **una sola API Laravel**, en este repo y con un solo deploy. **No** hay un microservicio de autenticación aparte.
- La API usa **dos tipos de base de datos**, en el mismo servidor MySQL: una **base central** (`sportiva_platform`) y **una base por club** (`club_river`, `club_boca`, ...).
- **La central** guarda lo que es de toda la plataforma: usuarios, clubes, quién pertenece a qué club y los tokens. **Cada base de club** guarda lo operativo de ese club: jugadores, partidos, pedidos y los roles del club.
- Las migraciones se separan **por carpeta**: `database/migrations/` para la central y `database/migrations/tenant/` para los clubes. Hay **una sola carpeta para todos los clubes**, no una por club.
- `php artisan migrate` migra la central. Un comando propio, `php artisan tenants:migrate`, recorre los clubes y aplica las migraciones de club en cada base.
- Dar de alta un club crea su base y le corre todas las migraciones de club. Lo hace un **job en la cola** (`ProvisionTenant`), que se dispara desde el panel de administración del front (vía `/api/admin/*`) o por consola.
- **Hoy en `main`** existen la base central con auth (`platform-auth`), la conexión `tenant` y el servicio `TenantDatabase` que la apunta a la base de un club (`tenant-connection`). El alta de clubes (`ProvisionTenant`, `tenants:*`) todavía no (ver la sección 3).

```
                API Laravel (este repo, un solo deploy)
                ┌──────────────────────────────────────┐
                │ 1. Auth (Sanctum)                    │
request ──────> │ 2. Middleware de tenant: valida la   │
con token       │    membresía y apunta la conexión    │
                │    "tenant" a la base del club       │
                │ 3. Controllers y modelos del club    │
                └──────────────────────────────────────┘
              │                                │
      conexión "mysql"                conexión "tenant"
              ▼                                ▼
┌──────────────────────────┐    ┌─────────────────────────────┐
│ sportiva_platform        │    │ club_river  club_boca  ...  │
│   users                  │    │   jugadores, partidos,      │
│   tenants                │    │   pedidos, roles, permisos  │
│   tenant_memberships     │    │   (una base por club, todas │
│   personal_access_tokens │    │    con el MISMO esquema)    │
└──────────────────────────┘    └─────────────────────────────┘

Todo en el mismo servidor MySQL: local = contenedor "mysql";
producción = cluster de MySQL de Laravel Cloud.
```

## 2. Qué vive en cada base y por qué

| Base | Contenido | La usan |
| --- | --- | --- |
| `sportiva_platform` (central, una sola) | Usuarios de la plataforma, clubes (`tenants`), membresías (`tenant_memberships`: qué usuario pertenece a qué club y con qué rol), tokens de Sanctum, la cola de jobs y la caché | El login, la selección de club, el panel de administración y el alta de clubes |
| `club_<slug>` (una por club) | Todo lo operativo del club: jugadores, partidos, pedidos, y los roles y permisos internos del club | Todos los endpoints del día a día de un club |

**Un usuario, varias membresías.** Cada usuario existe **una sola vez**, en la base central. Si una persona trabaja en dos clubes, tiene **un** usuario y **dos** membresías.

**Por qué una base por club:**

- **Aislamiento:** un error en una consulta no puede mezclar datos de dos clubes, porque están en bases distintas.
- **Operación por club:** se puede hacer backup, restaurar, exportar o dar de baja un club entero sin tocar a los demás.
- **Costo:** cada migración de club hay que aplicarla en N bases (lo resuelve `tenants:migrate`), y en cada request hay que cambiar de conexión (lo resuelve el middleware).

**Por qué no un microservicio de autenticación aparte:**

| Un servicio de auth aparte implicaría... | Con todo en una sola API |
| --- | --- |
| Otro repo, otro deploy, otra base y otra CI | Un solo repo y un solo deploy |
| Un salto de red más en cada request (y ya hay uno: browser → Next.js → API) | Validar el token es una consulta local |
| Que dar de alta un club toque dos servicios a la vez: si uno falla, quedan inconsistentes | Todo pasa en un mismo proceso |
| Coordinar las versiones de los dos servicios | Sanctum viene integrado en Laravel |

La idea es un **monolito modular**: una sola aplicación, con el código separado por dentro. Por ejemplo, `App\Platform\...` para usuarios, clubes y auth, y `App\Club\...` para lo operativo. Si algún día hiciera falta separar la parte de plataforma, la frontera ya estaría marcada.

## 3. Situación actual en main

✅ **EXISTE HOY:**

| Pieza | Dónde |
| --- | --- |
| Migraciones centrales: las de Laravel (`users`, `cache`, `jobs`), `tenants`, `tenant_memberships`, `is_platform_admin` y `personal_access_tokens` con `tenant_id` | `database/migrations/` |
| Sanctum, los endpoints de auth (`login`, `select-tenant`, `me`, `logout`), `/api/admin/tenants` y `php artisan platform:create-admin` | change `platform-auth` |
| Migraciones de club (`players`, `sports`, `seasons`, `categories`, `coaches`) | `database/migrations/tenant/` |
| La conexión `tenant` (las mismas variables `DB_*` que `mysql`, sin base fija) | `config/database.php` |
| `App\Tenancy\TenantDatabase`: la única clase que apunta la conexión `tenant` a la base de un club (`connect`, `disconnect`, `run`) y que crea, migra y borra bases de club (`create`, `migrate`, `drop`) | change `tenant-connection`, [ADR 0002](adr/0002-multi-tenancy-implementacion-propia.md) |
| El middleware `tenant.token` apunta la conexión a la base del club del token y la desconecta al terminar el request | `app/Http/Middleware/EnsureTenantContext.php` |
| El permiso del usuario MySQL local sobre las bases `club_*` | `docker/mysql/init/02-club-dbs.sh` (hay que recrear el volumen una vez: ver [desarrollo-local.md](desarrollo-local.md#los-tests-de-club-fallan-por-permisos-de-mysql)) |
| Tests multi-club: dos clubes de prueba (`club_test_a` y `club_test_b`) con sus bases migradas | `tests/Concerns/InteractsWithTenants.php` |

🟡 **Todavía NO existe en `main`** (change `platform-admin-and-provisioning`, que usa `TenantDatabase`):

- el job `ProvisionTenant` y un worker de la cola: hoy `POST /api/admin/tenants` deja el club en `provisioning` y **no crea su base**;
- los comandos `tenants:create` y `tenants:migrate`;
- la tabla `club_users` (change `club-access-control`).

Para comprobarlo vos mismo:

```bash
# Qué migraciones existen y cuáles corrieron en la base central
docker compose exec backend php artisan migrate:status

# La conexión "tenant": database es null fuera de un request de club
docker compose exec backend php artisan config:show database.connections.tenant

# Bases que hay en el MySQL local (sportiva_platform, sportiva_testing y las club_test_* de los tests)
docker compose exec mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -u"$MYSQL_USER" -e "SHOW DATABASES"'
```

> La migración de `users` también crea la tabla `sessions`, que una API sin sesiones no usa. Se revisa en el change `platform-auth`, donde además se le agrega a `users` el campo `is_platform_admin`.

## 4. Los tres conceptos que hacen funcionar todo

### Pieza 1: una conexión es una dirección a una base

En `config/database.php`, cada **conexión** dice a qué servidor y a qué base conectarse. Hay una segunda conexión, `tenant`, con el **mismo servidor** y la base **vacía a propósito**:

```php
'mysql' => [                          // ✅ EXISTE HOY: la de siempre
    'host'     => env('DB_HOST'),          // "mysql" en local
    'database' => env('DB_DATABASE'),      // sportiva_platform
],
'tenant' => [                         // ✅ EXISTE HOY
    'host'     => env('DB_HOST'),          // el MISMO servidor
    'database' => null,                    // se completa en el momento
],
```

Es como una dirección con el número de la casa en blanco: el código lo completa justo antes de usarla.

Eso lo hace **solo** `App\Tenancy\TenantDatabase` (ADR 0002). Por dentro, valida el nombre, setea `database.connections.tenant.database` y llama a `DB::purge('tenant')` para olvidar la conexión anterior. En el código de un ABM o de un job nunca se toca la conexión a mano:

```php
// En un request de club no hace falta nada: el middleware tenant.token ya conectó.
// En un job, un comando o un test:
app(TenantDatabase::class)->run($tenant, function () {
    // acá, todo lo que use la conexión "tenant" va a la base de $tenant
});
// acá la conexión ya no apunta a ninguna base: una consulta falla con "No database selected"
```

> ⚠️ **Nunca usar `USE <base>`.** El proxy de MySQL de Laravel Cloud rechaza `USE club_river` (SQL Error 1148). La base ya va en el DSN al abrir la conexión (`dbname=club_river`), pero ojo: por defecto Laravel **además** ejecuta `use` después de conectar. Por eso la conexión `tenant` tiene `'use_db_after_connecting' => false`.

### Pieza 2: `migrate` puede apuntar a cualquier conexión y carpeta

```bash
php artisan migrate --database=tenant --path=database/migrations/tenant
#                   └─ a qué conexión  └─ qué carpeta de migraciones
```

Se lee así: "tomá las migraciones de la carpeta `tenant/` y aplicalas en la base a la que apunte ahora la conexión `tenant`".

### Pieza 3: cada base recuerda qué migraciones ya tiene

Laravel guarda en **cada base** una tabla `migrations` con la lista de las migraciones que ya corrió ahí. Antes de migrar, compara esa lista con la carpeta y **corre solo las que faltan**. Como cada club tiene su propia tabla `migrations`, cada uno lleva su propia cuenta.

> **Por qué `migrate` no toca la carpeta `tenant/`.** Está verificado en el código de Laravel (`Migrator::getMigrationFiles`): busca con `glob($path.'/*_*.php')`, **sin entrar en subcarpetas**. Por eso `php artisan migrate` nunca corre las migraciones de club en la base central.

## 5. Cómo se diferencian las migraciones

**Por la carpeta donde vive el archivo.** Es la única diferencia.

> ⚠️ **Una sola carpeta `tenant/` para TODOS los clubes.** No existe `database/migrations/river/`.
>
> - Las migraciones son el **plano** de la base de un club: código, en el repo, uno solo.
> - Las bases son los **datos** de cada club: una por club.
>
> Si un club necesita algo distinto, se resuelve con **datos** (una tabla de configuración, módulos que se activan), nunca con un esquema distinto. Si cada club tuviera su propio esquema, el código no sabría qué tablas existen en cada uno.

```
database/migrations/                          ← base CENTRAL (sportiva_platform)
├── 0001_01_01_000000_create_users_table.php       ✅ EXISTE HOY
├── 0001_01_01_000001_create_cache_table.php       ✅ EXISTE HOY
├── 0001_01_01_000002_create_jobs_table.php        ✅ EXISTE HOY
├── 2026_10_17_182514_create_tenants_table.php     ✅ EXISTE HOY
├── 2026_10_17_182632_create_tenant_memberships... ✅ EXISTE HOY
├── ...
└── tenant/                                   ← CADA CLUB (club_*)    ✅ EXISTE HOY
    ├── 2026_10_17_231953_create_players_table.php
    ├── 2026_10_17_232500_create_sports_table.php
    └── ...
```

Para crear una migración:

```bash
# Tabla de la base CENTRAL
docker compose exec backend php artisan make:migration create_tenants_table

# Tabla de CADA CLUB: la misma orden, con --path
docker compose exec backend php artisan make:migration create_players_table \
    --path=database/migrations/tenant
```

Ejemplo de migración central (🟡 planificado, ilustrativo):

```php
// database/migrations/2026_10_20_000001_create_tenants_table.php
Schema::create('tenants', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();           // river
    $table->string('name');                     // Club River
    $table->string('database_name')->unique();  // club_river
    $table->string('status');                   // provisioning | active | failed
    $table->timestamps();
});
```

Ejemplo de migración de club (🟡 planificado, ilustrativo):

```php
// database/migrations/tenant/2026_10_25_000001_create_players_table.php
// No dice a qué club: se aplica en el que esté apuntada la conexión "tenant".
Schema::create('players', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->date('birth_date');
    $table->timestamps();
});
```

Ejemplo de modelos:

```php
class Tenant extends Model { }            // base central: conexión por defecto

class Player extends Model               // base del club
{
    protected $connection = 'tenant';     // siempre la conexión del club actual
}
```

## 6. Cómo se aplican las migraciones a cada club

🟡 **PLANIFICADO.** El comando `tenants:migrate` es propio: se escribe en el change `platform-admin-and-provisioning`.

`tenants:migrate` es un **loop** que repite las piezas 1 y 2 de la sección 4 para cada club de la tabla `tenants`:

```php
foreach (Tenant::where('status', 'active')->get() as $tenant) {
    config(['database.connections.tenant.database' => $tenant->database_name]);  // pieza 1
    DB::purge('tenant');
    Artisan::call('migrate', [                                                 // pieza 2
        '--database' => 'tenant',
        '--path'     => 'database/migrations/tenant',
        '--force'    => true,
    ]);
    // informa OK o FALLO por club; si uno falla, sigue con los demás
}
```

### Ejemplo paso a paso

**Situación inicial:** hay dos clubes, y la carpeta `tenant/` tiene una sola migración, `create_players`.

```
sportiva_platform.tenants       club_river.migrations       club_boca.migrations
  slug    database_name           create_players   OK       create_players   OK
  river   club_river
  boca    club_boca
```

**Un dev agrega** `create_matches` en `database/migrations/tenant/` y se corre `php artisan tenants:migrate`:

```
Vuelta 1 → conexión tenant = club_river
           carpeta:               create_players, create_matches
           club_river.migrations: create_players
           → falta create_matches → la corre en club_river   OK

Vuelta 2 → conexión tenant = club_boca
           misma carpeta, misma comparación
           → falta create_matches → la corre en club_boca    OK
```

**Resultado:** los dos clubes tienen la tabla `matches`, cada uno en su base y con sus propios datos.

**Si mañana se da de alta "Racing":**

1. se crea `club_racing`, vacía;
2. se apunta la conexión `tenant` ahí;
3. se corre `migrate` sobre `tenant/`.

Como su tabla `migrations` está vacía, corre **todas** (`create_players` y `create_matches`). Racing queda exactamente igual a los demás.

**En el deploy, siempre en este orden:**

```bash
php artisan migrate --force            # 1. base central
php artisan tenants:migrate --force    # 2. todas las bases de clubes, una por una
```

## 7. Alta de un club: el job ProvisionTenant

🟡 **PLANIFICADO.** Se dispara desde el panel de admin (`POST /api/admin/tenants`) o por consola (`php artisan tenants:create river "Club River"`).

### Los pasos del alta

1. **Validar el slug** con `^[a-z0-9_]+$`. Es obligatorio, porque el nombre de una base no se puede pasar como parámetro de una consulta preparada: va concatenado en el SQL.
2. **Registrar el club** en la central: una fila en `tenants` con `database_name = club_river` y `status = provisioning`.
3. **Crear la base** con `CREATE DATABASE club_river`, usando el usuario MySQL de **provisión** (ver más abajo).
4. **Migrar solo esa base:** apuntar la conexión `tenant` a `club_river` y correr las migraciones de `tenant/`.
5. **Cargar los datos iniciales:** los roles y permisos del club, con un seeder.
6. **Activar:** `status = active`.

Si un paso falla, el club queda en `status = failed`, y el alta se puede reintentar sin duplicar nada (es idempotente).

### Qué es un job y por qué el alta usa uno

> Para crear, despachar, reintentar y testear jobs en general, ver la [guía de jobs](guia-jobs.md).

En Laravel, un **job** es una tarea que se guarda para ejecutarla **después**, en segundo plano, en lugar de hacerla mientras el usuario espera la respuesta. Crear la base y correrle todas las migraciones tarda segundos. Si se hiciera dentro del request, el panel quedaría colgado, y un timeout podría dejar el club a medio crear.

```
  API (request)                 cola (tabla "jobs")              worker (proceso aparte)
  ─────────────                 ───────────────────              ───────────────────────
  "creá el club River"   ──>    [ ProvisionTenant(river) ]  ──>  lo toma y lo ejecuta:
  responde ENSEGUIDA:                                            crea la base, migra,
  { status: provisioning }                                       carga roles, activa
```

| Pieza | Qué es | Estado |
| --- | --- | --- |
| La clase del job | Lo que hay que hacer (`App\Jobs\ProvisionTenant`) | 🟡 PLANIFICADO |
| La cola | Donde quedan los jobs pendientes: la tabla `jobs` de la base central (`QUEUE_CONNECTION=database`) | ✅ EXISTE HOY |
| El worker | El proceso que corre `php artisan queue:work` todo el tiempo, toma los jobs y los ejecuta | ❌ **NO EXISTE** (ver abajo) |

### Paso a paso: cómo se crea un club

**1. Alguien lo pide.** Hay dos puertas, y las dos hacen lo mismo:

```
Panel de admin (front)  ──>  POST /api/admin/tenants   { slug: "river", name: "Club River" }
Consola                 ──>  php artisan tenants:create river "Club River"
```

**2. La API registra el club, encola el job y responde enseguida** (🟡 ilustrativo):

```php
$tenant = Tenant::create([
    'slug'          => 'river',
    'name'          => 'Club River',
    'database_name' => 'club_river',
    'status'        => 'provisioning',
]);
ProvisionTenant::dispatch($tenant->id);       // queda en la tabla "jobs"

return response()->json(['status' => 'provisioning'], 202);   // 202 = aceptado, en proceso
```

**3. El worker ejecuta el job** (🟡 ilustrativo):

```php
class ProvisionTenant implements ShouldQueue
{
    public $tries = 3;                         // si falla, reintenta hasta 3 veces

    public function __construct(public int $tenantId) {}

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        // a. Crear la base, con el usuario MySQL de PROVISIÓN (el único que puede crear bases)
        DB::connection('provisioning')
          ->statement("CREATE DATABASE IF NOT EXISTS `{$tenant->database_name}`");

        // b. Apuntar la conexión "tenant" a la base nueva y migrarla
        config(['database.connections.tenant.database' => $tenant->database_name]);
        DB::purge('tenant');
        Artisan::call('migrate', ['--database' => 'tenant',
            '--path' => 'database/migrations/tenant', '--force' => true]);

        // c. Datos iniciales: roles y permisos del club
        Artisan::call('db:seed', ['--database' => 'tenant',
            '--class' => TenantSeeder::class, '--force' => true]);

        // d. Listo
        $tenant->update(['status' => 'active']);
    }

    public function failed(Throwable $e): void  // si fallan los 3 intentos
    {
        Tenant::whereKey($this->tenantId)->update(['status' => 'failed']);
    }
}
```

**4. El panel consulta el estado** (`GET /api/admin/tenants/{id}`) y muestra `provisioning`, `active` o `failed`. Si quedó en `failed`, el botón "reintentar" vuelve a encolar el mismo job.

> **Por qué se puede reintentar sin romper nada (idempotencia).** `CREATE DATABASE IF NOT EXISTS` no falla si la base ya existe, y `migrate` solo corre las migraciones que faltan (pieza 3 de la sección 4). Así, un alta que se cortó a mitad de camino se completa al reintentar, sin duplicar nada.

### Dos usuarios de MySQL

| Usuario | Permisos | Lo usa |
| --- | --- | --- |
| Operación | Leer y escribir en `sportiva_platform` y en las bases `club_*` | La API, en cada request |
| Provisión | `CREATE DATABASE`, y lo mínimo para migrar la base nueva | Solo el job de alta de clubes |

Con esta separación, aunque hubiera una inyección SQL en un endpoint, el usuario de la API no podría crear ni borrar bases. Hoy, en local, el usuario `sportiva` **no** tiene permiso para crear bases `club_*`: se le da en el change que implementa el alta.

### Lo que falta hoy: el worker de la cola

> ⚠️ **✅ EXISTE HOY: `compose.yaml` no tiene ningún worker.** Solo están los servicios `backend` y `mysql`, y nadie corre `queue:work`. Si hoy se encolara un job, quedaría en la tabla `jobs` para siempre y el club, en `provisioning` sin avanzar.

Se resuelve en el change que implementa el alta:

| Entorno | Solución (🟡 PLANIFICADO) |
| --- | --- |
| Local | Un tercer servicio, `queue`, en `compose.yaml`: usa la misma imagen y el mismo código que `backend`, pero corre `php artisan queue:work`. Se levanta solo con `docker compose up -d` |
| Tests | `QUEUE_CONNECTION=sync`: el job se ejecuta en el momento, sin worker, para poder testear el alta de punta a punta |
| Laravel Cloud | No se sube Docker: se configura en el panel. Lo recomendado es una **managed queue** (Laravel Cloud define `QUEUE_CONNECTION=cloud`, y requiere `aws/aws-sdk-php`). En Starter: 1 cola, hasta 3 workers y **90 s como máximo por job**. Detalle en la [guía de jobs](guia-jobs.md#producción-laravel-cloud) |

**Ojo con el worker local:** carga el código una sola vez, al arrancar. Si cambiás el código de un job, reinicialo con `docker compose restart queue`. Para ver qué está procesando: `docker compose logs -f queue`.

## 8. Qué pasa en un request del día a día

✅ **EXISTE HOY**, salvo el paso **e** (permisos del rol), que llega con `club-access-control`. Es el diseño del ADR 0001 del front y de `docs/integracion-api-laravel.md` (§3 y §5).

```
1. POST /api/auth/login            (email + contraseña, contra la base CENTRAL)
       → token "pre-tenant": solo sirve para elegir club
2. POST /api/auth/select-tenant    (tenant = river)
       → la API verifica la membresía en tenant_memberships
       → emite un token ATADO a ese club (tenant_id dentro del token)
3. GET  /api/players             (con el token del club)
       middleware:
         a. token válido y no vencido                  (si no → 401)
         b. el token tiene tenant_id                   (si no → 403 tenant_required)
         c. usuario, club y membresía siguen activos   (si no → 403)
         d. TenantDatabase::connect: valida tenants.database_name y apunta
            la conexión "tenant" (al terminar el request, la desconecta)
         e. permisos del rol en ese club               (si no → 403 forbidden)
       controller: Player::all()   → consulta club_river.players
```

> ⚠️ **El club nunca lo manda el cliente**: sale del token. Nunca se acepta `tenant_id` ni el slug del club en el body o en la URL como fuente de autorización. Así, nadie puede pedir datos de otro club cambiando un parámetro.

**Jobs que trabajan sobre un club.** La cola es una sola, en la base central. Un job que trabaja sobre un club recibe el `tenantId` y hace su trabajo dentro de `TenantDatabase::run`, que apunta la conexión y la desconecta al terminar. Ver [guia-jobs.md](guia-jobs.md#7-jobs-que-trabajan-sobre-un-club).

## 9. Panel de administración de plataforma

🟡 **PLANIFICADO.** Lo decidió el equipo: las pantallas van en el front (páginas `/admin` de Front-Sportiva, a través del BFF). La API expone los endpoints `/api/admin/*` y sigue sin interfaz propia.

### Dos niveles de administrador

| Rol | Dónde vive | Qué puede hacer |
| --- | --- | --- |
| Admin de plataforma (el equipo de Sportiva) | Base central (`users.is_platform_admin`) | Crear usuarios y clubes (con su base), asociar usuarios a clubes con un rol y suspender clubes. No necesita elegir club: su token es de plataforma |
| Admin de un club (alguien del club) | Base del club (roles y permisos del club) | Gestionar jugadores, partidos y demás, solo de su club |

### Endpoints previstos

| Endpoint | Para qué |
| --- | --- |
| `POST/GET/PATCH /api/admin/users` | Crear, listar y editar usuarios de la plataforma |
| `POST /api/admin/tenants` | Crear un club: dispara el alta de su base (el job) y responde `provisioning` |
| `GET /api/admin/tenants[/{id}]` | Listar los clubes y ver su estado: `provisioning`, `active` o `failed` |
| `POST /api/admin/tenants/{id}/retry` | Reintentar un alta que falló |
| `POST /api/admin/tenants/{id}/memberships` | Asociar un usuario a un club, con un rol |
| `PATCH /api/admin/tenants/{id}/memberships/{m}` | Cambiar el rol o desactivar la membresía |

### Reglas

- Solo los puede usar un usuario con `is_platform_admin`.
- **El primer admin se crea por consola** (`php artisan platform:create-admin`): no existe un registro público de administradores.
- Cada acción queda en una tabla de **auditoría** de la base central, con quién creó o asoció qué, y cuándo.

### El flujo completo desde el panel

1. El admin crea el club "River". La API lo guarda como `provisioning` y encola el alta.
2. El job crea `club_river`, corre las migraciones de `tenant/` solo ahí, carga los roles iniciales y lo pasa a `active`.
3. El panel muestra el estado, o el error con el botón para reintentar.
4. El admin asocia usuarios al club con un rol (por ejemplo, admin del club).
5. Esos usuarios eligen "River" al entrar y trabajan dentro de `club_river`.

## 10. Reglas para escribir migraciones

| Regla | Por qué |
| --- | --- |
| Elegí la carpeta correcta: la central o `tenant/` | La carpeta define a qué bases se aplica la migración. Una tabla de club en la carpeta central quedaría en `sportiva_platform`, y ningún club la tendría |
| Las migraciones de club tienen que ser **compatibles hacia atrás** | Si `tenants:migrate` falla a mitad de camino, unos clubes quedan con el esquema nuevo y otros con el viejo, y el código tiene que funcionar con los dos. Primero se agrega lo nuevo, y recién en un deploy posterior se quita lo viejo |
| Nunca edites una migración que ya está mergeada | Ya corrió en las bases de otros devs y de los clubes, así que tu cambio no se aplicaría. En su lugar, creá una migración nueva |
| Nada de `USE <base>` ni de nombres de base fijos en el código | La base del club la decide el middleware o el loop |
| Los modelos de club declaran `$connection = 'tenant'` | Si no la declaran, consultan la base central |
| Nombres de tablas, modelos, rutas y campos **en inglés** (`players`, `Player`, `birth_date`) | Es la decisión del equipo (ver [decisions.md](decisions.md)): coherente con los enums y con la pluralización de Laravel. La UI la traduce el front |
| Si cambiás un endpoint, regenerá el contrato | Corré `php artisan scramble:export` y commiteá `docs/api/openapi.json`: si quedó desactualizado, la CI falla |

## 11. Comandos

Todos se corren desde la raíz del repo, **dentro de WSL** (`~/repos/api-sportiva`), en la terminal de Ubuntu o en la de VS Code en modo WSL.

### ✅ Existen hoy

| Para qué | Comando |
| --- | --- |
| Setup completo, o "no sé qué cambió" (es idempotente y no borra datos) | `bash scripts/setup.sh` |
| Levantar, ver el estado, ver los logs | `docker compose up -d` · `docker compose ps` · `docker compose logs -f backend` |
| Migrar la base central | `docker compose exec backend php artisan migrate` |
| Ver qué migraciones corrieron | `docker compose exec backend php artisan migrate:status` |
| Crear una migración central | `docker compose exec backend php artisan make:migration create_x_table` |
| Correr los tests (contra `sportiva_testing`) | `docker compose exec backend php artisan test` |
| Lint (estilo de código) | `docker compose exec backend vendor/bin/pint --test` |
| Regenerar el contrato OpenAPI | `docker compose exec backend php artisan scramble:export` |
| Ver la documentación (Swagger) | `http://127.0.0.1:8000/api/docs` |
| Limpiar las cachés si una ruta no aparece | `docker compose exec backend php artisan optimize:clear` |
| Crear una migración de club | `docker compose exec backend php artisan make:migration create_x_table --path=database/migrations/tenant` |
| Crear el primer admin de plataforma | `docker compose exec backend php artisan platform:create-admin` |

### 🟡 Planificados (todavía no existen)

| Para qué | Comando |
| --- | --- |
| Migrar todas las bases de clubes | `php artisan tenants:migrate` |
| Dar de alta un club por consola | `php artisan tenants:create river "Club River"` |
| Ver o reiniciar el worker de la cola | `docker compose logs -f queue` · `docker compose restart queue` |
| Ver o reintentar los jobs fallidos | `php artisan queue:failed` · `php artisan queue:retry all` |

Los comandos planificados de `php artisan` también se van a correr anteponiendo `docker compose exec backend`.

### Al cambiar de rama

| Si en la rama cambió... | Qué correr |
| --- | --- |
| Solo código PHP (rutas, controllers, Requests, Resources) | Nada: recargá `/api/docs`. El código está montado en el contenedor y Scramble regenera el contrato en cada visita |
| `composer.json` o `composer.lock` | `docker compose exec backend composer install` |
| Migraciones nuevas | `docker compose exec backend php artisan migrate` (y, cuando exista, `tenants:migrate`) |
| El `.env` | `docker compose restart backend` |
| `docker/`, `compose.yaml` o el Dockerfile | `bash scripts/setup.sh` |

> ⚠️ **Nunca uses `docker compose down -v` para "reiniciar".** La `-v` borra el volumen de MySQL, con todas las bases locales (la central y las de los clubes). Para bajar el entorno alcanza con `docker compose down`.

### Ver las bases con DBeaver

MySQL no publica puertos por defecto. Activá el acceso en el clon que estés usando (ver [desarrollo-local.md](desarrollo-local.md#acceso-a-mysql-desde-el-host)):

```bash
cp compose.override.example.yaml compose.override.yaml    # una vez por clon
docker compose up -d
docker compose ps        # mysql: 127.0.0.1:3310->3306/tcp
```

Configuración en DBeaver:

- Host: `127.0.0.1`.
- Puerto: **3310** (no 3306).
- Usuario y contraseña: los de `DB_USERNAME` y `DB_PASSWORD` en el `.env`.
- En *Driver properties*: `allowPublicKeyRetrieval=true` y `useSSL=false` (solo para la base local).

## 12. Preguntas frecuentes

**¿Hay una carpeta de migraciones por club (`database/migrations/river`)?**
No. Hay una sola carpeta, `tenant/`, para todos los clubes. Ver la sección 5.

**¿Cómo sabe una migración de club a qué base aplicarse?**
No lo sabe: se aplica en la base a la que esté apuntada la conexión `tenant` en ese momento. El loop de `tenants:migrate` (o el alta de un club) la apunta a cada base, una por una.

**¿Hace falta otro repo o un microservicio para la autenticación?**
No. Es una sola API, con dos conexiones. Ver la sección 2.

**¿Qué crea un club? ¿Qué job?**
El job `ProvisionTenant`. Lo encola el panel (`POST /api/admin/tenants`) o la consola (`tenants:create`), y lo ejecuta el worker de la cola. Hoy no existen ni el job ni el worker. Ver la sección 7.

**¿Dónde vive la cola de jobs, en la central o en cada club?**
En la central, en la tabla `jobs`. Los jobs que trabajan sobre un club llevan el `tenant_id` y apuntan la conexión al ejecutarse.

**¿Tengo que bajar y subir Docker para ver un endpoint nuevo en Swagger?**
No: alcanza con recargar `/api/docs`. Ver la tabla "Al cambiar de rama" de la sección 11.

**¿Un club puede tener una tabla que los otros no tengan?**
No: todos los clubes comparten el mismo esquema. Lo que cambia entre clubes se resuelve con datos (configuración o módulos que se activan).

**¿Se puede probar todo esto en local?**
Sí, cuando esté implementado: los clubes van a ser bases dentro del mismo contenedor `mysql`. Para eso hace falta darle al usuario local permiso para crear bases `club_*`, lo que se hace en el change del alta de clubes.

## 13. Qué falta y en qué orden

| # | Trabajo | Qué incluye |
| --- | --- | --- |
| 1 | ✅ ADR de multi-tenancy | [ADR 0002](adr/0002-multi-tenancy-implementacion-propia.md): implementación propia. Los usuarios de MySQL de operación y de provisión los decide 3b |
| 2 | ✅ Change `platform-auth` | Las tablas `tenants` y `tenant_memberships`, el campo `is_platform_admin`, el `tenant_id` en los tokens, Sanctum (login, selección de club, `me` y logout) y el comando `platform:create-admin` |
| 3a | ✅ Change [`tenant-connection`](../openspec/changes/tenant-connection/proposal.md) | La conexión `tenant` y el cambio de base en el middleware y en los jobs; el servicio `TenantDatabase` (crear, migrar y conectar bases de club); los permisos de MySQL en local para `club_%`; el soporte de tests con dos clubes; el ADR 0002 |
| 3b | Change `platform-admin-and-provisioning` | Los endpoints `/api/admin/*` y la auditoría; el job `ProvisionTenant`; el servicio `queue` (worker) en Compose; la conexión `provisioning`; los comandos `tenants:create` y `tenants:migrate`, que usan `TenantDatabase` de 3a. Además, se **confirma la [guía de ABM](guia-abm.md)** (hoy es una propuesta), con el ABM de usuarios y clubes como ejemplo de referencia |
| 4 | Front-Sportiva | El login, el selector de club y las páginas `/admin`, contra estos endpoints |
| 5 | Módulos del club | Jugadores, partidos, pedidos..., con sus migraciones en `tenant/` |

Cada change pasa por OpenSpec: propuesta, revisión, aprobación, implementación, validación y archivo.
