# Design

## Context

- **Permisos:** el catálogo ya tiene `announcements.view` y `announcements.send`, con alcance (`config/club-permissions.php`): el coordinador tiene todo el club, el coordinador de deporte `sport` y el profe `own`. `ClubAccess::scopedCategoryIds($permission)` devuelve las categorías del alcance, o `null` si alcanza a todo el club.
- **Emails disponibles:**
  - los contactos del jugador (`player_contacts.email`, con `relationship`, incluido `self`);
  - los profes (`coaches.email`);
  - el staff, a través de `club_users.global_user_id`, con el email en `users` de la base central.
  - El jugador no tiene un campo de email propio.
- **Mail:** `BaseMailable` (`ShouldQueue`) y la plantilla Markdown con la marca. El parser Markdown de los mails de Laravel bloquea los links inseguros (`allow_unsafe_links: false`), pero **no** escapa el HTML por defecto: deja pasar `<script>` u otro HTML del cuerpo.
- **TIZ-37** (change `callup-email-notice`, ya en `main`) agrega el limiter `mail`, el patrón de jobs "uno reparte, otro manda" y `TenantDatabase::run` anidable. Este change los reutiliza.
- **Elegir personas del club:** `GET /api/club-users` (Facundo, FRO-19) ya lista las personas activas del club con `id` y `display_name`. El front lo usa para armar `club_user_ids` de la audiencia; este change no necesita otro endpoint para eso.
- **Convención de alcance:** fuera de alcance, las rutas con un recurso responden 404 (`ChecksClubScope`). Las referencias dentro del body no son rutas, así que responden 403, como pide el ticket.

## Goals / Non-Goals

**Goals:**

- Que el historial muestre exactamente a quién se le mandó, aunque después cambien los datos del club.
- Que ni las direcciones ni el contenido viajen en el payload de los jobs.
- Que un reintento no mande dos veces el mismo mail.

**Non-Goals:**

- Link de baja automática y preferencias por persona (ver la política en `proposal.md`).
- Programar envíos a futuro, adjuntos e imágenes en el cuerpo.
- Seguimiento de rebotes y aperturas: `sent` significa que el proveedor aceptó el mail.
- Reintentar a mano los fallidos desde la API. Se puede sumar después con un endpoint `retry-failed`.

## Decisions

### 1. Nombre: `announcements`, no `communications`

El ticket habla de `communications`, pero el catálogo de permisos, la matriz de roles y el módulo del front ya usan `announcements`. Se usa `announcements` en tablas, rutas y clases para no tener dos nombres para lo mismo.

### 2. Modelo de datos (base del club)

- `announcements`: `id`, `subject` (150), `body` (text, Markdown, hasta 10.000 caracteres), `audience` (json), `status` (`draft` / `sending` / `sent`), `created_by` (club_user), `sent_by` (club_user, nullable), `sent_at` (nullable), `recipients_count`, `sent_count`, `failed_count`, timestamps y soft delete.
- `announcement_recipients`: `id`, `announcement_id`, `email` (normalizado), `name` (para el saludo y el historial), `sources` (json: de dónde salió, por ejemplo `[{type: "player_contact", id: 12}]`), `status` (`pending` / `sent` / `failed`), `error` (nullable, truncado), `sent_at` (nullable) y timestamps.
  - Único `(announcement_id, email)`: es la deduplicación a nivel de base.

**Por qué congelar los destinatarios en una tabla:** el historial tiene que decir a quién se le mandó en ese momento. Además, el job de envío lee la dirección desde una fila de la base del club, no desde el payload.

### 3. Formato de la audiencia

```json
{
  "categories": [{ "category_id": 3, "players": true, "tutors": true, "coaches": false }],
  "role_slugs": ["coordinator"],
  "players": { "player_ids": [41], "players": true, "tutors": true },
  "club_user_ids": [7]
}
```

Lo valida `StoreAnnouncementRequest`: los IDs existen, los roles existen, y tiene que haber al menos un destino. Lo resuelve `App\Club\Announcements\AudienceResolver`, que devuelve una lista de `{email normalizado, name, source}` ya agrupada por email. Lo usan tanto la vista previa como el envío, así los dos cuentan lo mismo.

- **Jugadores:** contactos `relationship = self` con email, de los jugadores con asignación activa en la categoría, o de los `players.player_ids` (con los mismos flags `players` y `tutors`).
- **Tutores:** los demás contactos con email de esos jugadores. Son todos los tutores, no solo el principal, porque un aviso como "se suspende el entrenamiento" tiene que llegar a las dos familias.
- **Profes:** `coaches` con asignación vigente en la categoría (`CategoryCoach::current()`) y con email.
- **Staff por rol:** `club_users` activos con alguno de esos roles. El email sale de `users` en la base central, con una sola consulta por `global_user_id`.

### 4. Alcance

`AnnouncementScope::check(ClubAccess, audience)` corre al crear, al editar y al enviar:

