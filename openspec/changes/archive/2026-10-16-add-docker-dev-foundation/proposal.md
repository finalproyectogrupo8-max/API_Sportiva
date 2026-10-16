# Proposal

## Why

El repo `API_Sportiva` está vacío y los 4 devs del equipo no tienen un entorno común: el PHP local del dueño (8.2) ni siquiera alcanza para Laravel 13. Antes de construir auth y los módulos del club, hace falta una base reproducible: clonar, levantar con Docker, migrar y correr los tests, sin instalar PHP, Composer ni MySQL en Windows.

## What Changes

- Se crea la app Laravel 13 (PHP 8.5) en la raíz del repo, generada dentro de un contenedor.
- Imagen de desarrollo en `docker/php/Dockerfile`:
  - multi-stage, con una base PHP fijada por versión y digest;
  - extensiones `pdo_mysql`, `intl`, `zip`, `bcmath` y `opcache`;
  - Composer con versión fija y usuario no-root.
- `compose.yaml` con dos servicios:
  - `backend`: código montado y `php artisan serve` en `127.0.0.1:8000`;
  - `mysql`: MySQL 8.4 con volumen nombrado y healthcheck. No se expone al host por defecto.
- Setup automatizado:
  - `scripts/setup.sh`: idempotente; levanta, instala y migra;
  - `scripts/setup-windows.ps1`: verifica o instala WSL 2 y Docker Desktop (siempre con confirmación), clona el repo dentro de WSL y corre el setup.
- Script de inicialización de MySQL: crea `sportiva_platform` (la base central) y `sportiva_testing`, con un usuario de app que no es root.
- Fundación de la API:
  - routing bajo `/api`, con respuestas de error siempre en JSON;
  - endpoint `GET /api/health`, que verifica la conexión a la base sin exponer detalles internos;
  - convención de validación con Form Requests (documentada; esta fase no tiene un endpoint que la use).
- Tests de PHPUnit contra MySQL para el health check y para el formato de los errores.
- Workflow de GitHub Actions: build de la imagen sin publicarla y tests dentro de ella contra MySQL 8.4. Corre en los PRs y en los push a `main`, con permisos mínimos.
- Documentación en español:
  - onboarding en Windows (Docker Desktop + WSL 2), con comandos y troubleshooting;
  - la arquitectura multi-tenant y el trabajo diferido;
  - cómo se va a publicar en GHCR en el futuro.

## Capabilities

### New Capabilities

- `local-dev-environment`: entorno de desarrollo reproducible con Docker Compose. Cubre los servicios, la persistencia de datos, el aislamiento de red, la espera hasta que la base está lista y el manejo de secretos.
- `api-health`: endpoint público que informa el estado de la aplicación y de su base central.
- `api-error-responses`: formato JSON consistente para los errores de las rutas `/api/*`.
- `ci-verification`: verificación automática (build de la imagen y tests) en cada PR y en cada push a `main`, sin publicar artefactos.

### Modified Capabilities

Ninguna: es el primer change del repo.

## Impact

- **Código:** el skeleton completo de Laravel, más `bootstrap/app.php`, `routes/api.php`, un controller de health y los tests en `tests/Feature/`.
- **Infraestructura local:** `docker/`, `compose.yaml`, `.dockerignore` y `.env.example`.
- **CI:** `.github/workflows/ci.yml`. No se publica en GHCR ni se crean secrets en el repo.
- **Dependencias:** `laravel/framework` ^13. Sanctum queda para el change de auth.
- **Prerequisito:** el dueño tiene que instalar Docker Desktop (una instalación global que no hace el agente) antes de generar el skeleton y correr los tests.
- **Repo del front:** sus docs siguen diciendo `olim_platform`. Hay que actualizarlos en un change aparte, en ese repo.
- **Fuera de alcance:** autenticación/Sanctum, la conexión `tenant`, el alta de clubes, `tenants:migrate`, los módulos de negocio, la imagen de producción, la publicación en GHCR y el deploy en Laravel Cloud.
