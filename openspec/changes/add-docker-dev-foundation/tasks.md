# Tasks

## 1. Infraestructura Docker (no requiere Docker instalado)

- [x] 1.1 Resolver los tags de patch y los digests vigentes de `php:8.5-cli-trixie`, `mysql:8.4` y `composer:2`, y registrarlos en `docs/decisions.md`. Verificación: cada imagen tiene tag y digest anotados, con su fuente.
- [x] 1.2 Crear `docker/php/Dockerfile` con los targets `base` y `dev` (D1–D5). Verificación: lectura contra la spec `local-dev-environment`; el build real se hace en 3.1.
- [x] 1.3 Crear `.dockerignore` que excluya `.env*` (salvo `.env.example`), `vendor/`, `node_modules/`, `storage/logs/*`, `.git/`, `*.sql` y `*.sql.gz`. Verificación: el build de 3.1 no contiene `.env` (se inspecciona en 3.2).
- [x] 1.4 Crear `compose.yaml` (servicios `backend` y `mysql`, volumen `mysql-data`, healthcheck, `depends_on` con `service_healthy`, endurecimiento y puertos en `127.0.0.1`; D8–D11) y `compose.override.example.yaml`. Verificación: `docker compose config` en 3.1.
- [x] 1.5 Crear `docker/mysql/init/01-testing-db.sh`, que crea `sportiva_testing` y otorga permisos al usuario de app (D9). Verificación: `SHOW DATABASES` en 3.3.

## 2. Prerequisito: Docker Desktop (lo hace el dueño, bloqueante)

- [x] 2.1 El dueño instala Docker Desktop con el backend WSL 2 (los comandos están en `docs/desarrollo-local.md`, que se escribe en 4.4) y habilita la integración con Ubuntu. Verificación: `docker --version` y `docker compose version` responden, y `docker run --rm hello-world` funciona.

## 3. Skeleton de Laravel generado en el contenedor

- [x] 3.1 Construir la imagen `dev` y generar `laravel/laravel:^13` con `composer create-project` dentro del contenedor, en un directorio temporal. Después, copiarlo a la raíz sin pisar los archivos existentes (`AGENTS.md`, `CLAUDE.md`, `docs/`, `openspec/`, `.claude/`, `.engram/`), mergeando `.gitignore` y `README.md`. Verificación: `docker compose config` sin errores, `composer.json` con `laravel/framework` ^13, y `php -v` en el contenedor reporta 8.5.
- [x] 3.2 Editar `.env.example` para MySQL (D8): `DB_HOST=mysql`, `DB_DATABASE=sportiva_platform`, sin SQLite, y borrar `database/database.sqlite`. Verificación: `git check-ignore .env` lo ignora, y `docker run --rm <imagen> ls -a /var/www/html` no muestra `.env`.
- [x] 3.3 Levantar el entorno y migrar. Verificación: `docker compose up -d --wait` pasa, `php artisan migrate` funciona contra `sportiva_platform`, `sportiva_testing` existe, `whoami` en `backend` no es root, y después de `docker compose down` + `up` las tablas siguen presentes.
- [x] 3.4 Confirmar que MySQL no está expuesto. Verificación: `docker compose ps` no muestra ningún puerto publicado para `mysql`, y `backend` solo publica en `127.0.0.1:8000`.

## 4. Fundación de la API

- [x] 4.1 Registrar `routes/api.php` en `bootstrap/app.php` y configurar `shouldRenderJsonWhen` para `api/*` (D12). Agregar `tests/Feature/ApiErrorFormatTest.php` que cubra el 404 y el 405 sin `Accept`, el 422 con una ruta de test y el 500 con `APP_DEBUG=false` sin trace. Verificación: `php artisan test --filter=ApiErrorFormatTest` en verde.
- [x] 4.2 Implementar `GET /api/health` (D12) y `tests/Feature/HealthTest.php`, con el caso 200, el caso 503 con la base caída y el caso con `APP_DEBUG=true` sin detalles de la excepción. Verificación: `php artisan test --filter=HealthTest` en verde, y `curl http://127.0.0.1:8000/api/health` devuelve 200 desde el host.
- [x] 4.3 Configurar `phpunit.xml` para `sportiva_testing` (D13). Verificación: `php artisan test` completo en verde, sin tocar `sportiva_platform`.
- [x] 4.8 Quitar el scaffolding web del skeleton (D18):
  - borrar `resources/`, `vite.config.js`, `package.json`, `.npmrc` y `tests/Feature/ExampleTest.php`;
  - dejar `routes/web.php` sin rutas;
  - sacar `VITE_APP_NAME` de `.env.example`;
  - sacar los scripts de Composer `setup` y `dev`.

  Verificación: `composer validate` pasa, `php artisan route:list` no muestra `/`, `GET /` responde 404, no quedan referencias a Vite ni a npm fuera de `vendor/`, y los tests y Pint pasan.
