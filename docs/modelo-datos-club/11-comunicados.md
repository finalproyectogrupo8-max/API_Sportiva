# Comunicados por mail

[← Índice del modelo](README.md)

Comunicados del club por mail a jugadores, tutores, profes y staff (TIZ-24). El club escribe un borrador, ve a cuántos les llega, lo envía y después ve a quién se le entregó.

## `announcements`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | |
| `subject` | VARCHAR(150) | no | | |
| `body` | TEXT | no | | Markdown, hasta 10.000 caracteres. El HTML se muestra como texto en el mail |
| `audience` | JSON | no | | A quién va (ver abajo) |
| `status` | VARCHAR(20) | no | default `draft` | `draft` / `sending` / `sent`. Solo se edita y se borra en `draft` (si no, 409) |
| `created_by` | BIGINT UNSIGNED | no | FK → `club_users.id` | |
| `sent_by` | BIGINT UNSIGNED | sí | FK → `club_users.id` | Su email va como `Reply-To`: las respuestas le llegan a él |
| `sent_at` | TIMESTAMP | sí | | |
| `recipients_count`, `sent_count`, `failed_count` | INT UNSIGNED | no | default 0 | Para el historial |
| `created_at`, `updated_at`, `deleted_at` | TIMESTAMP | sí | | Soft delete |

## `announcement_recipients`

Se crea al enviar: la lista queda congelada, así el historial dice a quién se le mandó aunque después cambien los datos. Contiene **datos personales**: no va a los logs ni al payload de los jobs.

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | |
| `announcement_id` | BIGINT UNSIGNED | no | FK → `announcements.id`, `ON DELETE CASCADE` | |
| `email` | VARCHAR(255) | no | | Normalizado (minúsculas, sin espacios) |
| `name` | VARCHAR(150) | no | | Para el saludo y el historial |
| `sources` | JSON | no | | De dónde salió: `[{type: player_contact \| coach \| club_user, id}]` |
| `status` | VARCHAR(20) | no | default `pending` | `pending` / `sent` / `failed` |
| `error` | VARCHAR(255) | sí | | Error del proveedor, si falló después de 3 intentos |
| `sent_at` | TIMESTAMP | sí | | |
| `created_at`, `updated_at` | TIMESTAMP | sí | | |

- `UNIQUE (announcement_id, email)`: una dirección recibe un solo mail aunque aparezca varias veces (un tutor con dos hijos).

## La audiencia

```json
{
  "categories": [{ "category_id": 3, "players": true, "tutors": true, "coaches": false }],
  "players": { "player_ids": [41], "players": true, "tutors": true },
  "role_slugs": ["admin"],
  "club_user_ids": [7]
}
```

| Destino | Email que se usa |
|---|---|
| Jugadores (de la categoría o sueltos) | Su contacto con relación `self` |
| Tutores | Todos sus demás contactos con email, no solo el principal |
| Profes de la categoría | `coaches.email`, de los profes con asignación vigente |
| Staff por rol y usuarios del club sueltos | El email de su usuario en la base central. El front los elige con `GET /api/club-users` |

De las categorías se toman solo los jugadores con asignación activa.

## Endpoints y alcance

| Método y ruta | Permiso |
|---|---|
| `GET/POST /api/announcements`, `GET/PATCH/DELETE /api/announcements/{id}` | `announcements.view` para leer, `announcements.send` para escribir |
| `POST /api/announcements/preview-audience` | `announcements.send`. Devuelve la cantidad de direcciones, quiénes quedan sin email y una muestra. No guarda nada |
| `POST /api/announcements/{id}/send` | `announcements.send`. 202; 409 si no está en `draft`; 422 si nadie tiene email |
| `GET /api/announcements/{id}/recipients` | `announcements.view`. Filtro `status` |

- Con alcance limitado (profe: sus categorías; coordinador de deporte: sus deportes), solo se eligen categorías y jugadores del alcance, y nunca staff por rol ni usuarios del club (403). En el listado, cada uno ve solo los comunicados que creó.
- El envío corre en la cola: `DispatchAnnouncement` reparte y `DeliverAnnouncementEmail` manda un mail por destinatario, con el límite `mail` (2 por segundo). Un reintento no repite el mail ([guía de jobs](../guia-jobs.md)).
- **Baja:** no hay link de baja. El pie del mail dice que, para dejar de recibirlos, hay que pedírselo al club.
