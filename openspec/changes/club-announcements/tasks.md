# Tasks

## 1. Requisitos previos

- [ ] 1.1 Verificar que el change `callup-email-notice` (TIZ-37) esté mergeado en `main`: limiter `mail` en `AppServiceProvider`, `TenantDatabase::run` anidable y jobs en `app/Jobs/Club/`. Si no está, frenar y avisar. No reimplementar esas piezas acá.

## 2. Datos

- [ ] 2.1 Crear la migración de tenant con `announcements` (soft delete) y `announcement_recipients` (único `(announcement_id, email)`), los modelos `Announcement` y `AnnouncementRecipient` con sus constantes de estado, `@property` y factories. Verificar con `php artisan test` que la migración corra en las bases de test de los dos clubes.

## 3. Audiencia y alcance

- [ ] 3.1 Crear `App\Club\Announcements\AudienceResolver`. Tests unitarios:
  - jugadores (`self`) y tutores de una categoría, solo con asignaciones activas;
  - profes vigentes de la categoría;
  - staff por rol, con el email de la base central;
  - jugadores sueltos;
  - deduplicación por email (mayúsculas y espacios);
  - las personas sin email se cuentan aparte.
- [ ] 3.2 Crear `App\Club\Announcements\AnnouncementScope`. Tests:
  - alcance de todo el club acepta todo;
  - profe con su categoría, OK;
  - profe con una categoría ajena, 403;
  - profe con un jugador de otra categoría, 403;
  - profe con `role_slugs` o `club_user_ids`, 403.

## 4. Borradores y vista previa

- [ ] 4.1 Crear `StoreAnnouncementRequest`, `UpdateAnnouncementRequest`, `IndexAnnouncementRequest`, `AnnouncementResource` y `AnnouncementController` (index, store, show, update, destroy), con las rutas en `routes/api.php`. Tests en `AnnouncementApiTest`, siguiendo los mínimos de `docs/guia-abm.md`:
  - CRUD de borradores y paginación;
  - 422 por datos inválidos y por audiencia vacía;
  - 409 al editar o borrar un comunicado que no está en `draft`;
  - 403 sin permiso;
  - el profe ve solo los suyos;
  - aislamiento entre clubes (404).
- [ ] 4.2 Agregar `POST /api/announcements/preview-audience`. Tests: devuelve la cantidad deduplicada, los que no tienen email y una muestra de hasta 10 nombres; no crea filas ni manda mails; aplica el alcance (403).

## 5. Envío

- [ ] 5.1 Crear `App\Mail\AnnouncementMail` y la vista `resources/views/mail/announcement.blade.php` (asunto `[<club>] <subject>`, `Reply-To` del remitente, saludo, cuerpo Markdown y pie con la política de baja). Test: renderiza el cuerpo, escapa `<script>` y no deja un link `javascript:` ejecutable.
- [ ] 5.2 Crear los jobs `DispatchAnnouncement` y `DeliverAnnouncementEmail`, siguiendo `docs/guia-jobs.md`. Tests:
  - camino feliz;
  - idempotencia: correr `Deliver` dos veces manda un solo mail;
  - `failed()` deja la fila en `failed` sin loguear la dirección;
  - cierre del comunicado en `sent` con los contadores correctos;
  - trabaja sobre el club correcto (`tenantA`, `tenantB`).
- [ ] 5.3 Agregar `POST /api/announcements/{announcement}/send`. Tests con `Mail::fake`:
  - 202 y un mail por dirección ("Sub-15 + tutores" con dos hermanos → la madre recibe uno solo);
  - 409 si ya se envió;
  - 422 sin destinatarios con email;
  - 403 si el profe incluyó una categoría ajena.

## 6. Historial

- [ ] 6.1 Agregar `GET /api/announcements/{announcement}/recipients` con `AnnouncementRecipientResource` y filtro `status`. Tests: lista los destinatarios con su estado y su error, aplica el alcance y pagina.

## 7. Contrato y documentación

- [ ] 7.1 Regenerar `docs/api/openapi.json` con `scramble:export` y verificar que estén los ocho endpoints.
- [ ] 7.2 Documentar el módulo en `docs/modelo-datos-club/` (las dos tablas y la audiencia) y la política de baja. Sumar a `docs/mail-produccion.md` el aviso sobre el tope diario del proveedor. Verificar que los nombres coincidan con las migraciones y las rutas.

## 8. Verificación integrada

- [ ] 8.1 Ejecutar `docker compose exec backend php artisan test` y `docker compose exec backend vendor/bin/pint --test`, y verificar que todo pase.
- [ ] 8.2 En local, con el worker (`docker compose restart queue`): crear un comunicado a una categoría con tutores, ver la vista previa, enviarlo y verificar en Mailpit (`http://localhost:8025`) un mail por dirección, con el `Reply-To` del remitente. Después, verificar en `/recipients` que todos queden en `sent`.

## Workflow follow-up

- Avisar en FRO-23 que la API está lista, con el formato de la audiencia.
- Pasar TIZ-24 a In Review en Linear cuando se abra el PR, y a Done cuando se mergee.
- Archivar el change con `/opsx:archive` después del merge.
