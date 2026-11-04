#!/usr/bin/env bash
# Setup del entorno local de la API Sportiva. Es idempotente: se puede correr las veces que haga falta.
#
#   bash scripts/setup.sh
#
# Hace, en orden: crea .env, construye la imagen, levanta MySQL, carga los datos de demo (si la base
# está vacía), instala dependencias, genera APP_KEY, levanta la API, migra, verifica /api/health y
# levanta el visor de base de datos (Adminer en http://127.0.0.1:8080).
#
# NO corta la ejecución por un error recuperable: cada paso reintenta, y si al final algo quedó
# pendiente lo dice en el resumen con el comando para reintentarlo. Nunca borra volúmenes ni datos
# existentes, y nunca sobrescribe un .env.
#
# Requisitos: Docker con el plugin Compose v2. No hace falta PHP, Composer ni MySQL en el host.

# Sin -e a propósito: un paso que falla no debe matar al resto. Cada paso lo maneja con "step".
set -uo pipefail

# En Git Bash (Windows), evita que MSYS reescriba las rutas del contenedor.
export MSYS_NO_PATHCONV=1

cd "$(dirname "$0")/.." || exit 0

DEMO_DUMP="docker/mysql/init/03-demo-data.sql"
PENDING=()

info() { printf '\n==> %s\n' "$*"; }
ok() { printf '    OK: %s\n' "$*"; }
warn() { printf '    AVISO: %s\n' "$*" >&2; }

# Corre un comando hasta N veces con espera creciente. Devuelve 0 si alguna vez funcionó.
retry() {
    local attempts=$1; shift
    local i
    for ((i = 1; i <= attempts; i++)); do
        if "$@"; then return 0; fi
        if ((i < attempts)); then
            warn "intento $i de $attempts falló; reintento en $((i * 5)) s"
            sleep $((i * 5))
        fi
    done
    return 1
}

# Un paso: si falla, se anota como pendiente y el script sigue.
step() {
    local title=$1 pending_msg=$2; shift 2
    info "$title"
    if "$@"; then
        ok "$title"
        return 0
    fi
    warn "no se pudo: $title"
    PENDING+=("$pending_msg")
    return 1
}

case "$(uname -s)" in
    MINGW* | MSYS* | CYGWIN*) MSYS_OR_GITBASH=1 ;;
    *) MSYS_OR_GITBASH=0 ;;
esac

