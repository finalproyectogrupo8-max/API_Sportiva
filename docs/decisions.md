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

## Imágenes fijadas

Resueltas el 2026-10-16 con `docker buildx imagetools inspect <tag>`. El digest es el del índice multi-arquitectura.

| Uso | Imagen |
|---|---|
| PHP (Dockerfile, `base`) | `php:8.5.11-cli-trixie@sha256:01a109229f4465bc9ef042d9198f09a4d9da7775a825dbd8572dcdbd8756c4d3` |
| Composer (Dockerfile, `dev`) | `composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac` |
| MySQL (`compose.yaml`) | `mysql:8.4.11@sha256:24989179f58856db763f418010c1169fecc944e3ec1ea7e921572d83eb070f70` |

**Cómo actualizar:** correr `docker buildx imagetools inspect php:8.5-cli-trixie` (y lo mismo con `mysql:8.4` y `composer:2`), reemplazar el tag y el digest en el Dockerfile y en `compose.yaml`, actualizar esta tabla y abrir un PR para que la CI valide el build.

## Open decisions

| Question | Options | Needed by | Owner |
|---|---|---|---|
| ¿Multi-tenancy con un paquete o con una implementación propia? | `stancl/tenancy`; implementación propia (conexión `tenant` + `DB::purge`) | Antes del change de la conexión tenant | Equipo |
| ¿Cómo se separan los usuarios de BD (operación y provisión)? | Dos usuarios en el cluster de Laravel Cloud; uno solo con permisos amplios | Antes del change de alta de clubes | Equipo |
| ¿Se publica una imagen de desarrollo en GHCR? | Publicar en `main` y en tags; no publicar | Cuando el equipo lo pida | Dueño del repo |
| Región de Laravel Cloud y Vercel | São Paulo (si está disponible en las dos); US East | Antes del primer deploy | Equipo |
