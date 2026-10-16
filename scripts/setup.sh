#!/usr/bin/env bash
# Setup del entorno local de la API Sportiva. Es idempotente: se puede correr las veces que haga falta.
#
#   bash scripts/setup.sh
#
# Hace lo siguiente: crea .env si no existe, construye la imagen con tu UID/GID, levanta
# MySQL y la API, instala dependencias, genera APP_KEY si está vacía y migra.
# NUNCA borra volúmenes ni datos, y NUNCA sobrescribe un .env existente.
#
# Requisitos: Docker con el plugin Compose v2. No hace falta PHP, Composer ni MySQL en el host.

set -euo pipefail

# En Git Bash (Windows), evita que MSYS reescriba las rutas del contenedor.
export MSYS_NO_PATHCONV=1

cd "$(dirname "$0")/.."

info() { printf '\n==> %s\n' "$*"; }
fail() { printf '\nERROR: %s\n' "$*" >&2; exit 1; }

# Si el repo está en el filesystem de Windows (C:\), Docker monta el código cruzando de Windows a
# Linux y Laravel lee miles de archivos en cada request: medido, 0,2-2,3 s por request contra
# 0,03 s con el repo dentro de WSL. No se bloquea (funciona igual), pero se avisa.
warn_windows_filesystem() {
    printf '\n'
    printf '!! ATENCION: el repo está en el filesystem de Windows (%s).\n' "$1"
    printf '!! Funciona, pero la API va a responder MUY lento (medido: 0,2-2,3 s por request,\n'
    printf '!! contra 0,03 s con el repo dentro de WSL).\n'
    printf '!! Solución: cloná el repo dentro de WSL con scripts/setup-windows.ps1\n'
    printf '!! (ver "Opción 1" en docs/desarrollo-local.md).\n'
}

case "$(uname -s)" in
    MINGW* | MSYS* | CYGWIN*) warn_windows_filesystem "Git Bash sobre Windows" ;;
esac
if grep -qi microsoft /proc/version 2> /dev/null && [[ "$PWD" == /mnt/* ]]; then
    warn_windows_filesystem "WSL, pero en $PWD"
fi

command -v docker > /dev/null || fail "No se encontró 'docker'. Instalá Docker Desktop (ver docs/desarrollo-local.md)."
docker compose version > /dev/null 2>&1 || fail "No se encontró 'docker compose' (v2)."
docker info > /dev/null 2>&1 || fail "El daemon de Docker no responde. ¿Está abierto Docker Desktop?"

if [ -f .env ]; then
    info ".env ya existe: se deja como está"
else
    info "Creando .env a partir de .env.example"
    cp .env.example .env
fi

# El usuario del contenedor tiene que coincidir con el del host para que vendor/ y storage/ tengan
# el dueño correcto. En Git Bash (Windows), id -u devuelve valores que no sirven, así que se usa 1000.
case "$(uname -s)" in
    MINGW* | MSYS* | CYGWIN*) HOST_UID=1000; HOST_GID=1000 ;;
    *) HOST_UID="$(id -u)"; HOST_GID="$(id -g)" ;;
esac
export HOST_UID HOST_GID

info "Construyendo la imagen de desarrollo (UID=${HOST_UID}, GID=${HOST_GID})"
docker compose build backend

info "Levantando MySQL y esperando el healthcheck"
docker compose up -d --wait mysql

info "Instalando dependencias de PHP (composer install)"
docker compose run --rm --no-deps backend composer install --no-interaction --prefer-dist

if grep -qE '^APP_KEY=base64:.+' .env; then
    info "APP_KEY ya está definida: se deja como está"
else
    info "Generando APP_KEY"
    docker compose run --rm --no-deps backend php artisan key:generate --no-interaction
fi

info "Levantando la API"
docker compose up -d --wait

info "Migrando la base central"
docker compose exec -T backend php artisan migrate --no-interaction

info "Verificando /api/health"
docker compose exec -T backend php -r '
    $body = @file_get_contents("http://127.0.0.1:8000/api/health");
    if ($body === false) { fwrite(STDERR, "La API no respondió.\n"); exit(1); }
    echo $body, PHP_EOL;
'

port="$(grep -E '^APP_PORT=' .env | cut -d= -f2 || true)"
info "Listo. API en http://127.0.0.1:${port:-8000}/api/health"
