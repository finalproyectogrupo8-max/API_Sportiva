# Proposal

## Why

Hoy el club no tiene cómo mandar un aviso general desde el sistema. Por ejemplo: "Se suspende el entrenamiento de Sub-15" o "Retirar indumentaria el sábado". El profe lo resuelve por fuera, grupo por grupo, y no queda registro de a quién le llegó. Con la infraestructura de mail lista (TIZ-21), el club puede mandar comunicados por mail desde el sistema, con destinatarios armados a partir de sus datos y con historial (TIZ-24). Es la API del módulo "Comunicados" del front (FRO-23).

## What Changes

- **Comunicados en borrador:** `apiResource('announcements')` con asunto, cuerpo en Markdown y audiencia. Se pueden editar y borrar solo mientras están en `draft`.
- **Audiencia**, combinable:
  - una o más categorías, eligiendo jugadores, tutores o los dos;
  - los profes de esas categorías;
  - el staff con uno o más roles del club;
  - jugadores sueltos y usuarios del club sueltos.
- **Vista previa:** `POST /api/announcements/preview-audience` devuelve, antes de enviar, cuántos recibirían el mail, cuántos quedan afuera por no tener email y una muestra de nombres.
- **Envío:** `POST /api/announcements/{announcement}/send` congela la lista de destinatarios y encola un mail por dirección. Una dirección recibe un solo mail aunque aparezca varias veces, por ejemplo un tutor con dos hijos en la categoría.
- **Historial:** cada comunicado guarda quién lo mandó, cuándo, a cuántos, y cuántos se entregaron o fallaron. `GET /api/announcements/{announcement}/recipients` lista los destinatarios con su estado.
- **Alcance:** se usan los permisos existentes `announcements.view` y `announcements.send`, con sus alcances. Quien tiene alcance limitado (profe: sus categorías; coordinador de deporte: sus deportes) solo puede elegir categorías y jugadores dentro de su alcance. Si incluye algo fuera de su alcance, responde 403. El staff por rol y los usuarios del club solo los puede elegir quien tiene alcance sobre todo el club.
- **Respuestas:** el mail lleva `Reply-To` con el email del usuario que lo mandó, así las respuestas le llegan a él y no a una casilla `no-reply`.
- **Baja:** en esta versión no hay link para darse de baja. Se define la política: los comunicados son avisos operativos del club a sus jugadores, familias y staff. El pie del mail explica por qué lo recibe y que, para dejar de recibirlos, tiene que pedírselo al club. La baja automática queda para otro change.

## Capabilities

### New Capabilities

- `club-announcements`: comunicados por mail del club. Cubre borradores, audiencia, vista previa, envío deduplicado, historial con estado por destinatario y alcance por rol.

### Modified Capabilities

Ninguna.

## Impact

- **Base del club:** migración de tenant con las tablas `announcements` (soft delete) y `announcement_recipients`.
- **API:** endpoints nuevos bajo `/api/announcements`. Hay que regenerar `docs/api/openapi.json`.
- **Código:** modelos, Form Requests, Resources, controller, un resolvedor de audiencia, dos jobs de club (`app/Jobs/Club/`), un Mailable y su vista Markdown.
- **Permisos:** sin cambios en el catálogo. Se usan `announcements.view` y `announcements.send`, que ya existen.
- **Depende de TIZ-37** (change `callup-email-notice`). Reutiliza tres piezas que TIZ-37 agrega: el limiter `mail`, el patrón de jobs "uno reparte, otro manda" y el cambio en `TenantDatabase::run`. **Se implementa después de que TIZ-37 esté mergeado.**
- **Front (fuera de este change):** el módulo "Comunicados" (FRO-23).
