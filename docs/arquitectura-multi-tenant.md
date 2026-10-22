# Arquitectura multi-tenant: una base central y una base por club

> **Estado:** decidido a nivel de producto (ADR 0001 y ADR 0002 del repo [Front-Sportiva](https://github.com/finalproyectogrupo8-max/Front-Sportiva), y su `docs/integracion-api-laravel.md` §5.6). **Solo la base central está implementada.** Este documento describe cómo se va a introducir el resto y qué decisiones faltan.
>
> Esos documentos del front todavía dicen `olim_platform`. El nombre vigente es `sportiva_platform` (ver [decisions.md](decisions.md)).
>
> **Para el paso a paso con ejemplos** (cómo se escriben y aplican las migraciones de club, el job que da de alta los clubes, el panel de administración y los comandos), ver la [guía para devs de bases de datos](guia-bases-de-datos.md).

## El modelo

```
Servidor MySQL (local: servicio "mysql"; producción: cluster de Laravel Cloud)
├── sportiva_platform   ← base CENTRAL (conexión "mysql", la default)
│     users, tenants, tenant_memberships, personal_access_tokens
├── club_<slug>         ← una base OPERATIVA por club (conexión "tenant", futura)
│     jugadores, partidos, pedidos, roles, permisos...
└── club_<otro-slug>
```

- **Base central:** usuarios de la plataforma, clubes (`tenants`), membresías (`tenant_memberships`, la única fuente de autorización para entrar a un club) y tokens de Sanctum atados a un tenant.
- **Base de cada club:** todos sus registros operativos. Un club nunca comparte tablas con otro.
- **No** se usa una base compartida con una columna `club_id` en cada tabla. Fue el plan B y se descartó en el ADR 0002 del front.

Por qué una base por club: aislamiento fuerte entre clubes (un bug en una consulta no puede mezclar datos de dos clubes), backups y restauraciones por club, y la posibilidad de borrar o exportar un club entero. El costo: migraciones en N bases y un cambio de conexión por request.

## Qué existe hoy (Fase 1)

| Pieza | Estado |
| --- | --- |
| Conexión `mysql` → `sportiva_platform` | ✅ Configurada por variables `DB_*`. El nombre no está hardcodeado |
| Migraciones de la base central (`database/migrations/`) | ✅ Las de Laravel más `tenants`, `tenant_memberships` y `personal_access_tokens` con `tenant_id` |
| Auth (Sanctum, token atado al tenant) | ✅ Change `platform-auth` |
| Conexión `tenant`, `App\Tenancy\TenantDatabase` y el cambio de base en `tenant.token` y en los jobs | ✅ Change `tenant-connection` ([ADR 0002](adr/0002-multi-tenancy-implementacion-propia.md)) |
| `database/migrations/tenant/` | ✅ Las 32 tablas del [modelo de datos de la base de cada club](modelo-datos-club/README.md). Los ABM se implementan por módulo |
| Permiso del usuario MySQL local sobre `club\_%` | ✅ `docker/mysql/init/02-club-dbs.sh` |
| Tests multi-club (`club_test_a`, `club_test_b`) | ✅ `tests/Concerns/InteractsWithTenants.php` |
| Alta de clubes (`ProvisionTenant`, `tenants:create`), `tenants:migrate` | ❌ Change `platform-admin-and-provisioning`, que usa `TenantDatabase` |

La Fase 1 no asume que todos los datos vivan en una base: nada del código usa nombres de base fijos, y los modelos de club van a declarar su propia conexión.

## Cómo se introduce

Cada punto es un change de OpenSpec propio, con su propuesta y aprobación. El 1 ya está hecho; los demás siguen pendientes.

### 1. Conexión `tenant` ✅

- En `config/database.php`, una segunda conexión `tenant`: el mismo host, usuario y contraseña que `mysql`, con `database => null`.
- `App\Tenancy\TenantDatabase` es la única clase que la toca. `connect` valida el nombre (`^club_[a-z0-9_]{1,59}$`), setea la base del club y llama a `DB::purge('tenant')`; `disconnect` la deja sin base; `run` hace las dos cosas alrededor de un callback. También crea, migra y borra bases de club (`create`, `migrate`, `drop`; `drop` por ahora solo acepta `club_test_*`).
- El middleware `tenant.token`, después de validar el token, la membresía y el estado del club, conecta y desconecta en un `finally`. Va antes de `SubstituteBindings` en la prioridad de middlewares, para que el route model binding de los modelos de club ya tenga base.
- **Nunca** usar `USE <base>`: el proxy de Laravel Cloud lo rechaza (`SQL Error 1148`). La base va en el DSN, y la conexión `tenant` tiene `'use_db_after_connecting' => false`, porque por defecto Laravel además ejecuta `use` al conectar.
- Los modelos del club declaran `protected $connection = 'tenant';`. Los modelos centrales usan la default.
- **Pendiente de verificar:** la conexión directa desde PDO con `dbname=club_xxx` contra Laravel Cloud. El spike solo probó nombres calificados desde DBeaver.

### 2. Alta de clubes (`php artisan tenants:create <slug> "<nombre>"`)

Es un comando administrativo, no un endpoint público:

1. Valida el slug con `^[a-z0-9_]+$`. **Es obligatorio**: el nombre de una base no se puede pasar como parámetro de una consulta preparada, va concatenado en el SQL.
2. Inserta en `tenants` con `database_name = club_<slug>` y `status = provisioning`.
3. `TenantDatabase::create` (`CREATE DATABASE IF NOT EXISTS`).
4. `TenantDatabase::migrate` (`migrate --database=tenant --path=database/migrations/tenant --force`).
5. Seeder de roles y permisos iniciales.
6. Membresía del administrador y `status = active`.

Si un paso falla, queda `status = failed` y el comando se puede volver a correr sin duplicar nada.

### 3. Migraciones por club (`php artisan tenants:migrate`)

Recorre los clubes activos y migra cada base. Informa cuáles fallaron, para reintentar solo esos. En Laravel Cloud va en los *deploy commands*, después de `migrate --force`. Las migraciones tienen que ser **compatibles hacia atrás** (expand → migrate → contract), porque un deploy puede dejar clubes con el esquema nuevo y otros con el viejo.

### 4. Usuarios de base de datos y secretos

| Usuario | Permisos | Lo usa |
| --- | --- | --- |
| Operación | Lectura/escritura sobre `sportiva_platform` y `club_%` | La API en cada request |
| Provisión | `CREATE` de bases | Solo `tenants:create` |

- Las **credenciales** viven en las variables de entorno del entorno de Laravel Cloud (en local, en `.env`). **Nunca** en el repo ni en la base central.
- La tabla `tenants` guarda solo el **nombre** de la base del club, no contraseñas. Todas las bases de un cluster comparten el usuario de operación.
- Si en el futuro un club necesitara credenciales propias (otro servidor, por ejemplo), en la base central se guardaría solo una **referencia** a un secreto en un gestor de secretos, nunca el secreto. Eso requiere su propio ADR.
- En local, el usuario `sportiva` tiene permisos sobre `sportiva_platform`, `sportiva_testing` y `club\_%` (`docker/mysql/init/02-club-dbs.sh`, desde `tenant-connection`; un volumen anterior hay que recrearlo una vez, ver [desarrollo-local.md](desarrollo-local.md#los-tests-de-club-fallan-por-permisos-de-mysql)).

### 5. Tests

- Los tests de la base central usan `sportiva_testing`.
- Los tests de club usan `Tests\Concerns\InteractsWithTenants`: dos clubes de prueba, `club_test_a` y `club_test_b`, con sus bases creadas y migradas una vez por proceso y truncadas antes de cada test. En `testing`, `TenantDatabase` rechaza cualquier base que no empiece con `club_test_`.

## Decisiones que necesitan aprobación antes de implementar

| Decisión | Opciones | Cuándo |
| --- | --- | --- |
| Separación de usuarios de BD | Dos usuarios en el cluster de Laravel Cloud o uno solo con permisos amplios | Antes del change de alta de clubes |
| Convención de nombres de las bases | `club_<slug>` (actual) o `club_<id>` (no cambia si se renombra el slug) | Con el change de alta de clubes |
| Borrado de un club | Soft delete (`status = archived`) o `DROP DATABASE` con backup previo | Antes de implementar el borrado |

## Lo que Docker no resuelve

El aislamiento entre clubes lo dan **la API y la base de datos**, no los contenedores: la autenticación, la validación de la membresía en cada request, la conexión `tenant` correcta y los permisos de los usuarios de MySQL. Docker solo aísla procesos en tu máquina de desarrollo.
