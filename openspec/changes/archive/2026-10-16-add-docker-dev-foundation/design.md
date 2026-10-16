# Design

## Context

- El repo está vacío. Las decisiones de stack (Laravel 13, PHP 8.5, MySQL 8.4, la app en la raíz) están en [ADR 0001](../../../docs/adr/0001-stack-y-arquitectura.md).
- Producción va a Laravel Cloud, que compila desde Git: **la imagen Docker solo se usa para desarrollo y CI**.
- El host del dueño es Windows 11 con WSL 2 (Ubuntu) activo. No tiene Docker Desktop, y su PHP local (8.2) no sirve para Laravel 13. Hasta que haya Docker, el skeleton no se puede generar ni testear.
- La arquitectura multi-tenant (una base central y una por club) está descripta en `docs/integracion-api-laravel.md` §5.6, en el repo del front.

## Goals / Non-Goals

**Goals:**
- Un único camino de setup, igual para todos los devs y para la CI.
- Que la imagen y el Compose sean fáciles de leer para un equipo que está aprendiendo Docker.
- Que la configuración no impida agregar después la conexión `tenant`.

**Non-Goals:**
- Una imagen de producción: Laravel Cloud no la usa. Solo se deja el camino descripto.
- Nginx/PHP-FPM, Octane o FrankenPHP en desarrollo.
- Xdebug: se puede sumar después como un target opcional.
- Dependabot y escaneo de imágenes: van en un change posterior.

## Decisions

### D1. Base `php:8.5-cli-trixie`, fijada por tag de patch y digest
- Se usa Debian (glibc) porque las extensiones `intl` y `zip` compilan sin sorpresas. Es Debian 13 (trixie), el stable vigente: bookworm ya es oldstable.
- Las extensiones se instalan con el `docker-php-ext-install` oficial, más los paquetes `-dev` de apt.
- El tag exacto (`8.5.11-cli-trixie@sha256:...`) quedó registrado en `docs/decisions.md`, y en el Dockerfile queda documentado cómo actualizarlo.
- **Alternativas:**
  - Alpine: usa musl, con diferencias sutiles en `intl` e iconv, y el ahorro de tamaño no importa en desarrollo.
  - `mlocati/docker-php-extension-installer`: es más cómodo, pero suma una dependencia de terceros a la cadena de build.

### D2. Composer copiado desde la imagen oficial con versión fija
- `COPY --from=composer:2.x.y /usr/bin/composer /usr/bin/composer`.
- Es reproducible y no ejecuta un instalador descargado con `curl | php`.

### D3. Multi-stage con los targets `base` y `dev`
- **`base`:** PHP más las extensiones `pdo_mysql`, `intl`, `zip`, `bcmath` y `opcache`, el usuario no-root y los permisos.
- **`dev`:** agrega Composer, `git` y `unzip`, y su comando es `php artisan serve --host=0.0.0.0 --port=8000`. La CI usa el target `dev`.
- **`prod` (futuro, no se implementa):** se construiría sobre `base`, con `composer install --no-dev` en un stage aparte y el código copiado dentro de la imagen. Solo tendría sentido si se dejara Laravel Cloud.
- **Por qué multi-stage ya:** separar `base` cuesta poco y deja lista la frontera para un `prod` sin reescribir nada.

### D4. `php artisan serve` como servidor de desarrollo
- Es el servidor embebido de PHP. Con `PHP_CLI_SERVER_WORKERS=4` atiende requests en paralelo.
- **Limitaciones** (se documentan):
  - no es apto para producción;
  - no hay TLS;
  - el rendimiento es bajo con cargas grandes.
- **Alternativa:** Nginx + PHP-FPM. Son dos procesos y más configuración, sin beneficio para una API JSON en desarrollo.

### D5. Usuario no-root con el UID/GID del host
- Los build args `UID`/`GID` valen 1000 por defecto, que coincide con el primer usuario de WSL Ubuntu.
- Así, los archivos que crean `composer install` y `artisan` en el bind mount quedan con el dueño correcto.
- En la CI se pasa `UID=$(id -u)`, porque el runner de GitHub usa el UID 1001.
- `storage/` y `bootstrap/cache/` tienen que ser escribibles por ese usuario.