- Con `scopedCategoryIds('announcements.send')` en `null`: todo vale.
- Con una lista de categorías:
  - cada `category_id` tiene que estar en la lista;
  - cada uno de `players.player_ids` tiene que tener una asignación activa en alguna de esas categorías;
  - `role_slugs` y `club_user_ids` tienen que estar vacíos.
  - Si algo no se cumple: 403.

Para el listado, si `announcements.view` tiene alcance limitado, se filtra por `created_by` = el usuario actual.

### 5. Envío con dos jobs (igual que TIZ-37)

1. `AnnouncementController::send`, dentro de una transacción de la base del club:
   - verifica que esté en `draft` (si no, 409) y vuelve a chequear el alcance;
   - resuelve la audiencia y responde 422 si no queda ninguna dirección;
   - inserta los `announcement_recipients` en `pending`;
   - pasa a `sending`, con `sent_by`, `sent_at` y `recipients_count`;
   - despacha `DispatchAnnouncement(tenantId, announcementId)->afterCommit()`.
2. `DispatchAnnouncement` despacha un `DeliverAnnouncementEmail(tenantId, recipientId)` por cada destinatario en `pending`. Es idempotente, porque cada `Deliver` mira el estado de su fila.
3. `DeliverAnnouncementEmail` corre dentro de `TenantDatabase::run`:
   - si la fila no está en `pending`, termina;
   - si lo está, manda con `sendNow` y la pasa a `sent`;
   - usa `RateLimited('mail')`, `$tries = 3`, `$backoff = [10, 60]` y `$timeout = 30`;
   - en `failed()` deja la fila en `failed`, con el error truncado y sin la dirección en el log.
4. Después de cada fila terminada, se actualizan los contadores. Si no quedan `pending`, el comunicado pasa a `sent` con un `update ... where status = 'sending'` atómico, así solo un job lo cierra.

**Por qué los destinatarios se resuelven en el request y no en el job:** el request ya tiene el contexto del club y el usuario. Si la audiencia queda vacía, se responde 422 enseguida. Un club no supera unos cientos de direcciones, así que entra en el request sin problema.

### 6. Mailable

`App\Mail\AnnouncementMail extends BaseMailable`, que se manda con `sendNow`:

- **Asunto:** `[<club>] <subject>`.
- **`Reply-To`:** el email del usuario que lo envió (`sent_by` → `users.email`).
- **Vista** `resources/views/mail/announcement.blade.php`: saludo con el nombre del destinatario, el cuerpo y un pie: "Recibís este mail porque estás registrado en <club>. Si no querés recibir más comunicados, pedíselo al club."
- **Cuerpo:** la vista lo imprime con `{{ $body }}`, así Blade escapa el HTML antes de que se convierta el Markdown del mail. El formato Markdown funciona, el HTML se ve como texto y los links `javascript:` los bloquea el parser. El test lo verifica.

### 7. Endpoints

| Método y ruta | Permiso | Nota |
|---|---|---|
| `GET /api/announcements` | `announcements.view` | Paginado. Filtro `status` |
| `POST /api/announcements` | `announcements.send` | Crea en `draft` |
| `GET /api/announcements/{announcement}` | `announcements.view` | Con los contadores |
| `PATCH /api/announcements/{announcement}` | `announcements.send` | Solo en `draft` (si no, 409) |
| `DELETE /api/announcements/{announcement}` | `announcements.send` | Solo en `draft`. Soft delete |
| `POST /api/announcements/preview-audience` | `announcements.send` | No guarda nada |
| `POST /api/announcements/{announcement}/send` | `announcements.send` | 409, 422 o 202 |
| `GET /api/announcements/{announcement}/recipients` | `announcements.view` | Paginado. Filtro `status` |

`send` responde **202** con el `AnnouncementResource` en `sending`, porque el envío sigue en la cola.

## Risks / Trade-offs

- [Un comunicado a todo el club manda cientos de mails] → el limiter `mail` (2 por segundo) los espacia. Con 300 destinatarios tarda unos 2 o 3 minutos, y el historial muestra el avance.
- [El plan gratuito de Resend tiene un tope diario] → un club grande puede pasarlo. Se anota en `docs/mail-produccion.md`, y los fallos quedan en `failed` con el error del proveedor.
- [No hay link de baja] → es aceptable para avisos operativos del club a sus propios miembros. Si el club lo pide, se suma una baja por dirección en otro change.
- [Las direcciones y nombres en `announcement_recipients` son datos personales] → viven en la base del club, como los contactos. Se borran junto con el comunicado (soft delete) y nunca salen en los logs.
- [Depende de TIZ-37] → si TIZ-37 se demora, las tareas del grupo 1 de `tasks.md` dicen qué traer de ese change.

## Migration Plan

1. Migración de tenant con las dos tablas nuevas. Se aplica con `tenants:migrate`. No toca datos existentes.
2. No hay cambios de permisos: `announcements.*` ya está en el catálogo y en los roles.
3. Rollback: revertir el código. Las tablas pueden quedar, porque nadie más las lee.
