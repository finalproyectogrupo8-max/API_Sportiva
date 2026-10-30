# Actualizar tu entorno después de un pull

Qué hacer cuando traés cambios de `main` (API y front) y qué hacer si algo no levanta. Para el setup desde cero, ver [desarrollo-local.md](desarrollo-local.md).

Todos los comandos de la API se corren en la terminal de Ubuntu (WSL), desde la raíz del repo de la API.

> ⚠️ No uses `docker compose down -v` salvo que quieras **borrar** tu base local. Los casos de esta guía que lo necesitan lo avisan.

## 1. API: el camino corto

```bash
git checkout main
git pull
bash scripts/setup.sh
```

`setup.sh` no borra datos y se puede correr las veces que haga falta. Rearma la imagen (por ejemplo, cuando cambia la versión de PHP), instala las dependencias de Composer con un contenedor temporal, levanta todo y migra la base central.

Después:

1. **Revisá tu `.env`** contra `.env.example`: cada variable nueva de `.env.example` tiene que estar en tu `.env`. Para el alta de clubes, `QUEUE_CONNECTION=database`: el servicio `queue` (lo levanta `docker compose up -d`) crea la base en segundo plano. Si cambiaste algo:

   ```bash
   docker compose exec backend php artisan config:clear
   ```

2. **Si ya tenías clubes creados**, aplicales las migraciones de club nuevas:

   ```bash
   docker compose exec backend php artisan tenants:migrate
   ```

