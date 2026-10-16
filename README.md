# API Sportiva

API REST de **Sportiva**, una plataforma de gestión operativa de clubes deportivos. Laravel 13 + PHP 8.5 + MySQL 8.4.

El frontend es el repo [Front-Sportiva](https://github.com/finalproyectogrupo8-max/Front-Sportiva) (Next.js): su servidor es el único cliente de esta API (patrón BFF).

## Empezar

No hace falta instalar PHP, Composer ni MySQL: todo corre en Docker.

- **Windows:** descargá y ejecutá `scripts/setup-windows.ps1`. Instala o verifica WSL 2 y Docker Desktop, clona el repo dentro de WSL y deja todo andando.
- **Con el repo ya clonado** (WSL, Linux o macOS): `bash scripts/setup.sh`

Después: `http://127.0.0.1:8000/api/health` → `{"status":"ok","checks":{"database":"ok"}}`.

Detalles, comandos del día a día y troubleshooting: **[docs/desarrollo-local.md](docs/desarrollo-local.md)**.

## Documentación

| Documento | Contenido |
| --- | --- |
| [docs/desarrollo-local.md](docs/desarrollo-local.md) | Setup, uso diario, reset de datos, troubleshooting |
| [docs/arquitectura-multi-tenant.md](docs/arquitectura-multi-tenant.md) | Base central + una base por club: qué existe y qué falta |
| [docs/adr/](docs/adr/) | Decisiones de arquitectura |
| [docs/decisions.md](docs/decisions.md) | Log de decisiones cortas, imágenes fijadas y decisiones abiertas |
| [openspec/](openspec/) | Specs y changes (OpenSpec) |
| [AGENTS.md](AGENTS.md) | Convenciones y comandos (para personas y agentes) |

## Estado

Fase 1: entorno Docker, endpoint de health, errores JSON en `/api/*` y CI. Todavía **no** hay autenticación, multi-tenancy implementado ni módulos de negocio.

## Deploy

Producción va a Laravel Cloud, que compila desde Git: la imagen Docker de este repo es solo para desarrollo y CI. Ver [ADR 0001](docs/adr/0001-stack-y-arquitectura.md).
