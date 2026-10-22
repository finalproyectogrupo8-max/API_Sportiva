# syntax=docker/dockerfile:1

# Imagen de desarrollo y CI de la API Sportiva.
# Producción NO usa esta imagen: Laravel Cloud compila desde Git (ver docs/adr/0001-stack-y-arquitectura.md).
#
# Las imágenes están fijadas por tag de patch y digest. Para actualizarlas, seguir
# "Imágenes fijadas" en docs/decisions.md.

FROM composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS composer

# ---------------------------------------------------------------------------
# base: runtime PHP con las extensiones que necesitan Laravel y MySQL.
# Un futuro target "prod" partiría de acá.
# ---------------------------------------------------------------------------
FROM php:8.5.12-cli-trixie@sha256:595a7cbb1e0547336319f228c4dc991c2a199350ab12b1c8fda05f73abd06e6b AS base

# OPcache ya viene compilado en PHP 8.5. pdo_mysql, intl, zip y bcmath se compilan acá.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip bcmath \
    && rm -rf /var/lib/apt/lists/*

# Usuario no-root. UID/GID tienen que coincidir con el usuario del host para que
# los archivos que se crean en el bind mount (vendor/, storage/) tengan el dueño correcto.
# Si el GID ya existe en la imagen (por ejemplo, 20 en macOS o el grupo del runner de la CI),
# se reutiliza ese grupo. -o permite un UID que ya esté en uso.
ARG UID=1000
ARG GID=1000
RUN (getent group "${GID}" > /dev/null || groupadd --gid "${GID}" app) \
    && useradd --non-unique --uid "${UID}" --gid "${GID}" --create-home --shell /bin/bash app

WORKDIR /var/www/html
RUN chown "${UID}:${GID}" /var/www/html

# ---------------------------------------------------------------------------
# dev: Composer, herramientas de desarrollo y el servidor embebido de PHP.
# El código NO se copia: se monta desde el host (ver compose.yaml).
# ---------------------------------------------------------------------------
FROM base AS dev

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && cp "${PHP_INI_DIR}/php.ini-development" "${PHP_INI_DIR}/php.ini"

COPY --from=composer /usr/bin/composer /usr/bin/composer

USER app

# Varios workers para que el servidor embebido atienda requests en paralelo.
ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
