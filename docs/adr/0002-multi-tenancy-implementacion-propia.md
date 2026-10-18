# 0002. Multi-tenancy con implementación propia

- Status: accepted
- Date: 2026-10-18

## Context

- El modelo es una base central (`sportiva_platform`) y una base MySQL por club ([arquitectura-multi-tenant.md](../arquitectura-multi-tenant.md)). No se cambia a un esquema compartido con columnas `tenant_id`.
- Lo que hace falta para operar ese modelo es poco: una conexión que apunte a la base del club del token, un middleware que la establezca en cada request, y crear, migrar y borrar bases de club.
- Laravel Cloud rechaza `USE <base>` en su proxy: el cambio de base tiene que hacerse reconfigurando la conexión y llamando a `DB::purge()`.
- La conexión directa por PDO con `dbname=club_xxx` contra Laravel Cloud todavía no está verificada (solo se probaron nombres calificados desde DBeaver).

## Decision

Implementación propia, sin paquete de multi-tenancy (change `tenant-connection`):

- Una conexión `tenant` fija en `config/database.php`: las mismas variables `DB_*` que `mysql`, con `'database' => null`. Los modelos de club declaran `protected $connection = 'tenant';`.
- Un único servicio, `App\Tenancy\TenantDatabase`, que es la única clase que toca esa conexión: `connect`, `disconnect`, `run`, `create`, `migrate` y `drop`. Valida el nombre de la base (`club_<slug>`) antes de usarlo.
- El middleware `tenant.token` conecta después de validar el token, la membresía y el estado del club, y desconecta en un `finally`. Los jobs de club usan `TenantDatabase::run`.

## Alternatives considered

- **`stancl/tenancy`**: resuelve el cambio de conexión, el alta y las migraciones, pero trae sus propias tablas, eventos y *bootstrappers* (caché, filesystem, colas) que no se usan, y su modo multi-base hace `USE` o crea conexiones dinámicas que habría que adaptar al proxy de Laravel Cloud.
- **Una conexión por club, con nombre dinámico** (`club_river`): obliga a que los modelos conozcan el nombre de la conexión, y la configuración crece con cada club.

## Consequences

- El código de multi-tenancy es nuestro: unas pocas clases que el equipo entiende y puede probar, sin convenciones de un paquete.
- Lo que el paquete daría gratis (aislar caché, filesystem o colas por club, tenants en varios servidores) hay que hacerlo a mano si aparece la necesidad. En ese caso, `stancl/tenancy` vuelve a ser una opción.
- Cada request de club abre una conexión nueva a MySQL por el `purge`. Cuesta unos milisegundos y evita reutilizar la conexión de otro club.
- El alta de clubes (`platform-admin-and-provisioning`) usa `TenantDatabase` en lugar de escribir su propia lógica.
