# API Sportiva

API REST de **Sportiva**, una plataforma de gestión operativa de clubes deportivos. Laravel 13 + PHP 8.5 + MySQL 8.4.

El frontend es el repo [Front-Sportiva](https://github.com/finalproyectogrupo8-max/Front-Sportiva) (Next.js): su servidor es el único cliente de esta API (patrón BFF).

## Empezar.

No hace falta instalar PHP, Composer ni MySQL: todo corre en Docker.

- **Windows** (PowerShell normal, no como administrador), desde una máquina sin nada instalado:
  ```powershell
  irm https://raw.githubusercontent.com/finalproyectogrupo8-max/API_Sportiva/main/scripts/setup-windows.ps1 | iex
  ```
  Instala WSL, Ubuntu, Docker Desktop, Git y VS Code si faltan, clona el repo dentro de WSL y deja todo andando. Con `-WhatIf` solo muestra qué haría.
- **Con el repo ya clonado** (WSL, Linux o macOS): `bash scripts/setup.sh`

Después: `http://127.0.0.1:8000/api/health` → `{"status":"ok","checks":{"database":"ok"}}`.

Documentación de la API (Swagger UI): `http://127.0.0.1:8000/api/docs`. El contrato OpenAPI versionado está en [docs/api/openapi.json](docs/api/openapi.json).

Detalles, comandos del día a día y troubleshooting: **[docs/desarrollo-local.md](docs/desarrollo-local.md)**.

## Documentación

| Documento | Contenido |
| --- | --- |
| [docs/desarrollo-local.md](docs/desarrollo-local.md) | Setup, uso diario, reset de datos, troubleshooting |
| [docs/guia-jobs.md](docs/guia-jobs.md) | **Guía para devs:** jobs y colas (crear, despachar, reintentar, jobs de club, worker, tareas programadas y tests) |
| [docs/guia-abm.md](docs/guia-abm.md) | **Guía para devs:** cómo armar un ABM (rutas, controller, Requests, Resources, listados, permisos, bajas, tests y checklist con comandos) |
| [docs/guia-bases-de-datos.md](docs/guia-bases-de-datos.md) | **Guía para devs:** base central y de cada club, migraciones paso a paso, alta de clubes (job), panel de admin y comandos |
| [docs/arquitectura-multi-tenant.md](docs/arquitectura-multi-tenant.md) | Base central + una base por club: qué existe y qué falta |
| [docs/adr/](docs/adr/) | Decisiones de arquitectura |
| [docs/api/openapi.json](docs/api/openapi.json) | Contrato OpenAPI (generado por Scramble; lo consume el frontend) |
| [docs/decisions.md](docs/decisions.md) | Log de decisiones cortas, imágenes fijadas y decisiones abiertas |
| [openspec/](openspec/) | Specs y changes (OpenSpec) |
| [AGENTS.md](AGENTS.md) | Convenciones y comandos (para personas y agentes) |

## Autenticación

La API usa tokens de Laravel Sanctum, pensados para el patrón BFF: solo el servidor Next.js la consume.

1. `POST /api/auth/login` devuelve un token pre-tenant (solo sirve para elegir club) y la lista de clubes.
2. `POST /api/auth/select-tenant` lo cambia por un token atado al club.
3. Los endpoints operativos usan ese token; el club sale siempre del token, nunca del request.

Crear el primer administrador de plataforma: `docker compose exec backend php artisan platform:create-admin <email>`.
El contrato completo está en [docs/integracion-api-laravel.md](https://github.com/finalproyectogrupo8-max/Front-Sportiva/blob/main/docs/integracion-api-laravel.md) (repo Front-Sportiva) y `/api/docs`.

## Estado

La API incluye autenticación Sanctum, administración central de clubes y contexto de club derivado del token. La provisión de bases de datos y los módulos operativos de los clubes siguen en desarrollo.

## Deploy

Producción va a Laravel Cloud, que compila desde Git: la imagen Docker de este repo es solo para desarrollo y CI. Ver [ADR 0001](docs/adr/0001-stack-y-arquitectura.md).