3. **Verificá:**

   ```bash
   docker compose exec backend php artisan test
   ```

   Tienen que pasar todos. Si fallan los de club con `Access denied`, ver [el caso del permiso](#crear-un-club-o-los-tests-de-club-fallan-con-1044-access-denied).

## 2. Front: el camino corto

Desde la raíz del repo del front:

```bash
git checkout main
git pull
pnpm install                    # siempre después de un pull: puede haber dependencias nuevas
cp .env.example .env.local      # solo si todavía no tenés .env.local
pnpm dev
```

`.env.local` tiene que tener `API_URL=http://127.0.0.1:8000/api`, con la API levantada.

## 3. Usuarios para entrar

El login es contra la API: `admin` / `admin` ya no existe. Los usuarios viven en **tu** base local (un usuario creado en la máquina de otro dev no existe en la tuya), así que cada dev crea los suyos. Hasta que esté el panel de admin (FRO-5 a FRO-7), se crean por consola o por la API.

### Admin de plataforma

```bash
docker compose exec backend php artisan platform:create-admin tu@email.com
```

Te pide nombre y una contraseña de al menos 12 caracteres. Con ese usuario entrás en http://localhost:3000 y caés en `/admin`.

### Usuario de club (para entrar a la parte de clubes)

Un usuario común, sin permisos de plataforma:

```bash
docker compose exec backend php artisan tinker --execute='
$u = App\Models\User::firstOrNew(["email" => "socio.demo@sportiva.test"]);
$u->name = "Socio Demo";
$u->password = "clave-socio-demo-12";
$u->is_active = true;
$u->save();
echo "ok";'
```

Entrás con `socio.demo@sportiva.test` / `clave-socio-demo-12` y caés en `/clubes` ("Hola, Socio Demo"). Para otro usuario, cambiá email, nombre y clave (12 caracteres o más). Si el email ya existe, el comando le pisa el nombre y la clave.

> **Estado (24/10/2026):** `/clubes` todavía muestra los clubes de ejemplo y deja entrar a cualquiera, así que el usuario no necesita estar asociado a un club. Cuando esté FRO-3, `/clubes` va a mostrar solo los clubes del usuario: ahí hay que [asociarlo a un club](#asociar-un-usuario-a-un-club).

### Asociar un usuario a un club

Se hace con la API, usando el **token de plataforma** del admin. La forma más fácil es Swagger, en http://127.0.0.1:8000/api/docs:

1. En `POST /api/auth/login` → **Try it out**, mandá el email y la clave del admin. Copiá **`platform_token`** (no `token`).
2. Tocá **Authorize** y pegá el `platform_token`.
3. **Crear el club** (si no existe): `POST /api/admin/tenants` con `{ "name": "Club Demo", "slug": "demo" }`. Responde `"status": "provisioning"` y en unos segundos el worker lo pasa a `active` (`GET /api/admin/tenants/{id}`). Anotá el `id`.
4. **Crear el usuario** (si no lo creaste con tinker): `POST /api/admin/users` con `{ "name": "Socio Demo", "email": "socio@demo.com", "password": "clave-socio-12" }`. Anotá el `id`.
5. **Asociarlo:** `POST /api/admin/tenants/{id del club}/memberships` con `{ "user_id": <id del usuario>, "roles": ["admin"] }`. Los roles son los del club: los lista `GET /api/admin/tenants/{id del club}/roles` (`admin`, `coordinator`, `coach`, `physical_trainer`, `inventory_manager`, `maintenance_manager` y los custom). Ver [roles y permisos](roles-y-permisos.md).

Lo mismo con curl:

```bash
# Token del admin: copiá "platform_token" de la respuesta
curl -s -X POST http://127.0.0.1:8000/api/auth/login -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@email.com","password":"clave-del-admin"}'

PLAT='<platform_token>'

# Club
curl -s -X POST http://127.0.0.1:8000/api/admin/tenants -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $PLAT" -d '{"name":"Club Demo","slug":"demo"}'

# Usuario
curl -s -X POST http://127.0.0.1:8000/api/admin/users -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $PLAT" -d '{"name":"Socio Demo","email":"socio@demo.com","password":"clave-socio-12"}'

# Membresía (con los ids de las respuestas anteriores)
curl -s -X POST http://127.0.0.1:8000/api/admin/tenants/<id_club>/memberships -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $PLAT" -d '{"user_id":<id_usuario>,"roles":["admin"]}'
```

Para confirmar: el login de ese usuario tiene que traer `"tenants": [{ "slug": "demo", ... }]`, y `select-tenant` su `profile` con los roles y sus `permissions`.

**Después de este pull** (si venías de antes de los roles del club): corré `docker compose exec backend php artisan migrate` y `docker compose exec backend php artisan tenants:migrate`. El segundo siembra los roles y permisos en tus clubes locales y deja como **Administrador del club** a los usuarios que ya tenían membresía.

| Error | Causa |
|---|---|
| 403 `forbidden` | Usaste `token` en vez de `platform_token` |
| 401 | Token inválido o vencido (el de plataforma dura 8 h): volvé a loguearte |
| 422 en `slug` | Solo minúsculas, números y `_` (sin guiones), máximo 50 |
| 422 en `password` | Menos de 12 caracteres |
| 422 en `email` | Ya existe un usuario con ese email |

## 4. Ver la base con DBeaver (u otro visor)

MySQL no publica puertos por defecto. Para entrar desde tu máquina hay que activar el override, que **no se commitea** (`compose.override.yaml` está en `.gitignore`): cada dev lo crea copiando el ejemplo.

```bash
cp compose.override.example.yaml compose.override.yaml    # PowerShell: Copy-Item compose.override.example.yaml compose.override.yaml
docker compose up -d
```

MySQL queda en `127.0.0.1:3310`, solo para tu máquina. Para desactivarlo: `rm compose.override.yaml` y `docker compose up -d`.

**Datos de conexión** (los de `.env.example`; si los cambiaste, valen los de tu `.env`: `grep "^DB_" .env`):

| Campo | Valor |
|---|---|
| Host | `127.0.0.1` |
| Puerto | **`3310`** (no 3306) |
| Base | `sportiva_platform` (tests: `sportiva_testing`; clubes: `club_<slug>`) |
| Usuario | `sportiva` |
| Contraseña | `sportiva_dev_password` |

No uses `root`: su contraseña es aleatoria y nadie la conoce. Tampoco hace falta.

**En DBeaver**, en la pestaña **Driver properties** de la conexión:

- `allowPublicKeyRetrieval` = `true`
- `useSSL` = `false`

Si alguna no aparece, se agrega con clic derecho → **Add new property**. Sin esto, DBeaver da "Public Key Retrieval is not allowed" ([ver abajo](#dbeaver-public-key-retrieval-is-not-allowed)).

Detalle de cómo funciona el mapeo de puertos: [desarrollo-local.md](desarrollo-local.md#acceso-a-mysql-desde-el-host).

## 5. Problemas y soluciones

### El backend no levanta: `Class "Laravel\Sanctum\Sanctum" not found`

`docker compose ps` muestra el backend en `Exited (1)` y los logs dicen `Class "Laravel\Sanctum\Sanctum" not found` (o el de otro paquete).

**Causa:** tu carpeta `vendor/` es anterior a una dependencia nueva. Laravel la intenta cargar al arrancar, falla y el contenedor se cae. Por eso `docker compose exec backend composer install` tampoco funciona: no hay contenedor corriendo.

**Solución:** instalar con un contenedor temporal, que no arranca el servidor:

```bash
bash scripts/setup.sh
```

O a mano:

```bash
docker compose run --rm --no-deps backend composer install
docker compose up -d
docker compose exec backend php artisan migrate
```

### Crear un club o los tests de club fallan con `1044 Access denied`

Error típico: `SQLSTATE[HY000] [1044] Access denied for user 'sportiva'@'%' to database 'club_...'`.

**Causa:** el usuario de la app necesita permiso sobre las bases `club_*` para crearlas. Ese permiso lo da `docker/mysql/init/02-club-dbs.sh`, pero los scripts de init **solo corren la primera vez que se crea el volumen**. Si tu volumen es anterior, no lo tiene.

**Cómo confirmarlo:**

```bash
docker compose exec mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -u"$MYSQL_USER" -e "SHOW GRANTS"'
```

Si en la salida **no** aparece una línea con ``club\_%``, te falta el permiso:

```text
GRANT ALL PRIVILEGES ON `sportiva\_platform`.* TO `sportiva`@`%`
GRANT ALL PRIVILEGES ON `sportiva\_testing`.* TO `sportiva`@`%`
                                     ← falta la línea de `club\_%`
```

El usuario de la app no se puede dar el permiso a sí mismo: hace falta root. Hay dos caminos.

**Opción A: recuperar la contraseña de root (no borra nada).** La contraseña de root es aleatoria (`MYSQL_RANDOM_ROOT_PASSWORD`) y solo se imprime en el log del primer arranque. Si el contenedor de MySQL no se recreó desde entonces, sigue ahí:

```bash
docker compose logs mysql | grep "GENERATED ROOT PASSWORD"
```

Si aparece, reemplazá `<clave>` y corré:

```bash
docker compose exec mysql sh -c 'MYSQL_PWD="<clave>" mysql -uroot -e "GRANT ALL PRIVILEGES ON \`club\\_%\`.* TO '"'"'$MYSQL_USER'"'"'@'"'"'%'"'"'"'
```

Volvé a correr `SHOW GRANTS`: tiene que aparecer la línea de ``club\_%``.

**Opción B: recrear la base local (borra tus datos locales).** Si el grep no devuelve nada, la contraseña se perdió. Con datos de prueba, es lo más rápido:

> ⚠️ Borra **todo** tu MySQL local: `sportiva_platform`, `sportiva_testing` y las bases `club_*`. No afecta a nadie más.

```bash
docker compose down -v
bash scripts/setup.sh
docker compose exec backend php artisan platform:create-admin tu@email.com
```

Al recrear el volumen, los scripts de init vuelven a correr y el permiso queda puesto.

### `docker compose exec mysql bash /docker-entrypoint-initdb.d/02-club-dbs.sh` da "Access denied for user 'root'"

**Causa:** el script se conecta como root, y fuera del primer arranque nadie conoce esa contraseña. Ese comando no sirve para agregar el permiso a un volumen existente.

**Solución:** seguir [el caso del permiso](#crear-un-club-o-los-tests-de-club-fallan-con-1044-access-denied) (opción A u opción B).

### El club queda en `provisioning` para siempre

**Causa:** con `QUEUE_CONNECTION=database`, el alta del club queda en la cola hasta que el worker la procesa, y el worker no está corriendo.

**Solución:** levantarlo con `docker compose up -d` y revisar `docker compose logs -f queue`. Si cambiaste un job, `docker compose restart queue` (el worker guarda el código en memoria).

Si el club quedó en `failed`, se reintenta con `POST /api/admin/tenants/{id}/retry`.

### Los endpoints de un club viejo fallan con "Table doesn't exist"

**Causa:** el club se creó antes de las migraciones de club nuevas.

**Solución:**

```bash
docker compose exec backend php artisan tenants:migrate
```

### Front: "No pudimos conectar con el servidor"

**Causa:** la API no está levantada o `API_URL` está mal.

**Solución:** verificá que http://127.0.0.1:8000/api/health responda `"status":"ok"`, y que `.env.local` del front tenga `API_URL=http://127.0.0.1:8000/api` (con `/api`). Si cambiaste `.env.local`, reiniciá `pnpm dev`.

### Front: no arranca o falla por una variable

**Causa:** falta `.env.local`.

**Solución:** `cp .env.example .env.local` y reiniciá `pnpm dev`.

### Front: el usuario que creé no entra

**1. Probá el login directo contra la API**, sin el front:

```bash
curl -s -X POST http://127.0.0.1:8000/api/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"tu@email.com","password":"tu-clave"}'
```

| Resultado | Qué significa | Siguiente paso |
|---|---|---|
| JSON con `"token"` y `"user"` | La API está bien: el problema es el front | Punto 3 |
| `{"message":"Credenciales inválidas."}` | El usuario no existe o no coinciden email o clave | Punto 2 |
| `Too Many Attempts` | Bloqueo por intentos fallidos | Esperá 1 minuto |
| No responde / `Connection refused` | La API no está levantada | `docker compose ps` y `bash scripts/setup.sh` |

**2. Si da "Credenciales inválidas", mirá el usuario en la base:**

```bash
docker compose exec backend php artisan tinker --execute='dump(App\Models\User::select("id","email","is_platform_admin","is_active")->get()->toArray());'
```

- **No aparece:** se creó en otra base (la de otro dev) o el comando falló. Crealo de nuevo ([paso 3](#3-usuarios-para-entrar)).
- **Aparece con otro email** (mayúscula, espacio): escribilo exactamente igual en el login.
- **`is_active` en `false`:** el usuario está desactivado y la API rechaza el login.
- **Aparece bien y la clave igual falla:** ponele una clave nueva:

  ```bash
  docker compose exec backend php artisan tinker --execute='$u=App\Models\User::where("email","tu@email.com")->first(); $u->password="clave-nueva-123"; $u->save(); echo "ok";'
  ```

**3. Si con curl funciona pero en el front no:**

| En el front | Causa | Solución |
|---|---|---|
| "No pudimos conectar con el servidor" | La API no responde desde el front | Revisá `API_URL` en `.env.local` y reiniciá `pnpm dev` |
| "No pudimos iniciar sesión…" | `API_URL` sin `/api` (la API responde 404) | `API_URL=http://127.0.0.1:8000/api` y reiniciá `pnpm dev` |
| Entra y después muestra una pantalla de error | La API no está en `main` actualizado (`/me/tenants` no devuelve `user`) | En la API: `git pull` y `bash scripts/setup.sh` |
| Vuelve al login sin mensaje | Front viejo, con el login de `admin`/`admin` | En el front: `git pull`, `pnpm install` y reiniciá `pnpm dev` |

El front nuevo se reconoce porque el primer campo del login dice **"Email"**, no "Usuario".

### DBeaver: "Connection refused" en el 3310

**Causa:** el override no está activo.

**Solución:** verificá que el archivo se llame exactamente `compose.override.yaml`, en la raíz del repo de la API, y corré `docker compose up -d` después de crearlo ([paso 4](#4-ver-la-base-con-dbeaver-u-otro-visor)).

### DBeaver: el puerto 3310 está ocupado

**Solución:** elegí otro puerto en tu `.env` (por ejemplo `DB_FORWARD_PORT=3320`), corré `docker compose up -d` y usá ese puerto en DBeaver.

### DBeaver: "Public Key Retrieval is not allowed"

**Causa:** MySQL 8 autentica con `caching_sha2_password`. Sin SSL, el driver de DBeaver necesita pedirle al servidor una clave pública para cifrar la contraseña, y por seguridad lo trae bloqueado.

**Solución:**

1. Clic derecho en la conexión → **Edit Connection** (F4).
2. Pestaña **Driver properties**.
3. `allowPublicKeyRetrieval` → **`true`** y `useSSL` → **`false`**. Si alguna no aparece: clic derecho → **Add new property**.
4. **OK** y volvé a conectar.

Habilitarlo está bien **solo contra tu base local** en `127.0.0.1`. Contra una base remota, usá SSL.

### DBeaver: "Access denied for user 'sportiva'"

**Causa:** usuario o contraseña distintos a los de tu `.env`.

**Solución:** `grep "^DB_" .env` y usá `DB_USERNAME` y `DB_PASSWORD`.

### DBeaver: no veo las bases `club_*`

**Causa:** tu usuario no tiene el permiso sobre `club\_%`, o todavía no creaste ningún club.

**Solución:** revisá el permiso con `SHOW GRANTS` ([caso 1044](#crear-un-club-o-los-tests-de-club-fallan-con-1044-access-denied)). Si lo tenés, creá un club ([paso 3](#asociar-un-usuario-a-un-club)) y refrescá la conexión.

### Front: "Demasiados intentos"

**Causa:** 5 intentos de login fallidos en un minuto con el mismo email.

**Solución:** esperá el tiempo que indica el mensaje.

## Resumen rápido

| Síntoma | Solución |
|---|---|
| Backend `Exited (1)`, `Class ... not found` | `bash scripts/setup.sh` |
| `1044 Access denied` al crear un club o en los tests | Permiso `club\_%`: opción A (contraseña de root del log) u opción B (`down -v` + `setup.sh`) |
| `Access denied for user 'root'` | Mismo caso: opción A u opción B |
| Club en `provisioning` | Que esté corriendo el worker: `docker compose ps queue` (si no, `docker compose up -d`) |
| "Table doesn't exist" en un club viejo | `php artisan tenants:migrate` |
| "No pudimos conectar con el servidor" | API levantada y `API_URL` con `/api` |
| El usuario no entra / "Credenciales inválidas" | Probar el login con curl y revisar el usuario en tu base ([diagnóstico](#front-el-usuario-que-creé-no-entra)) |
| Necesito un usuario de club | Crearlo con tinker ([paso 3](#usuario-de-club-para-entrar-a-la-parte-de-clubes)) |
| Asociar un usuario a un club | Swagger o curl con el `platform_token` ([paso 3](#asociar-un-usuario-a-un-club)) |
| Ver la base con DBeaver | `cp compose.override.example.yaml compose.override.yaml` + `docker compose up -d`; `127.0.0.1:3310`, `sportiva` / `sportiva_dev_password` ([paso 4](#4-ver-la-base-con-dbeaver-u-otro-visor)) |
| DBeaver: "Public Key Retrieval is not allowed" | Driver properties: `allowPublicKeyRetrieval=true`, `useSSL=false` |
| DBeaver: "Connection refused" | Falta `compose.override.yaml` o el `docker compose up -d` |
