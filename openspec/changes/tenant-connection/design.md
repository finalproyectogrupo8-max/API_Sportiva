# Design: conexión a la base de cada club

## Context

- `EnsureTenantContext` (alias `tenant.token`) ya valida el `tenant_id` del token, la membresía activa y que el club esté `active`, y deja el `Tenant` en `$request->attributes`. No toca ninguna conexión.
- `config/database.php` tiene solo la conexión `mysql`, que apunta a la base central por `DB_*`.
- `tenants.database_name` lo deriva el servidor como `club_<slug>`, y el `slug` valida `^[a-z0-9_]+$` con un máximo de 50 caracteres (change `platform-auth`).
- En MySQL local, el usuario de la app solo tiene permisos sobre `sportiva_platform` y `sportiva_testing` (`docker/mysql/init/01-testing-db.sh`). Los scripts de init corren solo con el volumen vacío.
- Los tests usan `RefreshDatabase` sobre `sportiva_testing`, y `Tests\TestCase` frena la suite si la base default no es `sportiva_testing`.
- Laravel Cloud rechaza `USE <base>`, así que el cambio de base se hace reconfigurando la conexión y llamando a `DB::purge()`.

## Goals / Non-Goals

**Goals:**

- Que los ABM de club se puedan escribir y probar (incluido el aislamiento entre clubes) en cuanto se mergee este change.
- Un solo punto del código que sepa conectarse a la base de un club, para que el middleware, los jobs, los tests y el alta de clubes no repitan la lógica.

**Non-Goals:**

- Cachear conexiones por club o mantener varias abiertas a la vez.
- Soportar clubes en otro servidor de MySQL o con credenciales propias.
- Tests en paralelo (`php artisan test --parallel`).

## Decisions

### 1. Implementación propia, sin `stancl/tenancy`

Se registra en el ADR 0002.

- **Por qué:** lo que hace falta es poco (una conexión, un middleware, crear y migrar bases) y ya está descrito en `docs/arquitectura-multi-tenant.md`. `stancl/tenancy` trae sus propias tablas, eventos y *bootstrappers* (caché, filesystem, colas) que no se usan, y su modo multi-base hace `USE` o crea conexiones dinámicas que habría que adaptar al proxy de Laravel Cloud.
- **Alternativa descartada:** `stancl/tenancy`. Vuelve a ser una opción si aparecen necesidades que el paquete ya resuelve, como tenants en varios servidores o aislar la caché y el filesystem por club.

### 2. Una conexión `tenant` fija, con la base configurada en runtime

`config/database.php` define `tenant` como una copia de `mysql` (mismas variables `DB_*`) con `'database' => null`. Los modelos de club declaran `protected $connection = 'tenant';`.

- **Alternativa descartada:** una conexión por club, con nombres dinámicos (`club_river`). Obliga a que los modelos conozcan el nombre de la conexión, y la configuración crece con cada club.

### 3. Un servicio, `App\Tenancy\TenantDatabase`

Es la única clase que toca la conexión `tenant`. Su interfaz, que es el contrato con `platform-admin-and-provisioning`:

| Método | Qué hace |
|---|---|
| `connect(Tenant $tenant)` | Valida el nombre, setea `database.connections.tenant.database` y hace `DB::purge('tenant')` |
| `disconnect()` | Vuelve la base a `null` y hace `DB::purge('tenant')` |
| `run(Tenant $tenant, callable $callback)` | `connect`, ejecuta el callback y `disconnect` en un `finally`. Es lo que usan los jobs, los comandos y los tests |
| `create(Tenant $tenant)` | `CREATE DATABASE IF NOT EXISTS` con `utf8mb4`, con el nombre validado y entre backticks. Corre sobre la conexión `tenant` (sin base) y no sobre la central: `CREATE DATABASE` hace un commit implícito que cerraría una transacción abierta en la central |
| `migrate(Tenant $tenant, bool $fresh = false)` | Dentro de `run`: `migrate` (o `migrate:fresh`) con `--database=tenant --path=database/migrations/tenant --force` |
| `drop(Tenant $tenant)` | `DROP DATABASE IF EXISTS`. En este change **solo acepta bases `club_test_*`**: el borrado de un club real sigue siendo una decisión abierta (ver la arquitectura) |

- **Validación del nombre:** `^club_[a-z0-9_]{1,59}$` (64 caracteres en total como máximo). Si no cumple, lanza una excepción propia que se registra en el log con el `tenant_id` y que el handler devuelve como 500 genérico (spec `api-error-responses`). Nunca se interpola en SQL un nombre sin validar.
- **Guard de testing:** si `app()->environment('testing')` y el nombre no empieza con `club_test_`, `connect`, `create` y `drop` lanzan una excepción con un mensaje que explica la causa.
- **Alternativa descartada:** poner la lógica en el middleware. Habría que repetirla en los jobs, los tests y el alta de clubes.

### 4. El middleware conecta y desconecta en el mismo request

En `EnsureTenantContext`, después de las validaciones que ya existen:

```php
$this->tenantDatabase->connect($tenant);
try {
    return $next($request);
} finally {
    $this->tenantDatabase->disconnect();
}
```

- La respuesta (incluida la serialización de los Resources) se arma dentro de `$next`, así que ya no se consulta la base después del `finally`.
- **Por qué desconectar:** en PHP-FPM cada request es un proceso limpio, pero en los tests, en Octane o en un worker el proceso se reutiliza. Sin `disconnect`, una consulta fuera de contexto usaría la base del último club, que es justo el error que el modelo por base quiere evitar.
- **Alternativa descartada:** desconectar en `terminate()`. Corre después de enviar la respuesta y no siempre en el mismo orden con otros middlewares; el `finally` es más simple y se puede probar.

