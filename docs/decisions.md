# Decision log

Decisiones cortas que no necesitan un ADR completo. Cuando una decisión se vuelve difícil de revertir, se promueve a ADR.

| Date | Decision | Reason | Alternatives | Owner |
|---|---|---|---|---|
| 2026-10-16 | La app Laravel va en la raíz del repo | El repo es solo la API, y Laravel Cloud espera la app en la raíz | `backend/` | Dueño del repo |
| 2026-10-16 | La base central se llama `sportiva_platform` | El proyecto es Sportiva. `olim_platform` era un nombre heredado | Mantener `olim_platform` | Dueño del repo |
| 2026-10-16 | En la Fase 1 la CI corre los tests y hace el build de la imagen sin publicarla. GHCR se difiere | Laravel Cloud no usa imágenes, así que una imagen publicada solo sirve para desarrollo | Publicar en GHCR ahora; solo documentar | Dueño del repo |
| 2026-10-16 | Se usa OpenSpec para los cambios no triviales | Proyecto de larga vida con 4 devs | Planes livianos | Dueño del repo |
| 2026-10-16 | La documentación y los artefactos de OpenSpec van en español | Es la convención del equipo, igual que en el repo del front | Inglés | Dueño del repo |
| 2026-10-16 | Imágenes base fijadas por tag de patch y digest (ver la tabla de abajo) | Builds reproducibles; nunca `latest` | Tags flotantes (`8.5`, `8.4`) | Dueño del repo |
| 2026-10-16 | Debian trixie como base de la imagen PHP | Es el stable vigente (Debian 13); bookworm ya es oldstable | bookworm, Alpine | Dueño del repo |
| 2026-10-16 | Setup automatizado con `scripts/setup.sh` y `scripts/setup-windows.ps1` | Los devs no tienen que configurar WSL ni Docker a mano | Dev Container; solo documentar | Dueño del repo |
| 2026-10-17 | Nombres de código en **inglés**: tablas, modelos, rutas, campos y enums (`players`, `Player`, `/api/players`, `birth_date`). La UI sigue en español: la traduce el front | Coherente con los enums (ya en inglés), con la pluralización de Laravel y con el contrato que consume el front | Español (`jugadores`, `Jugador`) | Dueño del repo |
| 2026-10-17 | El panel de administración de plataforma va en el front (páginas `/admin` de Front-Sportiva). La API expone `/api/admin/*` y no tiene UI | Respeta el BFF y la decisión de que la API no tenga frontend | Filament dentro de la API; solo comandos | Dueño del repo |
| 2026-10-17 | La guía de ABM ([guia-abm.md](guia-abm.md)) se adelanta como **propuesta**, para que el equipo arranque con las mismas reglas. Se confirma y se ajusta con el primer ABM real (usuarios y clubes del panel admin): si choca con ese código, gana el código | El equipo necesita las reglas antes de que exista el primer ABM | Esperar al primer ABM real | Dueño del repo |
| 2026-10-18 | Multi-tenancy con implementación propia (conexión `tenant` + `DB::purge`), sin `stancl/tenancy`. Ver [ADR 0002](adr/0002-multi-tenancy-implementacion-propia.md) | Hace falta poco, y el paquete trae convenciones que no se usan y hace `USE`, que el proxy de Laravel Cloud rechaza | `stancl/tenancy` | Equipo |

## Imágenes fijadas

Resueltas el 2026-10-16 con `docker buildx imagetools inspect <tag>`. El digest es el del índice multi-arquitectura. PHP se actualizó a 8.5.12 el 2026-10-22 por CVE-2026-109618 y CVE-2026-109619 ("php2shell").

| Uso | Imagen |
|---|---|
| PHP (Dockerfile, `base`) | `php:8.5.12-cli-trixie@sha256:595a7cbb1e0547336319f228c4dc991c2a199350ab12b1c8fda05f73abd06e6b` |
| Composer (Dockerfile, `dev`) | `composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac` |
| MySQL (`compose.yaml`) | `mysql:8.4.11@sha256:24989179f58856db763f418010c1169fecc944e3ec1ea7e921572d83eb070f70` |

**Cómo actualizar:** correr `docker buildx imagetools inspect php:8.5-cli-trixie` (y lo mismo con `mysql:8.4` y `composer:2`), reemplazar el tag y el digest en el Dockerfile y en `compose.yaml`, actualizar esta tabla y abrir un PR para que la CI valide el build.

## Swagger UI fijado

Lo usa `/api/docs` (change `add-api-docs`). Se carga desde jsDelivr con integridad SRI. Se eligió la última versión publicada hace al menos 14 días, para no adoptar una release recién publicada.

| Archivo | Versión | SRI |
|---|---|---|
| `swagger-ui.css` | `swagger-ui-dist@5.33.1` (2026-10-01) | `sha384-Ov4/wv3j2bmct8cDc5X4ngJZohVPzEmc6uDPH8WeljUxO5vtoykvMEfbu9Vh6RaW` |
| `swagger-ui-bundle.js` | `swagger-ui-dist@5.33.1` (2026-10-01) | `sha384-ZPehFMQommnnuaZ4rpxgkgTT2DKFVp4hZC/7pLit+9Lek9T1YGSo23eHFbvNkXkw` |

Verificado el 2026-10-16: los archivos de jsDelivr son idénticos a los del tarball de npm, cuyo `sha512` coincide con el `dist.integrity` del registry.

**Cómo actualizar:**

1. Elegir una versión publicada hace al menos 14 días (`https://registry.npmjs.org/swagger-ui-dist`, campo `time`).
2. Descargar los dos archivos de `https://cdn.jsdelivr.net/npm/swagger-ui-dist@<versión>/<archivo>` y calcular cada hash con `openssl dgst -sha384 -binary <archivo> | openssl base64 -A`.
3. Comparar con los mismos archivos del tarball de npm.
4. Actualizar `config/scramble.php` (`renderers.swagger`) y esta tabla.

## Open decisions

| Question | Options | Needed by | Owner |
|---|---|---|---|
| ¿Cómo se separan los usuarios de BD (operación y provisión)? | Dos usuarios en el cluster de Laravel Cloud; uno solo con permisos amplios | Antes del change de alta de clubes | Equipo |
| ¿Se publica una imagen de desarrollo en GHCR? | Publicar en `main` y en tags; no publicar | Cuando el equipo lo pida | Dueño del repo |
| Región de Laravel Cloud y Vercel | São Paulo (si está disponible en las dos); US East | Antes del primer deploy | Equipo |
