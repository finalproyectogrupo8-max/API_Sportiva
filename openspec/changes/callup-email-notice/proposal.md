# Proposal

## Why

Hoy el aviso de la convocatoria sale solo por WhatsApp: el profe tiene que tocar un link `wa.me` por cada jugador, y la familia que no tiene teléfono cargado no se entera. Con la infraestructura de mail lista (TIZ-21, change `mail-infrastructure`), el aviso puede salir solo al publicar, por mail, al contacto principal de cada convocado (TIZ-37).

## What Changes

- Al publicar la convocatoria (`POST /api/matches/{match}/callup/publish`), la API encola el aviso por mail al contacto principal con email de cada convocado. El mail lleva los datos del partido y el link personal para confirmar, con el mismo texto que el mensaje de WhatsApp (`CallupMessages`).
- Volver a publicar después de un cambio en el partido (`updated_after_publish`) avisa a todos de nuevo, con un mail que dice "Hubo cambios". Volver a publicar sin cambios avisa solo a los convocados que todavía no recibieron el mail (por ejemplo, los que se sumaron a la lista).
- Nuevo `POST /api/matches/{match}/callup/remind`: reenvía el aviso solo a los convocados que siguen en `pending`. Requiere la convocatoria publicada y el permiso `callups.publish` con su alcance.
- Deduplicación: si un mismo email es el contacto principal de varios convocados del partido (un tutor con dos hijos), recibe un solo mail con el link personal de cada uno.
- Cada convocado registra cuándo se le mandó el último aviso (`notified_at`). La convocatoria lo devuelve por jugador, junto con si su contacto principal tiene email, para que el profe vea a quién le llegó y a quién tiene que avisar por WhatsApp.
- El WhatsApp sigue igual: el mail se suma, no lo reemplaza.

## Capabilities

### New Capabilities

Ninguna.

### Modified Capabilities

- `club-callups`: la publicación avisa por mail a los contactos principales, se agrega el recordatorio a los pendientes y la convocatoria expone el estado del aviso por jugador.

## Impact

- **API:** `POST /api/matches/{match}/callup/publish` (ahora encola mails), nuevo `POST /api/matches/{match}/callup/remind`, y dos campos nuevos por fila en `CallupSheetResource` (`notified_at`, `has_email`). Hay que regenerar `docs/api/openapi.json`.
- **Base del club:** migración de tenant que agrega `match_callups.notified_at`.
- **Código:** `CallupService`, `CallupController`, `CallupMessages` (texto del mail), jobs nuevos en `app/Jobs/Club/`, un Mailable sobre `BaseMailable` y su vista Markdown en `resources/views/mail/`.
- **Cola:** primeros jobs de club que mandan mails. Corren en el worker existente (servicio `queue` en local; la cola de Laravel Cloud en producción).
- **Front (fuera de este change):** puede mostrar `notified_at` y `has_email`, y un botón "Recordar a los pendientes". No es necesario para que el aviso funcione.
