# Tasks

Todos los comandos corren con `docker compose exec backend` adelante, salvo `openspec`.

## 1. Decisión y permisos de MySQL

- [x] 1.1 Escribir `docs/adr/0002-multi-tenancy-implementacion-propia.md` (contexto, opciones, decisión y consecuencias de la decisión 1 del design) y enlazarlo desde `docs/decisions.md`; verificar que los links resuelven
- [x] 1.2 Crear `docker/mysql/init/02-club-dbs.sh` con el `GRANT` sobre `club\_%` (decisión 6); verificar con un volumen nuevo que el usuario de la app puede `CREATE DATABASE club_test_x` y `DROP DATABASE club_test_x`, y que no puede crear `otra_base`
- [x] 1.3 Documentar en `docs/desarrollo-local.md` por qué hay que recrear el volumen una vez y cómo hacerlo, con el aviso de que borra los datos; verificar que el comando documentado funciona desde PowerShell

## 2. Conexión y servicio `TenantDatabase`

- [x] 2.1 Agregar la conexión `tenant` a `config/database.php` (copia de `mysql` con `database => null`); verificar con `php artisan tinker` que `config('database.connections.tenant.database')` es `null`
- [x] 2.2 Implementar `App\Tenancy\TenantDatabase` con `connect`, `disconnect`, `run`, `create`, `migrate` y `drop`, la validación del nombre y el guard de testing (decisión 3); verificar con tests unitarios: nombre inválido rechazado, `run` desconecta también con excepción, consulta sin contexto falla, `drop` rechaza bases que no son `club_test_*`, y en `testing` `connect` rechaza `club_river`
- [x] 2.3 Mapear la excepción de nombre inválido a un 500 genérico, con log del `tenant_id`; verificar con un test que la respuesta no incluye el nombre de la base
- [x] 2.4 Crear `database/migrations/tenant/.gitkeep`; verificar que `migrate` sobre la base central no aplica nada de esa carpeta (la tabla `migrations` central no cambia)

## 3. Soporte de tests multi-club

- [x] 3.1 Crear la migración de prueba `tests/Fixtures/tenant-migrations/` (tabla `test_items`) y una ruta de club solo para tests que lea y modifique `test_items`, registrada únicamente en `testing`; verificar que `php artisan route:list` fuera de `testing` no la muestra
- [x] 3.2 Implementar `Tests\Concerns\InteractsWithTenants` (decisión 7): `tenantA()`, `tenantB()`, `clubToken()`, `inTenant()`, migración única por proceso y truncado antes de cada test; verificar que dos tests seguidos empiezan con `test_items` vacía
- [x] 3.3 Si falta el permiso de MySQL, el trait falla con un mensaje que apunta a la sección de `docs/desarrollo-local.md`; verificar quitando el permiso en un volumen de prueba

## 4. Middleware y jobs

- [x] 4.1 En `EnsureTenantContext`, conectar después de las validaciones y desconectar en un `finally` (decisión 4); verificar con tests de feature: el request del club A lee la base A, con token de A un registro de B responde 404 y no cambia, A y después B en el mismo test leen cada uno su base, y los 403 existentes (`tenant_required`, `membership_inactive`, `tenant_inactive`) no abren conexión de club
- [x] 4.2 Verificar que `tests/Feature/Auth/AuthFlowTest.php` y `tests/Feature/Admin/TenantAdminTest.php` siguen pasando sin cambios (`GET /api/me` responde igual)
- [x] 4.3 Agregar un test con un job de ejemplo (solo en tests) que recibe `tenantId` y usa `TenantDatabase::run`; verificar que dos jobs seguidos de clubes distintos consultan cada uno su base y que al terminar la conexión queda sin base
- [x] 4.4 Actualizar la sección de jobs de club de `docs/guia-jobs.md` con `TenantDatabase::run`; verificar que el ejemplo coincide con el test de 4.3

## 5. Documentación y contrato

- [x] 5.1 Actualizar `docs/guia-bases-de-datos.md` y `docs/arquitectura-multi-tenant.md`: qué existe ahora (conexión, servicio, permisos, tests), y que el alta de clubes (`platform-admin-and-provisioning`) usa `TenantDatabase`; verificar que las tablas de estado coinciden con el código
- [x] 5.2 Actualizar `docs/guia-abm.md`: los middlewares reales del grupo de club (`auth:sanctum`, `tenant.token`) y el uso de `InteractsWithTenants` en los tests mínimos; verificar que el ejemplo de test usa los helpers reales
- [x] 5.3 Actualizar `docs/modelo-datos-club/plan-implementacion.md`: C1–C4 pasan a "hecho" y la sección "Quién hace qué" refleja la división de este change
- [x] 5.4 Correr `php artisan scramble:export` y verificar que `docs/api/openapi.json` no cambia (la ruta de test no se publica)

## 6. Verificación final

- [x] 6.1 Con un volumen recreado: `vendor/bin/pint --test`, `php artisan test` y `openspec validate tenant-connection --strict`, y pegar la salida real en el PR
- [ ] 6.2 Verificar en la CI del PR que los tests de club pasan con el volumen nuevo, sin cambios en `.github/workflows/ci.yml`

## Workflow follow-up

- Avisar a Tiziano que `platform-admin-and-provisioning` usa `TenantDatabase` (`create`, `migrate` y `run`) y ya no incluye la conexión ni el middleware.
- Siguiente change del plan: `club-access-control` (módulo 1: `club_users`, roles y permisos).
- Archivar con `/opsx:archive` después de la aprobación del equipo.