# Si el repo está en el filesystem de Windows (C:\), la API va muy lenta (0,2-2,3 s por request contra
# 0,03 s dentro de WSL). Funciona igual, pero se avisa.
if [ "$MSYS_OR_GITBASH" = 1 ] || { grep -qi microsoft /proc/version 2> /dev/null && [[ "$PWD" == /mnt/* ]]; }; then
    warn "el repo está en el filesystem de Windows: la API va a responder lento. Lo ideal es clonarlo dentro de WSL."
fi

if ! command -v docker > /dev/null 2>&1; then
    warn "no se encontró 'docker'. Instalá Docker Desktop y volvé a correr este script."
    PENDING+=("Instalar Docker Desktop y volver a correr: bash scripts/setup.sh")
fi

# Docker Desktop tarda en arrancar: esperamos al daemon antes de rendirnos.
docker_ready() { docker info > /dev/null 2>&1; }
if command -v docker > /dev/null 2>&1 && ! retry 12 docker_ready; then
    warn "el daemon de Docker no responde. ¿Está abierto Docker Desktop?"
    PENDING+=("Abrir Docker Desktop, esperar a que diga 'running' y volver a correr: bash scripts/setup.sh")
fi

# Deja un ACCESOS.txt en la raíz del repo con todo lo que hay que saber para usar la app:
# links, usuarios, datos del visor de base y de Mailpit. Es local y no se versiona.
write_access_file() {
    local file="ACCESOS.txt"
    local db_user db_pass
    db_user="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- || true)"
    db_pass="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- || true)"
    cat > "$file" <<EOF
ACCESOS DE SPORTIVA EN ESTA MÁQUINA
Generado: $(date '+%d/%m/%Y %H:%M')

------------------------------------------------------------
LINKS
------------------------------------------------------------
App (front) .......... http://127.0.0.1:3000
API (salud) .......... http://127.0.0.1:${port:-8000}/api/health
Visor de base ........ http://127.0.0.1:${adminer_port:-8080}/   (Adminer)
Mails (Mailpit) ...... http://127.0.0.1:8025
Documentación API .... http://127.0.0.1:${port:-8000}/api/docs

------------------------------------------------------------
USUARIOS
------------------------------------------------------------
Plataforma (panel /admin):
  admin@sportiva.com          admin123     Admin de plataforma
  general@sportiva.com        general123   Usuario sin club

Club Demo Sportiva (entra directo a /c/demo):
  admin.club@sportiva.com          demo1234   Administrador del club
  coordinador@sportiva.com         demo1234   Coordinador de Básquet
  coordinador.futbol@sportiva.com  demo1234   Coordinador de Fútbol
  profe@sportiva.com               demo1234   Profe (Sub-13 y Sub-15)
  preparador@sportiva.com          demo1234   Preparador físico
  utilero@sportiva.com             demo1234   Utilero (stock e indumentaria)
  mantenimiento@sportiva.com       demo1234   Mantenimiento (canchas)

------------------------------------------------------------
VISOR DE LA BASE (Adminer)
------------------------------------------------------------
URL:      http://127.0.0.1:${adminer_port:-8080}/
Sistema:  MySQL
Servidor: mysql
Usuario:  ${db_user:-sportiva}
Clave:    ${db_pass:-la del .env}
Bases:    sportiva_platform (plataforma) y club_demo (club)

------------------------------------------------------------
MAILS (Mailpit)
------------------------------------------------------------
Todo mail que manda la app queda acá y no sale a internet:
  http://127.0.0.1:8025

Para probar: en el login usá "Olvidé mi contraseña" con cualquier
usuario; el mail aparece en Mailpit en segundos.

Para mandar mails reales con Gmail, o cambiar de proveedor:
  docs/mail-demo.md  y  docs/mail-produccion.md

------------------------------------------------------------
COMANDOS ÚTILES (desde la carpeta de la API)
------------------------------------------------------------
Levantar todo:    docker compose up -d
Bajar todo:       docker compose down
Ver estado:       docker compose ps
Ver logs:         docker compose logs -f backend
Reiniciar cola:   docker compose exec backend php artisan queue:restart

Guía completa: docs/instalacion.md
EOF
    ok "usuarios, links y accesos en $file"
}

summary() {
    printf '\n============================================================\n'
    if ((${#PENDING[@]} == 0)); then
        printf 'API lista.\n'
    else
        printf 'API: quedó pendiente lo siguiente (el resto sí se completó):\n'
        local p
        for p in "${PENDING[@]}"; do printf '  - %s\n' "$p"; done
    fi
    printf '\nAPI:            http://127.0.0.1:%s/api/health\n' "${port:-8000}"
    printf 'Visor de BD:    http://127.0.0.1:%s  (Sistema MySQL, servidor mysql, usuario y contraseña de .env)\n' "${adminer_port:-8080}"
    printf 'Mails:          http://127.0.0.1:8025  (Mailpit)\n'
    printf '\nCuentas de demo (contraseña demo1234 salvo las dos primeras): docs/datos-demo.md\n'
    printf '============================================================\n'
}

# Si no hay Docker no se puede seguir: se imprime el resumen y se sale sin error.
if ! command -v docker > /dev/null 2>&1 || ! docker_ready; then
    summary
    exit 0
fi

if [ -f .env ]; then
    info ".env ya existe: se deja como está"
else
    info "Creando .env a partir de .env.example"
    cp .env.example .env
fi

# Los puertos locales hablan HTTP. Si alguien dejó https:// en una URL de 127.0.0.1 o localhost,
# el front y los links del correo fallan con un error de TLS ("EPROTO: The TLS handshake failed").
# Se corrige y se avisa: no se toca ninguna URL real (solo las de loopback).
fix_local_scheme() {
    local key value fixed
    for key in APP_URL FRONTEND_URL; do
        value="$(grep -E "^${key}=" .env | cut -d= -f2- || true)"
        case "$value" in
            https://127.0.0.1* | https://localhost*)
                fixed="${value/https:\/\//http:\/\/}"
                awk -v k="$key" -v v="$fixed" 'BEGIN{FS=OFS="="} $1==k{print k "=" v; next} {print}' .env > .env.tmp && mv .env.tmp .env
                warn "$key usaba https en un puerto local: se cambió a http ($value -> $fixed)"
                ;;
        esac
    done
    return 0
}
fix_local_scheme

port="$(grep -E '^APP_PORT=' .env | cut -d= -f2 || true)"
adminer_port="$(grep -E '^ADMINER_PORT=' .env | cut -d= -f2 || true)"

# El usuario del contenedor tiene que coincidir con el del host para que vendor/ y storage/ tengan
# el dueño correcto. En Git Bash (Windows), id -u no sirve, así que se usa 1000.
if [ "$MSYS_OR_GITBASH" = 1 ]; then
    HOST_UID=1000; HOST_GID=1000
else
    HOST_UID="$(id -u)"; HOST_GID="$(id -g)"
fi
export HOST_UID HOST_GID

build_backend() {
    docker compose build backend && return 0
    # Si no se puede reconstruir (por ejemplo, sin red para bajar la base de Docker), se usa la imagen que ya existe.
    if docker image inspect api-sportiva-dev:local > /dev/null 2>&1; then
        warn "no se pudo reconstruir la imagen: se usa la que ya existe en esta máquina"
        return 0
    fi
    return 1
}
step "Construyendo la imagen de desarrollo (UID=${HOST_UID}, GID=${HOST_GID})" \
    "Construir la imagen: docker compose build backend" \
    retry 3 build_backend

start_mysql() { docker compose up -d --wait mysql; }
if ! step "Levantando MySQL" "Levantar MySQL: docker compose up -d --wait mysql" retry 3 start_mysql; then
    summary
    exit 0
fi

# Datos de demo: solo si la base de plataforma está vacía (instalación nueva o volumen sin datos).
# Nunca se pisan datos existentes. La importación usa el usuario de la app (no root).
demo_needed() {
    local count
    count="$(docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -N -B -usportiva -e "SELECT (SELECT COUNT(*) FROM sportiva_platform.tenants) + (SELECT COUNT(*) FROM sportiva_platform.users)"' 2> /dev/null | tr -d '\r' || true)"
    # Sin tablas todavía (volumen recién creado) la consulta falla y el conteo queda vacío: cuenta como vacía.
    [ -z "$count" ] || [ "$count" = "0" ]
}
import_demo() {
    docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -usportiva' < "$DEMO_DUMP"
}
if [ -f "$DEMO_DUMP" ]; then
    info "Datos de demo"
    if demo_needed; then
        step "Importando los datos de demo (plataforma y club_demo)" \
            "Importar los datos de demo: docker compose exec -T mysql sh -c 'MYSQL_PWD=\"\$MYSQL_PASSWORD\" mysql -usportiva' < $DEMO_DUMP" \
            retry 3 import_demo
    else
        ok "la base ya tiene datos: no se importa la demo"
    fi
else
    warn "no existe $DEMO_DUMP: se sigue sin datos de demo"
fi

install_composer() { docker compose run --rm --no-deps backend composer install --no-interaction --prefer-dist; }
step "Instalando dependencias de PHP (composer install)" \
    "Instalar dependencias: docker compose run --rm --no-deps backend composer install" \
    retry 3 install_composer

if grep -qE '^APP_KEY=base64:.+' .env; then
    ok "APP_KEY ya está definida: se deja como está"
else
    gen_key() { docker compose run --rm --no-deps backend php artisan key:generate --no-interaction; }
    step "Generando APP_KEY" "Generar la clave: docker compose run --rm --no-deps backend php artisan key:generate" retry 2 gen_key
fi

start_api() { docker compose up -d --wait backend queue mailpit; }
api_up=0
step "Levantando la API (backend, cola y Mailpit)" "Levantar la API: docker compose up -d --wait backend queue mailpit" retry 3 start_api && api_up=1

if ((api_up)); then
    migrate() { docker compose exec -T backend php artisan migrate --no-interaction; }
    step "Migrando la base (no hace nada si ya está al día)" "Migrar: docker compose exec backend php artisan migrate" retry 2 migrate

    health() {
        docker compose exec -T backend php -r '
            $body = @file_get_contents("http://127.0.0.1:8000/api/health");
            if ($body === false) { exit(1); }
            echo $body, PHP_EOL;
        ' > /dev/null 2>&1
    }
    step "Verificando /api/health" "La API no responde: docker compose logs backend" retry 6 health
fi

# El visor es opcional para la API: si no se puede bajar la imagen, la API igual funciona.
start_adminer() { docker compose up -d adminer; }
step "Levantando el visor de base de datos (Adminer)" \
    "Levantar el visor: docker compose up -d adminer (requiere bajar la imagen de Docker Hub)" \
    retry 2 start_adminer

write_access_file
summary
exit 0
