# Desarrollo local

Cómo levantar la API Sportiva en tu máquina. **No hace falta instalar PHP, Composer ni MySQL**: todo corre en Docker.

- [Resumen](#resumen)
- [Opción 1: setup automático en Windows (recomendado)](#opción-1-setup-automático-en-windows-recomendado)
- [Opción 2: paso a paso (flujo A, build local)](#opción-2-paso-a-paso-flujo-a-build-local)
- [Flujo B: usar una imagen publicada (futuro)](#flujo-b-usar-una-imagen-publicada-futuro)
- [Uso diario](#uso-diario)
- [Acceso a MySQL desde el host](#acceso-a-mysql-desde-el-host)
- [Resetear la base local (destruye datos)](#resetear-la-base-local-destruye-datos)
- [Troubleshooting](#troubleshooting)
- [Limitaciones y seguridad](#limitaciones-y-seguridad)

## Resumen

| Pieza | Qué es |
| --- | --- |
| `backend` | Contenedor PHP 8.5 + Laravel 13. Sirve la API con `php artisan serve` en `http://127.0.0.1:8000` |
| `mysql` | MySQL 8.4. Bases `sportiva_platform` (central) y `sportiva_testing` (tests). No se expone fuera de Docker |
| `mysql-data` | Volumen de Docker con los datos de MySQL. Sobrevive a reinicios y rebuilds |
| `.env` | Configuración local (la copia `setup.sh` desde `.env.example`). La leen Compose y Laravel |
| `scripts/setup.sh` | Deja todo levantado y migrado. Es idempotente: se puede correr siempre que haya dudas |

Fuente de verdad: Git tiene el código, `compose.yaml` y `docker/` definen el entorno, el `.env` tiene la configuración local y el volumen tiene los datos.

## Opción 1: setup automático en Windows (recomendado)

Requisitos: Windows 10/11 y Git for Windows (si ya lo tenés, no lo reinstales).

1. Descargá el script. El repo es privado, así que `irm <url> | iex` no funciona. Usá una de estas opciones:
   - Con GitHub CLI ya logueado (`gh auth login`), en PowerShell:

     ```powershell
     $token = gh auth token
     Invoke-WebRequest -Uri https://api.github.com/repos/finalproyectogrupo8-max/API_Sportiva/contents/scripts/setup-windows.ps1 `
       -Headers @{ Authorization = "Bearer $token"; Accept = "application/vnd.github.raw" } -OutFile setup-windows.ps1
     ```

   - O desde GitHub: abrí `scripts/setup-windows.ps1`, botón **Raw** y guardalo como `setup-windows.ps1`.

2. **Leelo antes de ejecutarlo.** Es un script que instala software: no ejecutes scripts que no revisaste.
3. Ejecutalo. `-ExecutionPolicy Bypass` aplica solo a este proceso, no cambia la configuración de tu sistema:

   ```powershell
   powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1
   ```

El script:

1. Verifica WSL 2 con Ubuntu. Si falta, ofrece instalarlo (`wsl --install -d Ubuntu`). Necesita PowerShell **como administrador** y, quizás, reiniciar. Después abrí Ubuntu desde el menú Inicio, creá tu usuario de Linux y volvé a correr el script.
2. Verifica Docker Desktop. Si falta, ofrece instalarlo con `winget`. Después abrilo una vez, aceptá los términos y volvé a correr el script.
3. Verifica que `docker` funcione **dentro de Ubuntu**. Si no funciona, te dice cómo activar la integración (ver [Troubleshooting](#docker-command-not-found-dentro-de-ubuntu)).
4. Verifica que git en WSL tenga acceso al repo. Si no tiene, ofrece usar el Git Credential Manager de Windows (el mismo login que ya usás en Windows).
5. Clona el repo en `~/repos/api-sportiva` **dentro de WSL**.
6. Corre `scripts/setup.sh` y abre VS Code en modo Remote WSL.

Todo lo que instala o cambia configuración global pregunta antes. Con `-Yes` acepta todo. Parámetros útiles: `-Distro Ubuntu-24.04`, `-TargetDir repos/otro-nombre`, `-NoOpen`.

> **¿Por qué dentro de WSL y no en `C:\repos`?** Docker monta el código en el contenedor. Desde `C:\` ese montaje cruza del sistema de archivos de Windows al de Linux y es **muy lento** con los miles de archivos de `vendor/`, además de tener permisos emulados. Desde WSL es nativo. Para editar, usá VS Code con la extensión **WSL** (`code .` desde la terminal de Ubuntu). En el Explorador de Windows, los archivos aparecen en `\\wsl$\Ubuntu\home\<usuario>\repos\api-sportiva`.

## Opción 2: paso a paso (flujo A, build local)

Es lo mismo que hace el script, a mano.

### 1. Instalar WSL 2 y Docker Desktop (una vez por máquina)

En PowerShell **como administrador**:

```powershell
wsl --install -d Ubuntu          # puede pedir reiniciar
winget install -e --id Docker.DockerDesktop
```

- Abrí **Ubuntu** desde el menú Inicio y creá tu usuario de Linux.
- Abrí **Docker Desktop**, aceptá los términos y esperá a que diga *Engine running*. Docker Desktop usa el backend WSL 2 por defecto.
- En Docker Desktop: **Settings → Resources → WSL integration** → activá **Ubuntu** → *Apply & restart*.

Verificá en PowerShell (no hace falta admin):

```powershell
docker --version
docker compose version
wsl -d Ubuntu -- docker version
```

> Si `docker` no se encuentra justo después de instalar, cerrá y volvé a abrir la terminal (o VS Code): el PATH se actualiza al abrir una sesión nueva.

### 2. Clonar dentro de WSL

En la terminal de **Ubuntu**:

```bash
mkdir -p ~/repos && cd ~/repos
git clone https://github.com/finalproyectogrupo8-max/API_Sportiva.git api-sportiva
cd api-sportiva
```

Si `git clone` pide credenciales y no las tenés en WSL, podés reutilizar las de Windows:

```bash
git config --global credential.helper "/mnt/c/Program\ Files/Git/mingw64/bin/git-credential-manager.exe"
```

### 3. Levantar todo

```bash
bash scripts/setup.sh
```

Termina mostrando `{"status":"ok","checks":{"database":"ok"}}`. Lo que hace, por si lo querés hacer a mano:

```bash
cp -n .env.example .env                                  # no pisa un .env existente
export HOST_UID=$(id -u) HOST_GID=$(id -g)
docker compose build backend                             # imagen con tu UID/GID
docker compose up -d --wait mysql                        # espera el healthcheck de MySQL
docker compose run --rm --no-deps backend composer install
docker compose run --rm --no-deps backend php artisan key:generate   # solo si APP_KEY está vacía
docker compose up -d --wait
docker compose exec backend php artisan migrate
```

### 4. Correr los tests

```bash
docker compose exec backend php artisan test
docker compose exec backend vendor/bin/pint --test       # lint (estilo de código)
```

Los tests usan la base `sportiva_testing`, nunca `sportiva_platform`.

## Flujo B: usar una imagen publicada (futuro)

> **Todavía no hay imágenes publicadas.** La publicación en GitHub Container Registry (GHCR) es un change futuro que necesita aprobación. Esto describe cómo va a funcionar.

La imagen va a ser `ghcr.io/finalproyectogrupo8-max/api_sportiva`, con tags `sha-<commit>` (cada merge a `main`) y `vX.Y.Z` (releases). Va a servir para **desarrollo y CI**, no para producción: Laravel Cloud compila desde Git.

```bash
# 1. Login (el package es privado). Token de GitHub con el permiso read:packages.
echo "<TOKEN>" | docker login ghcr.io -u <tu-usuario> --password-stdin

# 2. Hacé checkout del MISMO commit que la imagen: compose.yaml, migraciones y .env.example tienen que coincidir.
git checkout <commit>

# 3. Bajar la imagen y darle el nombre que espera compose.yaml.
docker pull ghcr.io/finalproyectogrupo8-max/api_sportiva:sha-<commit>
docker tag ghcr.io/finalproyectogrupo8-max/api_sportiva:sha-<commit> api-sportiva-dev:local

# 4. Inicializar SIN build (setup.sh siempre construye, así que acá van los pasos a mano).
cp -n .env.example .env
docker compose up -d --no-build --wait mysql
docker compose run --rm --no-deps backend composer install
docker compose run --rm --no-deps backend php artisan key:generate   # solo si APP_KEY está vacía
docker compose up -d --no-build --wait
docker compose exec backend php artisan migrate
```

- La imagen sola **no alcanza**: también necesitás el `compose.yaml`, las migraciones y el `.env.example` del mismo commit.
- La imagen publicada usa UID/GID 1000 (el primer usuario de WSL Ubuntu). Si tu `id -u` es otro, vas a tener errores de permisos: usá el flujo A.

## Uso diario

Todos los comandos se corren desde la raíz del repo (en WSL, en `~/repos/api-sportiva`).

| Tarea | Comando |
| --- | --- |
| Levantar | `docker compose up -d` |
| Estado | `docker compose ps` |
| Logs (seguir) | `docker compose logs -f backend` (o `mysql`) |
| Entrar al contenedor | `docker compose exec backend bash` |
| Artisan | `docker compose exec backend php artisan <comando>` |
| Composer | `docker compose exec backend composer <comando>` |
| Migrar | `docker compose exec backend php artisan migrate` |
| Tests | `docker compose exec backend php artisan test` |
| Lint | `docker compose exec backend vendor/bin/pint --test` (sin `--test` corrige) |
| Logs de Laravel | `docker compose exec backend tail -f storage/logs/laravel.log` |
| Detener (conserva datos) | `docker compose stop` o `docker compose down` |
| Reconstruir la imagen | `HOST_UID=$(id -u) HOST_GID=$(id -g) docker compose build backend` (o `bash scripts/setup.sh`) |

Usá la **terminal de Ubuntu** (o la terminal integrada de VS Code en modo WSL). Desde PowerShell, entrá primero a WSL: `wsl -d Ubuntu --cd ~/repos/api-sportiva`.

`docker compose down` **conserva** los datos: borra los contenedores y la red, no el volumen.

## Acceso a MySQL desde el host

MySQL no publica puertos por defecto. Para conectarte con DBeaver u otro cliente:

```bash
cp compose.override.example.yaml compose.override.yaml
docker compose up -d
```

Conexión: host `127.0.0.1`, puerto `3310` (cambialo con `DB_FORWARD_PORT` en `.env`), usuario y contraseña de `DB_USERNAME`/`DB_PASSWORD`. El puerto queda atado a `127.0.0.1`: no es accesible desde otras máquinas de la red. `compose.override.yaml` está en `.gitignore`. Para cerrarlo, borrá el archivo y corré `docker compose up -d`.

## Resetear la base local (destruye datos)

> ⚠️ **Esto borra TODOS los datos de tu MySQL local** (`sportiva_platform` y `sportiva_testing`) y no se puede deshacer. No afecta a nadie más: el volumen solo existe en tu máquina.

Opciones, de menos a más destructiva:

```bash
# Recrear solo las tablas de la base central (borra sus datos):
docker compose exec backend php artisan migrate:fresh

# Borrar el volumen completo (vuelve a correr los scripts de docker/mysql/init/):
docker compose down -v        # -v BORRA el volumen mysql-data
bash scripts/setup.sh
```

Nunca uses `docker compose down -v` como forma "normal" de bajar el entorno: para eso es `docker compose down` (sin `-v`).

## Troubleshooting

### `docker: command not found` dentro de Ubuntu

Falta la integración de Docker Desktop con la distro: **Settings → Resources → WSL integration** → activá **Ubuntu** → *Apply & restart*. Después, en PowerShell: `wsl --terminate Ubuntu` y abrí Ubuntu de nuevo.

### `docker` no se encuentra en PowerShell o VS Code

Si recién instalaste Docker Desktop, cerrá y volvé a abrir la terminal o VS Code. Si sigue, verificá que Docker Desktop esté abierto (*Engine running*).

### Puerto ocupado (`port is already allocated` / `bind: address already in use`)

Algo de tu máquina ya usa el puerto. Cambialo en `.env` y volvé a levantar:

- API: `APP_PORT=8001` (y `APP_URL=http://localhost:8001`).
- MySQL (solo con el override): `DB_FORWARD_PORT=3311`.

Para ver quién lo usa, en PowerShell: `netstat -ano | findstr :8000` y después `tasklist /FI "PID eq <pid>"`.

### Errores de permisos (`Permission denied` en `storage/` o `vendor/`)

El usuario del contenedor tiene que tener el mismo UID que tu usuario de WSL. `setup.sh` lo hace solo. Si construiste la imagen de otra forma:

```bash
HOST_UID=$(id -u) HOST_GID=$(id -g) docker compose build backend
docker compose up -d
```

Si el repo está en `C:\` en lugar de WSL, los permisos son emulados y pueden fallar: cloná dentro de WSL.

### La API no arranca o dice que no puede conectarse a MySQL

- `docker compose ps`: `mysql` tiene que estar `healthy`. La primera vez tarda ~20-30 s en inicializarse.
- `docker compose logs mysql` para ver errores.
- Revisá que `.env` tenga `DB_HOST=mysql` (el nombre del servicio, **no** `localhost` ni `127.0.0.1`).
- Si cambiaste `DB_USERNAME`/`DB_PASSWORD` **después** de la primera inicialización, MySQL no se entera: los scripts de init solo corren con el volumen vacío. Volvé a los valores originales o [reseteá la base](#resetear-la-base-local-destruye-datos).

### `sportiva_testing` no existe (los tests fallan con `Unknown database`)

El volumen se creó antes de que existiera el script de init (o con otra configuración). La app no usa root: su contraseña es aleatoria y solo aparece una vez en los logs de la primera inicialización (`GENERATED ROOT PASSWORD`). La solución es [resetear la base](#resetear-la-base-local-destruye-datos): al recrear el volumen, el script de init vuelve a correr.

### Un valor del `.env` con `$` se rompe

Compose interpreta `$` como variable. Escribilo como `$$`.

### Todo anda muy lento

Seguramente el repo está en `C:\`. Cloná dentro de WSL ([por qué](#opción-1-setup-automático-en-windows-recomendado)).

## Limitaciones y seguridad

- **`php artisan serve` no es un servidor de producción.** Corre 4 workers (`PHP_CLI_SERVER_WORKERS`), sin TLS, y no sirve para medir rendimiento. Producción usa el runtime de Laravel Cloud.
- **El entorno local no es igual a producción.** Las versiones de PHP y MySQL se alinean a mano con Laravel Cloud (ver [ADR 0001](adr/0001-stack-y-arquitectura.md)).
- **Docker no reemplaza la seguridad de la aplicación.** Aislar procesos en contenedores no da autenticación, autorización, aislamiento entre clubes (tenants) ni una red segura hacia la base de datos. Todo eso es responsabilidad de la API y de la infraestructura de producción.
- La contraseña de MySQL de `.env.example` es **solo para desarrollo**. Producción configura sus credenciales en Laravel Cloud y nunca se versionan.
- `APP_DEBUG=true` es solo para local. Fuera de local tiene que ser `false`.
- La imagen no contiene el código ni el `.env`: el código se monta desde tu clon.
