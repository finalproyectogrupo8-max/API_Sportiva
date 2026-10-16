# 0001. Stack y estructura del repo de la API

- Status: accepted
- Date: 2026-10-16

## Context

- El producto ya tiene decisiones de arquitectura tomadas en el repo del frontend ([Front-Sportiva](https://github.com/finalproyectogrupo8-max/Front-Sportiva)):
  - ADR 0001 (front): BFF, tokens Sanctum atados al tenant, una base de datos por club.
  - ADR 0002 (front, propuesto): Vercel para el frontend y Laravel Cloud para la API. Laravel Cloud compila desde Git y no acepta imágenes Docker propias.
  - `docs/integracion-api-laravel.md` (front): contrato de la API y diseño de las bases por club.
- El repo de la API (`API_Sportiva`) estaba vacío el 2026-10-16.
- El equipo son 4 devs, la mayoría en Windows. El PHP local del dueño es 8.2 (XAMPP), que no alcanza para Laravel 13.
- Un spike en Laravel Cloud (2026-10-15) usó MySQL 8.4.

## Decision

- **Laravel 13**: es la versión estable actual. Requiere PHP ≥ 8.3, tiene bugfixes hasta Q3 2027 y parches de seguridad hasta Q1 2028 ([release notes](https://laravel.com/docs/13.x/releases.md)).
- **PHP 8.5**: Laravel 13 lo soporta y es el default de Laravel Cloud para entornos nuevos ([runtimes](https://laravel.com/cloud/docs/runtimes.md)). Si una extensión necesaria no está disponible para 8.5, el fallback es 8.4.
- **MySQL 8.4 LTS**: la misma versión que el cluster de Laravel Cloud del spike.
- **La app Laravel va en la raíz del repo**: el repo contiene solo la API, porque el frontend vive en otro repo. Es la estructura que Laravel Cloud espera por defecto.
- **Base central `sportiva_platform`**: reemplaza al nombre `olim_platform` de los docs del front. Esos docs se actualizan en un cambio aparte, en ese repo.
- **Desarrollo local con Docker Compose**: los devs no instalan PHP, Composer ni MySQL en el host. El diseño está en el change `add-docker-dev-foundation`.

## Alternatives considered

- **Laravel 12**: tiene soporte de seguridad más corto y nada lo obliga, porque el proyecto arranca de cero.
- **PHP 8.4**: es igual de válido. Se elige 8.5 para alinearse con el default de Laravel Cloud y tener una ventana de soporte más larga.
- **La app en `backend/`**: suma un nivel sin beneficio en un repo que solo tiene la API, y obliga a configurar el directorio en Laravel Cloud.
- **PHP/Composer instalados en cada máquina**: genera diferencias de versión entre los devs, y el PHP local del dueño (8.2) ya es incompatible.

## Consequences

- El entorno local con Docker no es el de producción: Laravel Cloud construye su propio runtime. Las versiones de PHP y MySQL tienen que mantenerse alineadas a mano entre `docker/`, la CI y la configuración de Laravel Cloud.
- Una imagen publicada en GHCR solo sirve para desarrollo y CI, no para el deploy. Su publicación se difiere a un change posterior.
- Hay que revisar esta decisión cuando salga Laravel 14 (se espera para Q1 2027).
