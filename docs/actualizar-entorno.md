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

1. **Revisá tu `.env`** contra `.env.example`: cada variable nueva de `.env.example` tiene que estar en tu `.env`. Para el alta de clubes en local conviene `QUEUE_CONNECTION=sync` (sin worker). Si cambiaste algo:

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

## 3. Usuario para entrar

El login es contra la API: `admin` / `admin` ya no existe. Los usuarios viven en **tu** base local, así que cada dev crea los suyos:

```bash
docker compose exec backend php artisan platform:create-admin tu@email.com
```

Te pide nombre y una contraseña de al menos 12 caracteres. Con ese usuario entrás en http://localhost:3000 y caés en `/admin`.

## 4. Problemas y soluciones

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

**Causa:** con `QUEUE_CONNECTION=database`, el alta del club queda en la cola hasta que un worker la procesa.

**Solución (una de dos):**

- Poner `QUEUE_CONNECTION=sync` en el `.env` y correr `docker compose exec backend php artisan config:clear`. Los clubes nuevos se crean en el momento.
- O procesar la cola en otra terminal: `docker compose exec backend php artisan queue:work`.

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

### Front: "Credenciales inválidas" con un usuario que sí existe

**Causa:** el usuario existe en la base de otro dev, no en la tuya. Cada base local es independiente.

**Solución:** crealo en tu base con `platform:create-admin` ([paso 3](#3-usuario-para-entrar)).

### Front: "Demasiados intentos"

**Causa:** 5 intentos de login fallidos en un minuto con el mismo email.

**Solución:** esperá el tiempo que indica el mensaje.

## Resumen rápido

| Síntoma | Solución |
|---|---|
| Backend `Exited (1)`, `Class ... not found` | `bash scripts/setup.sh` |
| `1044 Access denied` al crear un club o en los tests | Permiso `club\_%`: opción A (contraseña de root del log) u opción B (`down -v` + `setup.sh`) |
| `Access denied for user 'root'` | Mismo caso: opción A u opción B |
| Club en `provisioning` | `QUEUE_CONNECTION=sync` + `config:clear`, o `queue:work` |
| "Table doesn't exist" en un club viejo | `php artisan tenants:migrate` |
| "No pudimos conectar con el servidor" | API levantada y `API_URL` con `/api` |
| "Credenciales inválidas" | Crear el usuario en tu base local |
