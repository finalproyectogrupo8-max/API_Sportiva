# Desarrollo local

Cómo levantar la API Sportiva en tu máquina. **No hace falta instalar PHP, Composer ni MySQL**: todo corre en Docker.

- [Resumen](#resumen)
- [Opción 1: setup automático en Windows (recomendado)](#opción-1-setup-automático-en-windows-recomendado)
- [Opción 2: paso a paso (flujo A, build local)](#opción-2-paso-a-paso-flujo-a-build-local)
- [Flujo B: usar una imagen publicada (futuro)](#flujo-b-usar-una-imagen-publicada-futuro)
- [Uso diario](#uso-diario)
- [Documentación de la API (Swagger)](#documentación-de-la-api-swagger)
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

Requisitos: Windows 10 (22H2) u 11, con winget (viene con Windows). No hace falta nada más instalado: el script instala WSL, Ubuntu, Docker Desktop, Git for Windows y VS Code si faltan.

Abrí **PowerShell normal**, no "como administrador", y corré:

```powershell
irm https://raw.githubusercontent.com/finalproyectogrupo8-max/API_Sportiva/main/scripts/setup-windows.ps1 | iex
```

> **Correlo como usuario normal.** El script pide permisos de administrador **solo** para activar WSL: vas a ver un aviso de UAC una vez. Si lo corrés entero como administrador, WSL y Docker pueden quedar configurados para otro contexto que no es el tuyo. En ese caso el script te avisa.

`irm | iex` ejecuta lo que esté en `main` en ese momento, sin que lo leas. Si preferís revisarlo antes, bajalo, leelo y ejecutalo como archivo:

```powershell
irm https://raw.githubusercontent.com/finalproyectogrupo8-max/API_Sportiva/main/scripts/setup-windows.ps1 -OutFile setup-windows.ps1
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1
```

`-ExecutionPolicy Bypass` aplica solo a ese proceso y no cambia la configuración de tu sistema. Para fijar una versión, reemplazá `main` por un tag o un commit en la URL.

### Qué hace solo y qué te va a preguntar

| Paso | Qué hace el script | Qué tenés que hacer vos |
| --- | --- | --- |
| 1. WSL 2 | Lo activa si falta | Aceptar el **UAC**, una sola vez. Si Windows pide reiniciar, aceptar: **el script sigue solo** cuando volvés a iniciar sesión |
| 2. Ubuntu | Lo instala sin la ventana de primer uso y lo deja como distro predeterminada | Elegir **usuario y contraseña de Linux**. La contraseña es para `sudo` |
| 3. Docker Desktop | Lo instala **por usuario**, sin admin y con la licencia aceptada. Después lo arranca y espera a que el motor responda | Nada |
| 4. Docker dentro de Ubuntu | Si Ubuntu no ve `docker`, lo repara: reinicia la distro (`wsl --terminate`) y, si hace falta, Docker Desktop | Confirmar los reinicios |
| 5. Git en WSL | Configura el acceso al repo con el Git Credential Manager de Windows, y tu identidad con el email noreply de GitHub | Escribir tu usuario de GitHub, si te lo pide |
| 6. Proyecto | Clona en `~/repos/api-sportiva` **dentro de WSL** y corre `scripts/setup.sh` | Nada |
| 7. VS Code | Lo instala si falta, agrega la extensión **WSL** y abre el proyecto | Aceptar las **extensiones recomendadas** cuando VS Code las ofrezca |

Todo lo que instala o cambia configuración pregunta antes. Con `-Yes` acepta todo.

### Parámetros útiles

| Parámetro | Para qué |
| --- | --- |
| `-WhatIf` | **Simulación:** muestra qué instalaría o cambiaría, sin tocar nada |
| `-Yes` | Acepta todas las confirmaciones |
| `-GitHubUser <usuario>` | Tu usuario de GitHub, para no tener que escribirlo. Es obligatorio con `-Yes` |
| `-Distro <nombre>` | Otra distro de WSL. Por defecto es `Ubuntu` |
| `-TargetDir <ruta>` | Otra carpeta dentro de WSL. Por defecto es `repos/api-sportiva` |
| `-NoOpen` | No abrir VS Code al final |

Con `irm`, los parámetros se pasan así:

```powershell
& ([scriptblock]::Create((irm https://raw.githubusercontent.com/finalproyectogrupo8-max/API_Sportiva/main/scripts/setup-windows.ps1))) -WhatIf
```

### Si algo falla

- Leé el mensaje, resolvé lo que pide y **volvé a correr el mismo comando**. El script retoma desde donde quedó, sin repetir lo que ya está hecho, y la ventana de PowerShell no se cierra.
- Cada corrida deja un **log** en `%TEMP%\api-sportiva-setup-<fecha>.log`, y el script muestra la ruta al final. Si pedís ayuda, pasá ese archivo: no contiene contraseñas.

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
| Regenerar el contrato OpenAPI | `docker compose exec backend php artisan scramble:export` |
| Logs de Laravel | `docker compose exec backend tail -f storage/logs/laravel.log` |
| Detener (conserva datos) | `docker compose stop` o `docker compose down` |
| Reconstruir la imagen | `HOST_UID=$(id -u) HOST_GID=$(id -g) docker compose build backend` (o `bash scripts/setup.sh`) |

Usá la **terminal de Ubuntu** (o la terminal integrada de VS Code en modo WSL). Desde PowerShell, entrá primero a WSL: `wsl -d Ubuntu --cd ~/repos/api-sportiva`.

`docker compose down` **conserva** los datos: borra los contenedores y la red, no el volumen.

## Documentación de la API (Swagger)

Con el entorno levantado, abrí **http://127.0.0.1:8000/api/docs**: Swagger UI con todos los endpoints. "Try it out" viene activo, así que podés ejecutar requests reales contra tu API local.

| Ruta | Qué es |
| --- | --- |
| `/api/docs` | Swagger UI |
| `/api/docs/openapi.json` | El contrato OpenAPI 3.1 en JSON |

El contrato lo genera **Scramble** a partir del código: rutas, Form Requests y API Resources. No se escribe a mano. Por eso conviene validar con Form Requests y responder con API Resources: así Scramble infiere los tipos.

### El contrato versionado (`docs/api/openapi.json`)

El contrato también se versiona en `docs/api/openapi.json`. Desde ese archivo el frontend genera sus tipos de TypeScript, y en cada PR muestra cómo cambia la API.

**Si cambiás un endpoint** (una ruta, un Form Request o un Resource), regenerá el contrato y commitealo junto con tu cambio:

```bash
docker compose exec backend php artisan scramble:export
git add docs/api/openapi.json
```

La CI regenera el contrato y **falla si el archivo commiteado quedó desactualizado** (paso *Contrato OpenAPI al día*).

No uses `php artisan scramble:cache` en local: si existe ese caché, el export podría usar un contrato viejo. Si ya lo corriste, limpialo con `php artisan scramble:clear`.

### Quién puede ver la documentación

| Entorno (`APP_ENV`) | Acceso |
| --- | --- |
| `local` | Abierta |
| `staging` | HTTP Basic con `API_DOCS_USERNAME` / `API_DOCS_PASSWORD` (variables del entorno en Laravel Cloud). Si falta alguna, responde 404 |
| `production` y cualquier otro | 404, como si no existiera |

En producción las rutas de documentación ni siquiera se registran. **Ningún entorno desplegado tiene que usar `APP_ENV=local`**, porque eso abre la documentación sin credenciales.

Swagger UI se carga desde jsDelivr con versión fija e integridad SRI (ver "Swagger UI fijado" en [decisions.md](decisions.md)). Necesita internet para mostrarse; el contrato JSON no.

## Acceso a MySQL desde el host

MySQL **no publica puertos por defecto**: la API no los necesita (ver abajo), y así la base no queda expuesta. Para entrar con DBeaver u otro cliente, hay que activar el override.

### Activar y desactivar

```bash
# Activar (WSL / Linux / macOS)
cp compose.override.example.yaml compose.override.yaml
docker compose up -d

# Desactivar
rm compose.override.yaml
docker compose up -d
```

En PowerShell: `Copy-Item compose.override.example.yaml compose.override.yaml` y `Remove-Item compose.override.yaml`.

`compose.override.yaml` está en `.gitignore`: es solo tuyo. Docker Compose lo combina automáticamente con `compose.yaml`.

### Qué hace el mapeo de puertos

El override agrega esta línea al servicio `mysql`:

```yaml
ports:
  - "127.0.0.1:${DB_FORWARD_PORT:-3310}:3306"
```

Se lee de izquierda a derecha:

| Parte | Valor | Significado |
| --- | --- | --- |
| IP del host | `127.0.0.1` | Solo escucha en tu propia máquina, no en la red |
| Puerto del host | `3310` | El puerto que abre tu máquina. Sale de `DB_FORWARD_PORT` en `.env`; si no está, vale 3310 |
| Puerto del contenedor | `3306` | Donde escucha MySQL **dentro** del contenedor |

Es decir: el puerto **3310 de tu máquina** reenvía al **3306 del contenedor**.

```
DBeaver (Windows)
   │  127.0.0.1:3310
   ▼
Docker Desktop  ── reenvía ──▶  contenedor "mysql" :3306   (MySQL 8.4 del proyecto)

API (contenedor "backend") ── red interna de Docker ──▶  mysql:3306   (no usa el 3310)
```

- **Por qué 3310 y no 3306:** dos procesos no pueden escuchar en el mismo puerto, y en muchas máquinas el 3306 (o el 3307) ya lo ocupa un MySQL instalado en Windows (XAMPP o MySQL Server). Si el 3310 también está ocupado, cambiá `DB_FORWARD_PORT` en `.env`.
- **La API no pasa por acá:** el contenedor `backend` se conecta por la red interna de Docker como `mysql:3306` (por eso `DB_HOST=mysql`). La API funciona igual con el override o sin él: el override existe solo para que vos entres desde el host.
- **Por qué `127.0.0.1:` adelante:** con solo `"3310:3306"`, Docker escucha en **todas las interfaces** (`0.0.0.0`), y cualquier máquina de tu red (por ejemplo, el Wi-Fi de la facultad) podría intentar conectarse a tu base. Con `127.0.0.1`, el puerto solo existe para tu máquina.

Para ver qué está escuchando en cada puerto (PowerShell): `netstat -ano | findstr ":3306 :3307 :3310"`. Si aparece en `0.0.0.0`, es accesible desde la red; si aparece en `127.0.0.1`, solo desde tu máquina.

### Conectarse con DBeaver

1. **Nueva conexión → MySQL**. En la pestaña **Main**:

   | Campo | Valor |
   | --- | --- |
   | Server Host | `127.0.0.1` |
   | Port | **`3310`** (no 3306: ese es otro MySQL, si tenés uno instalado en Windows) |
   | Database | `sportiva_platform` (la de tests es `sportiva_testing`) |
   | Username / Password | `DB_USERNAME` / `DB_PASSWORD` de tu `.env` |

2. En la pestaña **Driver properties**: `allowPublicKeyRetrieval` = `true` y `useSSL` = `false`.

   MySQL 8 autentica con `caching_sha2_password`. Sin SSL, el driver JDBC necesita pedirle al servidor su clave pública para cifrar la contraseña, y por seguridad lo tiene bloqueado (de ahí el error *"Public Key Retrieval is not allowed"*). Habilitarlo es aceptable **solo** contra tu base local en `127.0.0.1`: contra una base remota, usá SSL. La API no tiene este problema, porque usa otro driver (PDO).

3. **Test Connection**.

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

`setup-windows.ps1` lo intenta reparar solo. Si lo resolvés a mano, probá en este orden:

1. Que Ubuntu sea la distro predeterminada, porque Docker se integra con esa: `wsl --set-default Ubuntu`.
2. Reiniciar la distro: `wsl --terminate Ubuntu`, y abrir Ubuntu de nuevo. Suele alcanzar con esto.
3. Reiniciar Docker Desktop: `docker desktop restart`, o cerrarlo y abrirlo. Después esperá hasta un minuto y medio: la integración tarda en reconectarse.
4. Reiniciar WSL completo: `wsl --shutdown`, y después `docker desktop restart`. Hace falta cuando la carpeta compartida entre distros (`/mnt/wsl`) queda desincronizada; pasa, por ejemplo, después de instalar o borrar otra distro. El log de Docker Desktop lo muestra como *"waiting ... for the backend socket"*. Ojo: detiene **todas** las distros.
5. Activar la integración a mano: **Settings → Resources → WSL integration** → activá **Ubuntu** → *Apply & restart*. Después, `wsl --terminate Ubuntu`.

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

### DBeaver: "Communications link failure" / "Connection refused" en el 3310

MySQL no está publicando el puerto. `compose.override.yaml` es local (está en `.gitignore`), así que **cada clon tiene que tener el suyo**: si te pasaste a un clon nuevo (por ejemplo, a WSL), no está. Activalo en el clon que estás usando, como explica [Acceso a MySQL desde el host](#acceso-a-mysql-desde-el-host). Para comprobarlo:

```bash
docker compose ps    # mysql tiene que mostrar 127.0.0.1:3310->3306/tcp
```

### DBeaver: "Public Key Retrieval is not allowed"

Lo da el driver JDBC de MySQL 8. Antes de tocar nada, revisá **a qué puerto** te estás conectando:

- **El 3306 no es el MySQL de Docker.** Si tenés un MySQL instalado en Windows (XAMPP o MySQL Server), te estás conectando a ese. El de Docker no expone puertos por defecto: activalo como explica [Acceso a MySQL desde el host](#acceso-a-mysql-desde-el-host) y usá el **3310**.
- En la conexión de DBeaver, pestaña **Driver properties**, poné `allowPublicKeyRetrieval` = `true` y `useSSL` = `false`. Es seguro solo porque es tu base local en `127.0.0.1`: no lo uses contra bases remotas.

### Un valor del `.env` con `$` se rompe

Compose interpreta `$` como variable. Escribilo como `$$`.

### La CI falla en "Contrato OpenAPI al día"

Cambiaste un endpoint sin regenerar el contrato. Corré `docker compose exec backend php artisan scramble:export` y commiteá `docs/api/openapi.json`.

### `/api/docs` no muestra nada (página en blanco)

Swagger UI se carga desde jsDelivr: revisá tu conexión. Si en la consola del browser aparece un error de *integrity*, el archivo del CDN no coincide con el hash fijado (ver "Swagger UI fijado" en [decisions.md](decisions.md)). Mientras tanto, el contrato sigue disponible en `/api/docs/openapi.json`.

### Todo anda muy lento

Seguramente el repo está en `C:\` (o en `/mnt/c/...` desde WSL). `scripts/setup.sh` lo detecta y lo avisa con un mensaje `!! ATENCION`. Esto lo medimos el 2026-10-16 con `/api/health`, pidiéndolo desde Windows:

| Ubicación del repo | Tiempo por request |
| --- | --- |
| `C:\...` (filesystem de Windows) | 0,2 s, con picos de 2,1-2,3 s (la primera vez que el request cae en cada worker) |
| `~/...` dentro de WSL | 0,03 s |

Cloná dentro de WSL con `scripts/setup-windows.ps1` ([por qué](#opción-1-setup-automático-en-windows-recomendado)). Antes de pasarte, commiteá y pusheá tus cambios, y bajá el entorno viejo con `docker compose down` (**sin** `-v`). El clon de WSL usa el mismo volumen de MySQL (`api-sportiva_mysql-data`), así que tus datos locales se mantienen. OPcache no ayuda en este caso: desde `C:\` lo probamos y fue más lento.

## Limitaciones y seguridad

- **`php artisan serve` no es un servidor de producción.** Corre 4 workers (`PHP_CLI_SERVER_WORKERS`), sin TLS, y no sirve para medir rendimiento. Producción usa el runtime de Laravel Cloud.
- **El entorno local no es igual a producción.** Las versiones de PHP y MySQL se alinean a mano con Laravel Cloud (ver [ADR 0001](adr/0001-stack-y-arquitectura.md)).
- **Docker no reemplaza la seguridad de la aplicación.** Aislar procesos en contenedores no da autenticación, autorización, aislamiento entre clubes (tenants) ni una red segura hacia la base de datos. Todo eso es responsabilidad de la API y de la infraestructura de producción.
- La contraseña de MySQL de `.env.example` es **solo para desarrollo**. Producción configura sus credenciales en Laravel Cloud y nunca se versionan.
- `APP_DEBUG=true` es solo para local. Fuera de local tiene que ser `false`.
- La imagen no contiene el código ni el `.env`: el código se monta desde tu clon.
