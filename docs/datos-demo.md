# Datos de demo

La instalación con `scripts/setup.sh` carga una base con datos de ejemplo, para ver todas las pantallas con información real. Todas las tablas de la plataforma y de un club tienen filas.

## Cuentas

Contraseña de todas, salvo las dos primeras: **`demo1234`**.

| Email | Contraseña | Qué es | Dónde entra |
| --- | --- | --- | --- |
| `admin@sportiva.com` | `admin123` | Admin de plataforma | Panel `/admin` |
| `general@sportiva.com` | `general123` | Usuario sin club | Muestra "no tenés clubes asignados" |
| `admin.club@sportiva.com` | `demo1234` | Administrador del club | Club **Demo Sportiva** (`/c/demo`) |
| `coordinador@sportiva.com` | `demo1234` | Coordinador de deporte (solo Básquet) | Club Demo |
| `coordinador.futbol@sportiva.com` | `demo1234` | Coordinador de deporte (solo Fútbol) | Club Demo |
| `profe@sportiva.com` | `demo1234` | Profe (categorías Sub-13 y Sub-15) | Club Demo |
| `preparador@sportiva.com` | `demo1234` | Preparador físico | Club Demo |
| `utilero@sportiva.com` | `demo1234` | Utilero (stock e indumentaria) | Club Demo |
| `mantenimiento@sportiva.com` | `demo1234` | Mantenimiento (canchas) | Club Demo |

El club tiene un solo usuario que entra sin pasar por `/clubes`: el administrador tiene un club, así que el selector lo elige solo.

## Qué hay cargado

- **Plataforma:** 9 usuarios, 1 club, sus membresías, cinco suscriptores de newsletter, tickets de soporte, auditoría de altas, sesiones y tokens de ejemplo, un job programado y un fallo reintentable en la cola.
- **Club Demo Sportiva:** 3 deportes, 2 temporadas (una cerrada), 3 categorías activas, 2 competencias con sus requisitos y tabla de posiciones, 18 jugadores con contactos y su posición (cada deporte tiene sus posiciones), papeles en distintos estados (aprobado, pendiente, rechazado, vencido) y archivos reales, profes, tres meses de temporada (dos entrenamientos por semana y un partido cada dos semanas por categoría, con su asistencia y resultados), partidos próximos (con convocatoria publicada, postergado, suspendido), evaluaciones físicas mensuales para ver la evolución, instalaciones, reparaciones, stock con movimientos, pedidos de indumentaria y de materiales con su historial, anuncios enviados y un borrador, notificaciones, alertas y auditoría del club.

Las tablas técnicas `jobs`, `failed_jobs` y `cache_locks` tienen filas de ejemplo; `cache` y `sessions` también. Son datos que la app usaría igual en producción.

## Dónde está el archivo

`docker/mysql/init/03-demo-data.sql`: volcado de las dos bases (`sportiva_platform` y `club_demo`), con `CREATE TABLE` y los datos. Se importa solo en el primer arranque de MySQL, y `scripts/setup.sh` lo importa si la base está vacía.

## Crear más datos

Desde la app, con las pantallas de alta de cada módulo. Para volver al estado inicial, ver "Regenerar la demo" más abajo.

## Regenerar la demo

Solo para mantenedores. Borra todo lo de desarrollo local:

```bash
docker compose exec backend php artisan tinker --execute='DB::statement("DROP DATABASE IF EXISTS club_demo");'
docker compose exec backend php artisan migrate:fresh --force
docker compose exec backend php artisan db:seed --class='Database\Seeders\Demo\DemoSeeder' --force
```

Después, exportar de nuevo con `mysqldump` de `sportiva_platform` y `club_demo` y reemplazar `docker/mysql/init/03-demo-data.sql`.

### Regenerar el dump sin tocar la base local

Genera la demo en un MySQL temporal, en la misma red de Docker, y solo reemplaza el archivo del dump:

```bash
docker run -d --name sportiva-demo-gen --network api-sportiva_default \
  -e MYSQL_DATABASE=sportiva_platform -e MYSQL_USER=sportiva -e MYSQL_PASSWORD=sportiva_dev_password \
  -e MYSQL_RANDOM_ROOT_PASSWORD=yes \
  -v "$PWD/docker/mysql/init/02-club-dbs.sh:/docker-entrypoint-initdb.d/02-club-dbs.sh:ro" mysql:8.4.11
# Esperar a que responda (unos segundos) y cargar la demo ahí:
docker compose run --rm --no-deps -e DB_HOST=sportiva-demo-gen backend \
  sh -c "php artisan migrate --force && php artisan db:seed --class='Database\Seeders\Demo\DemoSeeder' --force"
docker exec sportiva-demo-gen mysqldump -usportiva -psportiva_dev_password --databases sportiva_platform club_demo \
  --add-drop-database --single-transaction --no-tablespaces --set-gtid-purged=OFF > docker/mysql/init/03-demo-data.sql
docker rm -f sportiva-demo-gen
```

Los datos de la temporada usan una semilla fija, así que siempre salen iguales; las fechas se calculan desde el día en que se genera.