### D6. El repo se clona dentro de WSL (recomendado)
- Un bind mount desde `C:\` (NTFS, vía 9P) es muy lento con los miles de archivos de `vendor/` y emula los permisos.
- La guía recomienda clonar en `~/` dentro de Ubuntu (WSL) y abrirlo con VS Code Remote WSL.
- Clonar en `C:\` queda como fallback documentado, con sus limitaciones.

### D7. Bind mount de todo el repo, `vendor/` incluido
- `composer install` corre dentro del contenedor y escribe en el repo montado. `vendor/` está en `.gitignore`.
- **Alternativa:** un volumen nombrado para `vendor/`. Es más rápido en NTFS, pero el IDE no ve las dependencias. Con D6 no hace falta.

### D8. Un solo `.env` en la raíz para Compose y Laravel
- Compose lee el `.env` del directorio del proyecto para interpolar valores, y Laravel lee el mismo archivo.
- Hay un solo `.env.example`, sin duplicados: el que viene con Laravel, editado para MySQL (`DB_HOST=mysql`, `DB_DATABASE=sportiva_platform`, `DB_USERNAME=sportiva` y una contraseña de desarrollo).
- **Riesgo:** los valores con `$` se interpolan → se documenta que hay que escaparlos como `$$`.

### D9. MySQL: usuario de app y bases de desarrollo
- `MYSQL_DATABASE=sportiva_platform`, `MYSQL_USER`/`MYSQL_PASSWORD` tomados de las variables `DB_*`, y `MYSQL_RANDOM_ROOT_PASSWORD=yes`. La app nunca usa root.
- `docker/mysql/init/01-testing-db.sh` es un script que el entrypoint ejecuta con *source* y que usa `docker_process_sql`. Crea `sportiva_testing` y le da al usuario de app permisos sobre esa base.
  - Los scripts de init **solo corren con un volumen vacío**. Se documenta.
- Para el alta de clubes (un change futuro), el usuario local va a necesitar `CREATE` sobre `club\_%`. No se otorga ahora.

### D10. Healthcheck y espera
- Healthcheck: `mysqladmin ping -h 127.0.0.1 --silent`.
  - Se usa TCP a propósito: durante la inicialización, MySQL corre un servidor temporal con `--skip-networking`, así que el ping recién pasa cuando el servidor definitivo está escuchando.
  - Parámetros: `interval: 5s`, `timeout: 3s`, `retries: 20`, `start_period: 30s`.
- `backend` usa `depends_on: mysql: condition: service_healthy`.
- Para scripts y CI se usa `docker compose up -d --wait`.

### D11. Endurecimiento de los contenedores
- Los dos servicios usan `security_opt: [no-new-privileges:true]` y ninguno es `privileged`.
- `backend` usa `cap_drop: [ALL]`, porque no necesita capabilities: escucha en el puerto 8000, mayor a 1024.
- `mysql` mantiene las capabilities por defecto. El entrypoint oficial necesita `CHOWN`, `SETUID` y `SETGID` para inicializar el directorio y bajar al usuario `mysql`; endurecerlo más tiene un costo de compatibilidad que no se justifica en desarrollo.
- Puertos:
  - `backend`: `127.0.0.1:${APP_PORT:-8000}:8000`;
  - `mysql`: no publica puertos. Hay un `compose.override.example.yaml` con `127.0.0.1:${DB_FORWARD_PORT:-3310}:3306`, que se copia a `compose.override.yaml` (ignorado por Git) cuando hace falta.
  - El puerto por defecto es el 3310 porque en el host del dueño ya hay MySQL nativos escuchando en el 3306 y en el 3307 (los dos de XAMPP/MySQL Server). Los dos están en `0.0.0.0`, y eso queda fuera de este repo.

### D12. La API: routing, errores JSON y health
- `routes/api.php` se registra a mano en `bootstrap/app.php` (`withRouting(api: ...)`). No se usa `php artisan install:api`, porque instala Sanctum, y Sanctum entra con el change de auth.
- Errores en JSON: `$exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson())`. Así se cubren el 404, el 405, el 422 y el 500 con el renderer estándar de Laravel, que ya oculta el trace cuando `APP_DEBUG=false`.
- Health:
  - un controller invocable ejecuta `select 1` en la conexión por defecto dentro de un `try/catch (Throwable)`;
  - si falla, loguea la excepción y responde 503 con un cuerpo fijo;
  - nunca devuelve el mensaje de la excepción. Así se cumple la spec también con `APP_DEBUG=true`.
  - No tiene throttle en esta fase. Se evalúa con el change de auth.
- La convención de validación (Form Requests y respuestas 422) se documenta en `AGENTS.md`. No se crea un endpoint de ejemplo.
- El escenario de validación de la spec `api-error-responses` se testea con una ruta definida dentro del test, no con un endpoint público.

### D13. Tests contra MySQL `sportiva_testing`
- `phpunit.xml` fija `DB_CONNECTION=mysql` y `DB_DATABASE=sportiva_testing`, y los tests usan `RefreshDatabase`.
- **Por qué no SQLite:** las bases por club y las diferencias de SQL hacen que SQLite dé falsos verdes.
- El 503 del health se prueba apuntando la conexión a un puerto inválido dentro del test y llamando a `DB::purge()`.

### D14. CI en GitHub Actions
- Corre en `pull_request` y en `push` a `main`, con `permissions: contents: read`. Usa `pull_request`, no `pull_request_target`, así que los PRs de forks no reciben secrets.
- Pasos:
  1. checkout;
  2. `./scripts/setup.sh` (D17): copia el `.env`, hace el build con el `UID` del runner, levanta los servicios con `--wait`, instala las dependencias, genera la key y migra;
  3. `docker compose exec -T backend php artisan test`;
  4. si algo falla, `docker compose logs`;
  5. `docker compose down`, sin `-v`: el runner se descarta igual.
- **Por qué con Compose:** la CI ejercita el mismo `compose.yaml`, el mismo healthcheck y el mismo script de init que los devs. Si se rompe el entorno local, se rompe la CI.
- Las actions se fijan por tag mayor, y se verifica la versión vigente al aplicar el change.

### D15. GHCR (diferido, se documenta en `docs/desarrollo-local.md`)
- **Nombre:** `ghcr.io/finalproyectogrupo8-max/api_sportiva`. GHCR exige minúsculas, y el nombre se deriva del repo `API_Sportiva` de esa organización.
- **Tags:** `sha-<short>` en cada push a `main`, y `vX.Y.Z` en cada tag de release.
- **Workflow:** un job aparte, que solo corre después de tests verdes y con `permissions: packages: write`. Se autentica con `GITHUB_TOKEN` y nunca corre desde un PR.
- **Para los devs:** como el package es privado, se autentican con `docker login ghcr.io` y un PAT con `read:packages`. La imagen sola no alcanza: también necesitan el `compose.yaml`, el `.env.example` y las migraciones del mismo commit.

### D16. Compatibilidad con multi-tenant
- La conexión por defecto `mysql` es la base central. Su nombre sale de `DB_DATABASE`, no está hardcodeado.
- La conexión `tenant`, `database/migrations/tenant/`, `tenants:create` y `tenants:migrate` **no** se crean en esta fase. Se describen en `docs/arquitectura-multi-tenant.md`, con la decisión pendiente entre `stancl/tenancy` y una implementación propia.

### D17. Setup automatizado con dos scripts
- **`scripts/setup.sh`**: es la inicialización del proyecto. Es idempotente y corre en WSL, Linux, macOS y la CI. Pasos:
  1. `cp -n .env.example .env`;
  2. build con el `UID`/`GID` del usuario;
  3. `up -d --wait`;
  4. `composer install`;
  5. `key:generate`, solo si `APP_KEY` está vacío;
  6. `migrate`.

  No borra volúmenes ni datos. La CI lo usa, así que el script queda verificado en cada PR.
- **`scripts/setup-windows.ps1`**: es el bootstrap de Windows y corre en PowerShell. Pasos:
  1. verifica que WSL 2 tenga Ubuntu. Si falta, ofrece `wsl --install -d Ubuntu`, que requiere admin y quizás reiniciar;
  2. verifica Docker Desktop. Si falta, ofrece `winget install -e --id Docker.DockerDesktop` y espera a que el daemon responda;
  3. verifica que `docker` funcione **dentro** de Ubuntu (la integración WSL de Docker Desktop). Si no funciona, explica cómo habilitarla, porque esa configuración no se automatiza;
  4. clona el repo en `~/repos/api-sportiva` dentro de Ubuntu. Si Git en WSL no tiene credenciales, ofrece configurar el Git Credential Manager de Windows como helper;
  5. ejecuta `scripts/setup.sh` dentro de WSL;
  6. si existe `code`, abre VS Code Remote WSL.
- **Principios:**
  - toda instalación o cambio de configuración global se **confirma con el usuario**, y `-Yes` lo omite;
  - el script no desactiva protecciones ni cambia la execution policy global: se ejecuta con `-ExecutionPolicy Bypass -File`, solo para ese proceso;
  - es idempotente, así que se puede volver a correr.
- **Bootstrap:** el repo es privado, así que `irm <url> | iex` no funciona sin autenticación. Se documenta que hay que descargar el script con `gh` o desde el botón *Raw* de GitHub. Además, se documenta revisarlo antes de ejecutarlo.
- **Alternativa:** un Dev Container. Ata el flujo a VS Code, y combinar Compose con un volumen tiene detalles delicados en los bind mounts. Queda como opción futura.

### D18. La API no tiene frontend: se quita el scaffolding web del skeleton
- El skeleton de Laravel trae un frontend: `resources/css`, `resources/js`, la vista `welcome`, la ruta `/`, Vite (`vite.config.js`, `package.json`, `.npmrc`), `VITE_APP_NAME` y los scripts de Composer `setup` y `dev`, que llaman a npm.
- La UI del producto vive en el repo Front-Sportiva, y el contenedor no tiene Node: todo eso sobra y confunde, así que se borra.
- **Se conserva:** `routes/web.php`, vacío y registrado, y `/up`, el health liviano de Laravel que no toca la base.
- La única UI que va a tener la API es la documentación (Swagger UI sobre el OpenAPI de Scramble). Va en el change `add-api-docs`, que va a recrear `resources/views/` con una sola vista.
- **Alternativa:** dejarlo, porque no hace daño en runtime. Se descarta: suma dependencias npm sin uso y un `composer setup` que falla dentro del contenedor.

## Risks / Trade-offs

- **El host no tiene Docker.** Nada de la etapa de app se puede verificar hasta que el dueño instale Docker Desktop → tasks.md marca ese bloqueo como un paso explícito, y lo que no se pueda correr se reporta como NOT RUN.
- **Bind mount sobre NTFS.** Si alguien clona en `C:\`, el entorno es lento y los permisos son raros → la guía recomienda WSL (D6) y tiene una sección de troubleshooting.
- **UID distinto del host.** Los archivos de `vendor/` y `storage/` quedan sin permisos de escritura → build args (D5), y la guía documenta cómo reconstruir con el UID propio.
- **Diferencias con Laravel Cloud.** PHP, MySQL o las extensiones pueden diferir entre local y producción → las versiones quedan escritas en el ADR 0001 y en un solo lugar del Dockerfile. Al configurar Laravel Cloud se comparan a mano.
- **Digests fijos que envejecen.** Sin actualizaciones, se acumulan CVEs → el procedimiento de actualización queda documentado, y Dependabot se propone en un change posterior.
- **`artisan serve` no es producción.** Alguien podría usarlo como referencia de rendimiento → se documenta la limitación.
- **Interpolación de `$` en el `.env`.** Un valor con `$` se rompe en silencio → se documenta (D8).
- **Health con una base que no responde a nivel de red.** El connect timeout de PDO es de 5 s (`DB_CONNECT_TIMEOUT`), pero Laravel reintenta en el connector y en la query ("lost connection"). Medido: ~21 s hasta el 503 (antes eran ~60 s o más). No se baja más porque la base Flex de Laravel Cloud tiene arranque en frío → si un monitor necesita respuestas más rápidas, se agrega un chequeo propio con un único intento en el change de deploy.
- **El bit de ejecución se pierde en Windows.** El repo en `C:\` tiene `core.filemode=false` → la documentación y los scripts usan `bash scripts/setup.sh`, y al commitear se marca con `git add --chmod=+x`.
- **El GID del host ya existe en la imagen** (macOS 20, runner de la CI) → el Dockerfile reutiliza el grupo existente y `useradd --non-unique`.
- **`artisan serve` como PID 1 ignora SIGTERM** → `init: true` en `backend`. El `stop` pasó de 10 s (exit 137) a 1 s.
- **`setup-windows.ps1` no es testeable en la CI.** Depende de WSL, de winget y de un escritorio con Docker Desktop → se verifica a mano en la máquina del dueño (camino "ya instalado"). Los caminos de instalación quedan como NOT RUN hasta que otro dev los pruebe.

## Migration Plan

No aplica: es un repo nuevo y no hay deploy en esta fase. El rollback es revertir los commits de la rama `chore/foundation`.

## Open Questions

- Las versiones exactas de patch y los digests de `php`, `mysql` y `composer` se resuelven al aplicar el change. No cambian el diseño.