- [x] 4.4 Crear `scripts/setup.sh` (D17), idempotente. Verificación: corre dos veces seguidas sobre el mismo clon; la segunda vez no cambia `APP_KEY` ni borra datos, y health responde 200.
- [x] 4.5 Crear `scripts/setup-windows.ps1` (D17), con confirmación antes de cada instalación o cambio global y un parámetro `-Yes`. Verificación: en la máquina del dueño, el camino "WSL y Docker ya instalados" clona en WSL y deja health en 200. Los caminos de instalación se reportan como NOT RUN si no se pudieron probar.
- [x] 4.6 Escribir `docs/desarrollo-local.md`. Contenido:
  - instalación de Docker Desktop y WSL 2 en PowerShell;
  - clonado en WSL (D6);
  - flujo A (build local) y flujo B (pull desde GHCR, marcado como futuro según D15);
  - el setup automatizado (`setup-windows.ps1` y `setup.sh`), y el camino manual equivalente;
  - Artisan, Composer, logs, cómo detener el entorno y acceso opcional a MySQL;
  - reset de datos, advirtiendo que `down -v` borra la base local;
  - troubleshooting de puertos, permisos/UID, espera de la base y `$` en el `.env`;
  - las limitaciones de `artisan serve`, y que Docker no reemplaza la auth, la autorización ni el aislamiento entre tenants.

  Verificación: cada comando de los flujos A y B (salvo el pull, que es futuro) se ejecutó tal como está escrito.
- [x] 4.7 Completar los comandos verificados en `AGENTS.md`, y reescribir el `README.md` con una intro corta y links a la documentación. Verificación: ningún comando queda en TBD sin una razón.

## 5. Arquitectura multi-tenant (documentación)

- [x] 5.1 Escribir `docs/arquitectura-multi-tenant.md`. Contenido:
  - la base central y las bases por club;
  - qué vive en cada una;
  - cómo entran la conexión `tenant`, el alta de clubes, `tenants:migrate` y los usuarios de BD separados;
  - los secretos (credenciales en las variables de entorno de Laravel Cloud, nunca en la base central ni en el repo);
  - las decisiones que necesitan un ADR (`stancl/tenancy` o una implementación propia; los usuarios de provisión).

  Verificación: es consistente con §5.6 del doc de integración del front, con `sportiva_platform` como nombre de la base central.

## 6. CI

- [x] 6.1 Crear `.github/workflows/ci.yml` (D14) con `permissions: contents: read`, en `pull_request` y en `push` a `main`, sin publicar nada. Verificación: si `actionlint` está disponible, sin errores. **Se reporta como "no verificado en una ejecución real de GitHub" hasta que corra en un PR**, porque hacer push requiere aprobación.

## 7. Verificación integral y revisión

- [x] 7.1 Desde un clon limpio en WSL, seguir `docs/desarrollo-local.md` (flujo A) de punta a punta. Verificación: health 200 y tests en verde.
- [x] 7.2 Correr `openspec validate add-docker-dev-foundation --strict`. Verificación: sin errores.
- [x] 7.3 Revisión independiente (correctness y seguridad) del Dockerfile, el Compose, la CI y el health. Verificación: los hallazgos quedan resueltos o registrados como riesgos en el reporte final.
- [x] 7.4 Reporte final con los criterios de aceptación 1 a 15 de la Fase 1, cada uno como PASS, FAIL, BLOCKED o NOT RUN.

## Workflow follow-up

- Commit en `chore/foundation`, push y PR, cada uno con aprobación explícita del dueño. Antes del commit: `git add --chmod=+x scripts/setup.sh docker/mysql/init/01-testing-db.sh` (el repo en `C:\` tiene `core.filemode=false`).
- Ver la CI en verde en el PR.
- Archivar el change con `/opsx:archive` después de la aprobación.
- Change aparte en el repo Front-Sportiva: renombrar `olim_platform` a `sportiva_platform` en los ADR y en la doc de integración.
