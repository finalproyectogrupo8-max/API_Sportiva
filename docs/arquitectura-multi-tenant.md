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
| Migraciones de la base central (`database/migrations/`) | ✅ Las default de Laravel (`users`, `cache`, `jobs`) |
| Conexión `tenant` | ❌ Futura |
| `database/migrations/tenant/` | ❌ Futura |
| Alta de clubes, `tenants:migrate` | ❌ Futuros |
| Auth (Sanctum, token atado al tenant) | ❌ Futuro |

La Fase 1 no asume que todos los datos vivan en una base: nada del código usa nombres de base fijos, y los modelos de club van a declarar su propia conexión.

## Cómo se va a introducir (changes futuros)

Cada punto es un change de OpenSpec propio, con su propuesta y aprobación.

### 1. Conexión `tenant`

- En `config/database.php`, una segunda conexión `tenant`: el mismo host, usuario y contraseña que `mysql`, con `database => null`.
- Un middleware, después de autenticar y validar la membresía, setea la base del club y descarta la conexión anterior:

  ```php
  config(['database.connections.tenant.database' => $tenant->database_name]);
  DB::purge('tenant');
  ```

- **Nunca** usar `USE <base>`: el proxy de Laravel Cloud lo rechaza (`SQL Error 1148`). Laravel no lo necesita, porque abre la conexión con la base indicada en el DSN.
- Los modelos del club declaran `protected $connection = 'tenant';`. Los modelos centrales usan la default.
- **Pendiente de verificar:** la conexión directa desde PDO con `dbname=club_xxx` contra Laravel Cloud. El spike solo probó nombres calificados desde DBeaver.

### 2. Alta de clubes (`php artisan tenants:create <slug> "<nombre>"`)

Es un comando administrativo, no un endpoint público:

1. Valida el slug con `^[a-z0-9_]+$`. **Es obligatorio**: el nombre de una base no se puede pasar como parámetro de una consulta preparada, va concatenado en el SQL.
2. Inserta en `tenants` con `database_name = club_<slug>` y `status = provisioning`.
3. `CREATE DATABASE club_<slug>`.
4. `migrate --database=tenant --path=database/migrations/tenant --force`.
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
- En local, el usuario `sportiva` hoy solo tiene permisos sobre `sportiva_platform` y `sportiva_testing`. El change del alta de clubes le va a agregar `club\_%` (por un nuevo script de init y un reset documentado).

### 5. Tests

- Los tests de la base central usan `sportiva_testing`.
- Los tests de tenant van a necesitar bases de club de testing (por ejemplo, `club_test_<n>`) creadas y borradas por el propio test suite. Se diseña en el change de la conexión `tenant`.

## Decisiones que necesitan aprobación antes de implementar

| Decisión | Opciones | Cuándo |
| --- | --- | --- |
| Paquete o implementación propia | `stancl/tenancy` (resuelve el cambio de conexión, el alta y las migraciones, pero impone sus convenciones) o una implementación propia (conexión `tenant` + `DB::purge`, comandos propios) | ADR antes del change de la conexión `tenant` |
| Separación de usuarios de BD | Dos usuarios en el cluster de Laravel Cloud o uno solo con permisos amplios | Antes del change de alta de clubes |
| Convención de nombres de las bases | `club_<slug>` (actual) o `club_<id>` (no cambia si se renombra el slug) | Con el change de alta de clubes |
| Borrado de un club | Soft delete (`status = archived`) o `DROP DATABASE` con backup previo | Antes de implementar el borrado |

## Lo que Docker no resuelve

El aislamiento entre clubes lo dan **la API y la base de datos**, no los contenedores: la autenticación, la validación de la membresía en cada request, la conexión `tenant` correcta y los permisos de los usuarios de MySQL. Docker solo aísla procesos en tu máquina de desarrollo.
