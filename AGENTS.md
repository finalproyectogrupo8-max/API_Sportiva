# API Sportiva

API REST en Laravel para Sportiva, una plataforma de gestión operativa de clubes deportivos. La consume el servidor Next.js del repo [Front-Sportiva](https://github.com/finalproyectogrupo8-max/Front-Sportiva), que actúa como BFF: el browser nunca llama a esta API directamente.

## Stack

- Runtime y framework: PHP 8.5 + Laravel 13 (ver [ADR 0001](docs/adr/0001-stack-y-arquitectura.md)).
- Datos: MySQL 8.4. Una base central (`sportiva_platform`) más una base por club (multi-tenancy por base de datos).
- Desarrollo local: Docker Compose (servicios `backend` y `mysql`). No hace falta PHP, Composer ni MySQL instalados en el host.
- Deploy: Laravel Cloud, que compila desde Git (sin imágenes Docker). El frontend va en Vercel.

## Commands

Todos corren en Docker, desde la raíz del repo. Guía completa: [docs/desarrollo-local.md](docs/desarrollo-local.md).

| Task | Command |
|---|---|
| Setup completo (idempotente) | `bash scripts/setup.sh` |
| Bootstrap en Windows (WSL + Docker + clone + setup) | `powershell -ExecutionPolicy Bypass -File .\scripts\setup-windows.ps1` |
| Levantar el entorno | `docker compose up -d` |
| Instalar dependencias | `docker compose exec backend composer install` |
| Migrar | `docker compose exec backend php artisan migrate` |
| Test | `docker compose exec backend php artisan test` |
| Lint | `docker compose exec backend vendor/bin/pint --test` |
| Regenerar el contrato OpenAPI | `docker compose exec backend php artisan scramble:export` |
| Build de la imagen | `HOST_UID=$(id -u) HOST_GID=$(id -g) docker compose build backend` |

- La API no tiene frontend (sin Vite ni npm): la UI del producto es el repo Front-Sportiva. La única vista es Swagger UI (`resources/views/api-docs/`). El setup es siempre `bash scripts/setup.sh`.
- No uses `docker compose down -v` salvo que quieras **borrar** la base local.

## Conventions

- Todas las rutas de la API van bajo `/api` y siempre responden JSON, también en los errores.
- Validación con Form Requests (`php artisan make:request`): nunca validar a mano en el controller. Un error de validación responde 422 con `{ message, errors: { campo: [...] } }`.
- Respuestas con API Resources, no modelos ni arrays sueltos (lo necesita `dedoc/scramble` para el contrato tipado).
- **Contrato:** `docs/api/openapi.json` lo genera Scramble desde el código. Si cambiás una ruta, un Form Request o un Resource, regeneralo con `scramble:export` y commitealo con el cambio: la CI falla si quedó desactualizado.
- Documentación: `/api/docs` (Swagger UI) y `/api/docs/openapi.json`. Abierta en `local`, con HTTP Basic en `staging` (`API_DOCS_USERNAME` y `API_DOCS_PASSWORD`) y 404 en el resto (`App\Http\Middleware\ApiDocsAccess`). No la expongas en producción.
- `GET /api/health` responde un cuerpo fijo (`status`, `checks`): no le agregues versiones, hosts ni detalles de excepciones.
- Claves en `snake_case`, fechas ISO 8601 con zona horaria, enums en inglés.
- El contrato con el frontend está en `docs/integracion-api-laravel.md` del repo Front-Sportiva.
- Los tests viven en `tests/` y usan PHPUnit, contra la base MySQL `sportiva_testing` (no SQLite).
- Las imágenes Docker se fijan por tag y digest. Para actualizarlas, ver "Imágenes fijadas" en `docs/decisions.md`.
- El `AGENTS.md`/`CLAUDE.md` que trae el skeleton de Laravel (bootstrap de Laravel Boost, que instala PHP en el host) se descartó a propósito: no hay PHP en el host.
- Git: crear ramas con `git checkout -b <rama>`.

## Constraints

- Security: datos personales de socios y jugadores. Autenticación con tokens Sanctum atados a un tenant (ADR 0001 del front). Nunca aceptar `tenant_id` ni el slug del club desde el cliente como fuente de autorización.
- Multi-tenancy: una base por club. No usar `USE <base>` (el proxy de Laravel Cloud lo rechaza): cambiar de base reconfigurando la conexión `tenant` y llamando a `DB::purge()`.
- Cost: proyecto académico, plan Starter de Laravel Cloud.
- Availability: sin SLA formal.

## Workflow

- Specs: OpenSpec en `openspec/`. Los cambios con comportamiento nuevo pasan por `/opsx:propose`, revisión y aprobación antes de `/opsx:apply`.
- Decisions: `docs/adr/` y `docs/decisions.md`.
- Documentación en español, en Markdown dentro del repo. Los términos técnicos quedan en inglés.
- Never commit secrets. `.env.example` lista los nombres de las variables.
