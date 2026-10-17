# Guía para devs: jobs, colas y tareas programadas

Esta guía explica qué es un job, cuándo usar uno, cómo se crea, se despacha, se reintenta y se prueba, cómo se escriben los jobs que trabajan sobre un club y cómo funcionan el worker y las tareas programadas.

> **🟡 PROPUESTO.** Estas convenciones se adelantaron para que el equipo arranque con las mismas reglas. Se confirman con el **primer job real**: `ProvisionTenant`, el alta de clubes del change `platform-admin-and-provisioning`. Si algo choca con ese código, gana el código, y esta guía se corrige.
>
> Versión: 17 de octubre de 2026 · Laravel 13.37. Documentos relacionados: [guia-bases-de-datos.md](guia-bases-de-datos.md) (sección 7, el alta de clubes) y [guia-abm.md](guia-abm.md).

## Índice

1. [Qué es un job y cuándo usar uno](#1-qué-es-un-job-y-cuándo-usar-uno)
2. [Situación actual en main](#2-situación-actual-en-main)
3. [Las piezas: job, cola, worker, fallidos](#3-las-piezas-job-cola-worker-fallidos)
4. [Crear un job](#4-crear-un-job)
5. [Anatomía de un job](#5-anatomía-de-un-job)
6. [Las reglas de oro](#6-las-reglas-de-oro)
7. [Jobs que trabajan sobre un club](#7-jobs-que-trabajan-sobre-un-club)
8. [Despachar un job](#8-despachar-un-job)
9. [Colas con nombre y prioridades](#9-colas-con-nombre-y-prioridades)
10. [Errores, reintentos y jobs fallidos](#10-errores-reintentos-y-jobs-fallidos)
11. [Tareas programadas (scheduler)](#11-tareas-programadas-scheduler)
12. [El worker en local y en producción](#12-el-worker-en-local-y-en-producción)
13. [Tests](#13-tests)
14. [Paso a paso con comandos (checklist)](#14-paso-a-paso-con-comandos-checklist)
15. [Errores comunes](#15-errores-comunes)

---

## 1. Qué es un job y cuándo usar uno

Un **job** es una tarea que se guarda en una **cola** para que la ejecute **después** un proceso aparte (el **worker**), en lugar de hacerla dentro del request mientras el usuario espera.

```
  API (request)                 cola (tabla "jobs")              worker (proceso aparte)
  ─────────────                 ───────────────────              ───────────────────────
  "hacé X"               ──>    [ MiJob(datos) ]           ──>   lo toma y lo ejecuta
  responde ENSEGUIDA
```

| Usá un job cuando... | Ejemplos | No hace falta cuando... |
| --- | --- | --- |
| La tarea tarda más de un segundo | Crear la base de un club, generar un reporte, importar un archivo | Es una consulta o una escritura simple: un ABM normal |
| Depende de un servicio externo que puede fallar o tardar | Mandar mails o notificaciones, llamar a una API externa | El usuario necesita el resultado en la misma respuesta |
| Hay que reintentar si falla | Una notificación que no salió | Es parte de una transacción que tiene que ser todo o nada |
| Hay que repetirla cada cierto tiempo | Limpiar tokens vencidos, recalcular estadísticas | |

Cuando el endpoint dispara un job, responde **202 Accepted** (aceptado, en proceso) y el cliente consulta el estado después. Es lo que hace el alta de un club.

## 2. Situación actual en main

| Pieza | Estado |
| --- | --- |
| La cola en la base central (`QUEUE_CONNECTION=database`) | ✅ EXISTE HOY |
| Las tablas `jobs`, `job_batches` y `failed_jobs` | ✅ EXISTEN HOY (migración `0001_01_01_000002_create_jobs_table.php`) |
| Los tests usan `QUEUE_CONNECTION=sync` (en `phpunit.xml`) | ✅ EXISTE HOY |
| Algún job (`app/Jobs/`) | ❌ No hay ninguno |
| **Un worker** (`queue:work`) en `compose.yaml` | ❌ **No existe**: un job encolado hoy quedaría en la tabla para siempre |
| El scheduler (`schedule:work`) y alguna tarea programada | ❌ No existe |
| `retry_after` de la cola | 90 s (en `config/queue.php`) |

El worker y el scheduler se agregan en el change `platform-admin-and-provisioning` (sección 12).

## 3. Las piezas: job, cola, worker, fallidos

| Pieza | Qué es | Dónde está |
| --- | --- | --- |
| **Job** | Una clase con el método `handle()`, que contiene lo que hay que hacer | `app/Jobs/...` |
| **Cola** | La lista de jobs pendientes | La tabla `jobs` de la base central |
| **Worker** | El proceso que corre `php artisan queue:work` todo el tiempo: toma un job, lo ejecuta, toma el siguiente | El servicio `queue` en Compose (🟡) y un worker en Laravel Cloud |
| **Fallidos** | Los jobs que agotaron sus reintentos | La tabla `failed_jobs`, para revisarlos y reintentarlos |

Cuando un job se encola, se guarda **serializado** en la tabla `jobs`: su clase y los datos del constructor. El worker lo lee, reconstruye el objeto y llama a `handle()`.

## 4. Crear un job

```bash
docker compose exec backend php artisan make:job Club/RecalculatePlayerStats
docker compose exec backend php artisan make:job Platform/ProvisionTenant
```

Organización por área, igual que los ABM:

```
app/Jobs/
├── Platform/ProvisionTenant.php           ← trabaja sobre la base central
└── Club/RecalculatePlayerStats.php        ← trabaja sobre la base de UN club (sección 7)
```

Opciones útiles de `make:job`: `--batched`, para jobs que van en un lote (sección 8); `--test`, que genera también el test; y `--sync`, para un job que se ejecuta sin cola, casi nunca necesario.

## 5. Anatomía de un job

```php
namespace App\Jobs\Platform;

class ProvisionTenant implements ShouldQueue        // ShouldQueue = va a la cola
{
    use Queueable;

    public int $tries = 3;                          // intentos totales antes de darlo por fallido
    public array $backoff = [10, 60];               // espera entre reintentos: 10 s y después 60 s
    public int $timeout = 60;                       // segundos máximos por intento (MENOR que retry_after = 90)

    public function __construct(public int $tenantId) {}   // datos mínimos: IDs, no modelos ni secretos

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        // ... el trabajo, idempotente (sección 6)
    }

    public function failed(Throwable $e): void      // se llama cuando se agotan los intentos
    {
        Tenant::whereKey($this->tenantId)->update(['status' => 'failed']);
        Log::error('Fallo el alta del club', ['tenant_id' => $this->tenantId, 'error' => $e->getMessage()]);
    }
}
```

| Propiedad o método | Para qué |
| --- | --- |
| `$tries` | Cuántas veces se intenta en total. Por defecto en este proyecto: **3** |
| `$backoff` | Cuánto esperar antes de cada reintento, para no golpear de nuevo enseguida a algo que falló |
| `$timeout` | Corta un intento que se colgó. **Tiene que ser menor que `retry_after` (90 s)**: si no, otro worker puede tomar el mismo job mientras el primero sigue corriendo |
| `failed()` | Lo que se hace cuando ya no hay más intentos: marcar el estado, avisar, loguear |
| `ShouldBeUnique` | Interfaz que evita encolar dos veces el mismo job (por ejemplo, dos altas del mismo club). Se define con `uniqueId()` |
| `middleware()` | Middlewares de job: `WithoutOverlapping` (no correr dos a la vez para el mismo recurso), `RateLimited` y `ThrottlesExceptions` |

## 6. Las reglas de oro

1. **Idempotente.** Un job puede ejecutarse **más de una vez**: por un reintento, o porque un worker se cortó a mitad de camino. Correrlo dos veces tiene que dejar el mismo resultado que correrlo una. Por ejemplo, `CREATE DATABASE IF NOT EXISTS`, `updateOrCreate` en lugar de `create`, o verificar el estado antes de actuar.
2. **Pasá IDs, no modelos.** El constructor recibe `int $tenantId`, no `Tenant $tenant`. Es obligatorio en los jobs de club (sección 7) y es buena práctica en todos: el job lee el dato fresco cuando corre.
3. **Nada de secretos en el payload.** El job se guarda en la tabla `jobs` **en texto plano**. Nunca le pases contraseñas, tokens ni datos personales sensibles: pasale un ID y que el job busque lo que necesita.
4. **Chico y con un solo propósito.** Si un proceso tiene muchos pasos independientes, dividilo en varios jobs (una cadena o un lote, sección 8).
5. **`timeout` menor que `retry_after`** (90 s). Si un job necesita más tiempo, se sube `retry_after` para esa cola, a conciencia.
6. **Despachá después del commit.** Si el job se despacha dentro de una transacción, usá `->afterCommit()`. Si no, el worker puede tomarlo antes de que los datos existan (sección 8).
7. **Logueá lo importante,** con contexto (`tenant_id` e IDs), y sin datos sensibles.

## 7. Jobs que trabajan sobre un club

🟡 **PLANIFICADO.** Se implementa junto con la conexión `tenant` (ver [guia-bases-de-datos.md](guia-bases-de-datos.md)).

**El problema.** La cola es una sola, en la base central. Cuando el worker toma un job de club, la conexión `tenant` **no apunta a ninguna base**: eso lo hace el middleware HTTP en cada request, y en el worker no hay request.

**La trampa de pasar modelos de club.** Si un job recibe un modelo (`Player $player`), Laravel guarda el **nombre de su conexión** (`tenant`) y **lo vuelve a buscar al deserializar el job**, *antes* de llamar a `handle()`. Está verificado en `SerializesAndRestoresModelIdentifiers`. En ese momento la conexión `tenant` está vacía, así que el job falla o lee de la base equivocada.

Pasa **por defecto**: el trait `Queueable` que genera `make:job` incluye `SerializesModels`.

**La regla:** un job de club recibe el **`tenantId` y los IDs** que necesita, y apunta la conexión **antes** de tocar datos del club. Un trait común lo resuelve:

```php
// 🟡 ilustrativo: se define en el change de la conexión tenant
trait RunsInTenant
{
    public int $tenantId;

    protected function useTenant(): Tenant
    {
        $tenant = Tenant::where('status', 'active')->findOrFail($this->tenantId);
        config(['database.connections.tenant.database' => $tenant->database_name]);
        DB::purge('tenant');

        return $tenant;
    }
}

class RecalculatePlayerStats implements ShouldQueue
{
    use Queueable, RunsInTenant;

    public function __construct(int $tenantId, public int $playerId)   // IDs, NO el modelo Player
    {
        $this->tenantId = $tenantId;
    }

    public function handle(): void
    {
        $this->useTenant();                              // PRIMERO apuntar la conexión
        $player = Player::findOrFail($this->playerId);   // recién ahora, datos del club
        // ...
    }
}
```

Quién lo despacha, desde un request de club, toma el club del **contexto autenticado**, nunca del body:

```php
RecalculatePlayerStats::dispatch($currentTenant->id, $player->id);
```

> El worker procesa jobs de distintos clubes uno detrás del otro, así que cada job apunta su propia conexión al empezar. Nunca asumas que la conexión `tenant` "ya quedó" apuntada por el job anterior.

## 8. Despachar un job

```php
ProvisionTenant::dispatch($tenant->id);                        // a la cola, cuanto antes

ProvisionTenant::dispatch($tenant->id)->afterCommit();         // recién cuando se confirme la transacción

SendMatchReminder::dispatch($tenantId, $matchId)
    ->delay(now()->addMinutes(10));                            // dentro de 10 minutos (máximo 15 en producción)

ProvisionTenant::dispatch($tenant->id)->onQueue('provisioning');   // en una cola con nombre (sección 9)
```

> ⚠️ **Demoras largas.** En producción (managed queues de Laravel Cloud, sección 12), una cola estándar admite **como máximo 15 minutos** de demora. Para algo que tiene que pasar "en 2 horas" o "mañana a las 9", no uses `delay()`: guardá la fecha en la base y que una **tarea programada** (sección 11) despache los jobs cuando llegue el momento.

**Por qué `afterCommit()`.** Si dentro de una transacción creás el club y despachás el job, el worker puede tomarlo **antes** del commit: no encuentra el club y falla. Con `afterCommit()`, el job se encola recién cuando la transacción se confirma (y no se encola si hace rollback). En `config/queue.php`, `after_commit` está en `false`, así que hay que pedirlo en cada caso.

**Cadenas: uno después del otro, y si uno falla, se cortan los siguientes:**

```php
Bus::chain([
    new CreateTenantDatabase($tenantId),
    new MigrateTenantDatabase($tenantId),
    new SeedTenantRoles($tenantId),
])->dispatch();
```

**Lotes: muchos en paralelo, con seguimiento del progreso** (necesitan `--batched` al crearlos y usan la tabla `job_batches`):

```php
Bus::batch($tenantIds->map(fn ($id) => new MigrateTenantDatabase($id)))
    ->name('Migrar todos los clubes')
    ->dispatch();
```

## 9. Colas con nombre y prioridades

Por defecto, todo va a la cola `default`. Conviene separar lo pesado para que no frene lo rápido:

| Cola | Para qué |
| --- | --- |
| `default` | Lo normal y rápido |
| `provisioning` | Las altas de clubes y las migraciones por club (lentas) |
| `notifications` | Mails y notificaciones (dependen de servicios externos) |

El worker atiende las colas **en el orden indicado**: primero vacía la primera, después la segunda, y así:

```bash
php artisan queue:work --queue=default,notifications,provisioning
```

Con un solo worker alcanza al principio. Si las altas empiezan a demorar el resto, se agrega un worker dedicado a `provisioning`.

> ⚠️ **En producción, con el plan Starter, hay UNA sola cola.** Las managed queues de Laravel Cloud permiten 1 cola por entorno en Starter (10 en Growth). Hasta que cambie el plan, **todo va a la cola por defecto**: no uses `->onQueue(...)` en el código. Separar colas queda como opción para un plan superior. En local da igual, porque el worker atiende la cola `default`.

## 10. Errores, reintentos y jobs fallidos

> ⚠️ **Los comandos de esta sección son para local** (cola `database`). En producción, con managed queues, `queue:failed`, `queue:retry`, `queue:clear` y `queue:pause` **no funcionan**: los fallidos se ven, reintentan y borran desde el dashboard **Queues** de Laravel Cloud (pestaña *Monitoring*) o con su API.

**Qué pasa cuando un intento falla (lanza una excepción):**

1. Si quedan intentos (`$tries`), se reencola, esperando lo que diga `$backoff`.
2. Si no quedan, se llama a `failed()` y el job pasa a la tabla `failed_jobs`, con la excepción completa.

Para lanzar un error que **no** se reintenta (por ejemplo, un dato inválido que no se va a arreglar solo), usá `$this->fail('motivo')` dentro de `handle()`.

**Comandos para los fallidos** (con `docker compose exec backend` adelante):

| Para qué | Comando |
| --- | --- |
| Ver los jobs fallidos | `php artisan queue:failed` |
| Reintentar uno o todos | `php artisan queue:retry <id>` · `php artisan queue:retry all` |
| Borrar uno o todos | `php artisan queue:forget <id>` · `php artisan queue:flush` |
| Borrar los fallidos de más de 48 h | `php artisan queue:prune-failed --hours=48` |
| Vaciar la cola pendiente (⚠️ destructivo) | `php artisan queue:clear` |
| Pausar o reanudar una cola | `php artisan queue:pause default` · `php artisan queue:resume default` |

## 11. Tareas programadas (scheduler)

Las tareas que se repiten cada cierto tiempo se declaran en **`routes/console.php`**:

```php
// 🟡 ilustrativo
use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=24')->daily();          // limpiar tokens vencidos
Schedule::job(new RecalculateAllStats)->dailyAt('03:00')->onOneServer();  // un job, una vez por día
```

- El scheduler necesita un proceso que corra `php artisan schedule:work`, o `schedule:run` cada minuto. Hoy **no existe** en local: se agrega junto con el worker (sección 12). En Laravel Cloud es un *toggle* (sección 12).
- **En producción, la lista de tareas se toma en cada deploy:** un cambio en `routes/console.php` recién vale después del siguiente deploy.
- **No programes tareas más seguido que el tiempo de *Scale to Zero*** del entorno. Si una tarea corre cada 5 minutos y la app se duerme a los 5 minutos de inactividad, nunca llega a dormirse, y eso cuesta.
- `onOneServer()` evita que la tarea corra dos veces si hay más de un servidor.
- Las tareas pesadas conviene que **despachen un job** en lugar de hacer el trabajo ahí mismo.

Para ver y probar:

```bash
php artisan schedule:list                 # qué tareas hay y cuándo corren
php artisan schedule:test                 # ejecutar una a mano, para probarla
```

## 12. El worker en local y en producción

🟡 **PLANIFICADO** (change `platform-admin-and-provisioning`).

**Local:** dos servicios más en `compose.yaml`, con la misma imagen y el mismo código montado que `backend`:

```yaml
# 🟡 ilustrativo
  queue:
    image: api-sportiva-dev:local
    command: php artisan queue:work --tries=3 --timeout=60      # una sola cola (default), como en producción con Starter
    volumes: [".:/var/www/html"]
    depends_on: { mysql: { condition: service_healthy } }

  scheduler:
    image: api-sportiva-dev:local
    command: php artisan schedule:work
    volumes: [".:/var/www/html"]
    depends_on: { mysql: { condition: service_healthy } }
```

| Situación | Qué hacer |
| --- | --- |
| Ver qué está procesando | `docker compose logs -f queue` |
| **Cambiaste el código de un job** | `docker compose restart queue`: el worker carga el código **una sola vez** al arrancar, y si no lo reiniciás sigue corriendo la versión vieja |
| Ver los fallidos | `docker compose exec backend php artisan queue:failed` |
| Probar un job sin el worker | En un test, con `QUEUE_CONNECTION=sync` (sección 13) |

### Producción: Laravel Cloud

En Laravel Cloud **no se sube nada de Docker**: compila desde Git, y la cola y el scheduler se configuran en su panel. Verificado en la documentación oficial ([colas](https://laravel.com/cloud/docs/queues), [cómputo](https://laravel.com/cloud/docs/compute) y [tareas programadas](https://laravel.com/cloud/docs/scheduled-tasks)), 2026-10-17.

| | **Managed queues** (recomendado) | Worker propio (`queue:work`) |
| --- | --- | --- |
| Qué es | Laravel Cloud opera la cola (sobre SQS) y los workers, que escalan solos | Un proceso `queue:work` en un *Worker cluster* o en el *App cluster* |
| Cómo se activa | Panel: **Add compute → Managed queue** → deploy | Panel: crear el Worker cluster con el comando |
| Conexión | Laravel Cloud define `QUEUE_CONNECTION=cloud` | La que se elija (por ejemplo, `database`) |
| Con *Scale to Zero* | **Sigue procesando** aunque la app duerma | **No procesa** mientras la app duerme, salvo que el worker quede siempre despierto |
| Requisitos | Laravel ≥ 13.19 (tenemos 13.37) y `composer require aws/aws-sdk-php` | Ninguno |
| Costo | Por segundo de worker, más US$1 por millón de operaciones, más aproximadamente US$0,13 por mes por cola | El cómputo del worker mientras corre |

**Límites del plan Starter, con managed queues:**

- **1 cola** por entorno y **hasta 3 workers**.
- Workers Flex: **un job puede durar como máximo 90 s**. El `$timeout` de cada job tiene que quedar por debajo de eso. Para jobs más largos hace falta un worker Pro, que no está disponible en Starter.
- Demora máxima de un job: **15 minutos**.
- La entrega es **"al menos una vez"**: un job puede llegar dos veces, así que tiene que ser idempotente (sección 6).

**Scheduler:** en el *App cluster* se activa el toggle **Scheduler** y se hace deploy. Laravel Cloud corre `schedule:run` cada minuto y, con *Scale to Zero*, despierta la app sola para las tareas programadas.

**El código no cambia entre local y producción:** los jobs se despachan sin indicar una conexión (`ProvisionTenant::dispatch(...)`). En local van a la cola `database` y los procesa el servicio `queue`; en producción, Laravel Cloud define `QUEUE_CONNECTION=cloud` y van a la managed queue.

🟡 **Pendiente para el change de deploy:**
- decidir entre managed queue y worker propio (la recomendación es managed queue);
- agregar `aws/aws-sdk-php` si se elige managed queue;
- activar el scheduler;
- confirmar que el alta de un club entra en 90 s.

## 13. Tests

En los tests, la cola es `sync` (`phpunit.xml`): un job despachado **se ejecuta en el momento**. Hay dos tipos de test.

**1. Que el endpoint despache el job, sin ejecutarlo:**

```php
public function test_creating_a_tenant_dispatches_provisioning(): void
{
    Queue::fake();                                       // los jobs NO se ejecutan, solo se registran

    $this->actingAsPlatformAdmin()                       // 🟡 helper ilustrativo
         ->postJson('/api/admin/tenants', ['slug' => 'river', 'name' => 'Club River'])
         ->assertAccepted();                             // 202

    Queue::assertPushed(ProvisionTenant::class, fn ($job) => $job->tenantId === Tenant::first()->id);
}
```

**2. Que el job haga lo que tiene que hacer**, ejecutando `handle()` directamente:

```php
public function test_provisioning_is_idempotent(): void
{
    $tenant = Tenant::factory()->create(['status' => 'provisioning']);

    (new ProvisionTenant($tenant->id))->handle();
    (new ProvisionTenant($tenant->id))->handle();        // segunda vez: no tiene que romper ni duplicar

    $this->assertSame('active', $tenant->fresh()->status);
}
```

Casos mínimos para cada job:

| # | Caso |
| --- | --- |
| 1 | El camino feliz: el resultado esperado |
| 2 | **Idempotencia**: correrlo dos veces deja el mismo resultado |
| 3 | `failed()`: cuando falla, el estado queda bien (por ejemplo, `status = failed`) |
| 4 | El endpoint o el comando que lo dispara lo encola con los datos correctos (`Queue::fake()`) |
| 5 | Para los jobs de club: trabaja sobre la base del club **correcto** |

## 14. Paso a paso con comandos (checklist)

1. **Decidí si hace falta un job** (sección 1).
2. **Crealo:** `php artisan make:job Club/<Nombre>` o `Platform/<Nombre>`.
3. **Constructor:** solo IDs. Si es de club, `tenantId` + `RunsInTenant` (sección 7).
4. **`handle()` idempotente**, con `$tries`, `$backoff` y un `$timeout` menor a 90.
5. **`failed()`**: qué estado queda y qué se loguea.
6. **Despachalo** desde el controller, la Action o el comando. Usá `->afterCommit()` si estás dentro de una transacción. **No uses `->onQueue(...)`**: en Starter hay una sola cola (sección 9). Tampoco uses una demora (`delay`) de más de 15 minutos (sección 8).
7. **Tests:** los 5 casos de la sección 13.
8. **Probalo en local:** con el worker levantado (`docker compose logs -f queue`). Si cambiaste el código del job, `docker compose restart queue`.
9. **PR:** la CI tiene que pasar y un compañero tiene que aprobarlo.

## 15. Errores comunes

| Error | Qué pasa | Cómo evitarlo |
| --- | --- | --- |
| No hay worker corriendo | El job queda en la tabla `jobs` para siempre | `docker compose ps` tiene que mostrar el servicio `queue` (🟡 cuando exista) |
| Cambiaste el código y el worker no lo toma | Sigue corriendo la versión vieja | `docker compose restart queue` |
| Pasarle un modelo de club al constructor | Al deserializar busca en la conexión `tenant` vacía: error o base equivocada | Pasá IDs y apuntá la conexión en `handle()` (sección 7) |
| Despachar dentro de una transacción sin `afterCommit()` | El worker no encuentra los datos todavía | `->afterCommit()` |
| `$timeout` mayor o igual que `retry_after` | El mismo job corre dos veces en paralelo | `$timeout` < 90, o subir `retry_after` |
| Un job que no es idempotente | Un reintento duplica datos (dos mails, dos registros) | Diseñarlo para correr N veces (sección 6) |
| Secretos o datos personales en el constructor | Quedan en texto plano en la tabla `jobs` y en `failed_jobs` | Pasar IDs y buscar los datos en `handle()` |
| Asumir que `tenant` ya apunta al club del job anterior | Datos de un club escritos en otro | Cada job de club llama a `useTenant()` al empezar |