### 5. Jobs de club: `TenantDatabase::run`

Siguiendo `docs/guia-jobs.md`, el job recibe `int $tenantId` y en `handle()` hace `Tenant::findOrFail($this->tenantId)` y `$tenantDatabase->run($tenant, fn () => ...)`. Esto se documenta en la guía de jobs, con un test de ejemplo. No se agrega un job middleware: con un solo método alcanza, y no hay jobs de club todavía.

### 6. Permisos de MySQL en local y CI: `club\_%`

Un script nuevo, `docker/mysql/init/02-club-dbs.sh`, con el mismo esquema que `01-testing-db.sh`: `GRANT ALL PRIVILEGES ON \`club\_%\`.* TO '<MYSQL_USER>'@'%'`. El `_` va escapado para que el patrón sea literalmente `club_`.

- Sirve para las bases de test (`club_test_*`) y para las bases de club que va a crear el alta de clubes en local.
- **Volúmenes existentes:** el script no corre, y el usuario de la app no se puede dar permisos a sí mismo (la contraseña de root es aleatoria). La solución documentada es recrear el volumen, avisando que borra los datos locales. Si el permiso falta, el test falla con un mensaje que explica cómo arreglarlo.
- **Alternativa descartada:** pedir la contraseña de root de los logs del primer arranque para correr el `GRANT` a mano. Es frágil: el log puede no estar, y expone una credencial.
- En Laravel Cloud, los permisos del usuario del cluster y la separación entre el usuario de operación y el de provisión son de `platform-admin-and-provisioning`.

### 7. Tests: bases fijas, migradas una vez y truncadas entre tests

El trait `Tests\Concerns\InteractsWithTenants`:

- Crea en la base central (dentro de la transacción de `RefreshDatabase`) dos clubes con slugs `test_a` y `test_b`, cuyas bases quedan `club_test_a` y `club_test_b`.
- La primera vez en el proceso, hace `create` y `migrate(fresh: true)` de las dos bases, así el esquema siempre corresponde a las migraciones del código. Los tests siguientes no vuelven a migrar (con un flag estático).
- Antes de cada test, trunca todas las tablas de las dos bases, salvo `migrations`, con `FOREIGN_KEY_CHECKS=0`.
- Helpers: `tenantA()`, `tenantB()`, `clubToken(Tenant $tenant, ?User $user = null)` (crea el usuario, la membresía y un token de club como lo emite `select-tenant`) e `inTenant(Tenant $tenant, callable $callback)` para cargar datos o hacer asserts dentro de un club.
- **Por qué no transacciones:** `DB::purge('tenant')` cierra la conexión, y con eso se pierde la transacción que `RefreshDatabase` abriría en `tenant`. Los datos cargados antes del request quedarían invisibles o se perderían. Truncar dos bases con pocas tablas es rápido.
- **Alternativa descartada:** crear una base nueva por test. Es más aislado, pero `CREATE DATABASE` + migrar en cada test hace la suite mucho más lenta a medida que crecen las migraciones.

Como todavía no hay tablas de club, este change prueba el aislamiento con una migración de prueba que solo existe en los tests (por ejemplo, una tabla `test_items` en `tests/Fixtures/tenant-migrations/`), que el trait aplica además de `database/migrations/tenant`. Así los tests de aislamiento no dependen de ningún módulo.

## Risks / Trade-offs

- **[Riesgo] La conexión directa por PDO con `dbname=club_xxx` contra Laravel Cloud no está verificada** (`docs/arquitectura-multi-tenant.md`). El spike solo probó nombres calificados desde DBeaver. → Mitigación: verificarlo en staging antes del primer club real, dentro de `platform-admin-and-provisioning`. Si falla, el diseño cambia ahí, sin afectar los tests locales ni los ABM.
- **[Riesgo] Un dev con el volumen viejo ve fallar los tests de club.** → Mitigación: el mensaje del error y la guía de desarrollo local explican cómo recrear el volumen, y que eso borra los datos locales.
- **[Trade-off] Una reconexión a MySQL por request de club**, por el `purge`. Cuesta unos milisegundos dentro de la misma red. Se acepta a cambio de no reutilizar nunca la conexión de otro club.
- **[Riesgo] Respuestas *streamed* que consulten la base después de `$next`** quedarían sin conexión. → Hoy no hay ninguna. Si aparece una, tiene que cargar los datos antes de devolver la respuesta.
- **[Trade-off] Tests con truncado en lugar de transacciones:** un test que deje la base de un club a medias no afecta al siguiente, porque se trunca al empezar. A cambio, no se pueden correr en paralelo con las mismas dos bases (está fuera de los objetivos).

## Migration Plan

1. Mergear. En producción no cambia nada mientras no existan clubes con base: `GET /api/me` sigue respondiendo igual, y las consultas de club solo pasan en rutas nuevas.
2. Cada dev recrea su volumen local **una vez** (`docker compose down -v` y `bash scripts/setup.sh`). **Esto borra la base local.** La CI no necesita nada: el volumen es nuevo en cada corrida.
3. Rollback: revertir el PR. No hay migraciones ni datos que deshacer. El script de init extra no molesta si queda en un volumen.

## Open Questions

- Si `create` y `drop` tienen que usar una conexión `provisioning` aparte: lo decide `platform-admin-and-provisioning`. El servicio puede recibir el nombre de la conexión sin cambiar su interfaz pública.
