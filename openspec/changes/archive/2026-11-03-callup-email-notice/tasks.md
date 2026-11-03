# Tasks

## 1. Base: conexión de tenant anidada y columna `notified_at`

- [x] 1.1 Cambiar `TenantDatabase::run` para que, al salir, vuelva a apuntar la conexión `tenant` a la base que tenía antes de entrar (y la deje sin base si no tenía ninguna). Agregar tests en `tests/Feature/Tenancy/`: `run` dentro de otro club vuelve a ese club, y sin club previo queda en null. Verificar que `TenantContextTest` siga pasando.
- [x] 1.2 Crear la migración de tenant que agrega `match_callups.notified_at` (timestamp nullable) y sumar `@property` y el cast `immutable_datetime` en `MatchCallup`. Verificar con `php artisan test --filter=CallupApiTest` que los tests existentes sigan pasando.

## 2. Texto y Mailable del aviso

- [x] 2.1 En `CallupMessages`, exponer las líneas del partido que hoy arma `summary()` y agregar `mailSubject(GameMatch, Tenant, string $mode)` con los asuntos de `published`, `updated` y `reminder`. Agregar un test unitario que compare el resumen con el del mensaje de WhatsApp y cubra los tres asuntos.
- [x] 2.2 Crear `App\Mail\CallupNoticeMail extends BaseMailable` y la vista `resources/views/mail/callup-notice.blade.php` con la plantilla de marca: aviso de "Hubo cambios" si corresponde, datos del partido y un botón "Confirmar" por jugador. Agregar un test que renderice el Mailable con dos jugadores y verifique los dos links personales y el texto en español rioplatense.

## 3. Jobs de club

- [x] 3.1 Crear `App\Jobs\Club\SendCallupNotice` (IDs, `TenantDatabase::run`, `sendNow`, marca `notified_at`, descarta los convocados con `notified_at >= requestedAt`, `RateLimited('mail')`, `$tries = 3`, `$backoff = [10, 60]`, `$timeout = 30`, y `failed()` que loguea sin emails). Definir el limiter `mail` en `AppServiceProvider`. Tests: manda un mail, es idempotente si se ejecuta dos veces, trabaja sobre el club correcto con `tenantA` y `tenantB`, y `failed()` loguea sin datos personales.
- [x] 3.2 Crear `App\Jobs\Club\NotifyCallup`: elige a los convocados según el modo, salta a los que no tienen contacto principal con email, agrupa por email normalizado y despacha un `SendCallupNotice` por dirección. Tests: dos hermanos con el mismo email (en mayúsculas distintas) generan un solo mail, convocado sin email no recibe, modo `reminder` solo a pendientes, modo `published` solo a los que tienen `notified_at` null, y modo `updated` a todos.

## 4. Endpoints y contrato

- [x] 4.1 Hacer que `CallupService::publish` devuelva el modo (`updated` si ya estaba publicada con `updated_after_publish`, si no `published`) y que `CallupController::publish` despache `NotifyCallup` con el tenant del contexto y `->afterCommit()`. Tests en `CallupApiTest` con `Mail::fake`: publicar avisa a cada contacto con su link, republicar después de un cambio manda "Hubo cambios" a todos, y republicar sin cambios avisa solo al jugador nuevo.
- [x] 4.2 Agregar `POST /api/matches/{match}/callup/remind` en `routes/api.php` y `CallupController::remind` (`callups.publish` con su alcance; 422 si no está publicada). Tests: solo los pendientes reciben mail, 422 sin publicar, 403 sin permiso, 404 para un profe fuera de su categoría (la convención de alcance del repo), y aislamiento entre clubes.
- [x] 4.3 Sumar `has_email` y `notified_at` a `CallupRow` y a `CallupService::sheet`. Test: después de publicar, la convocatoria muestra `notified_at` en los que tienen email y `has_email = false` en los que no. Regenerar `docs/api/openapi.json` con `scramble:export` y verificar que el diff incluya el endpoint nuevo y los dos campos.
- [x] 4.4 Actualizar la documentación de convocatorias en `docs/` (o el modelo de datos del club, si es donde está la sección de `match_callups`) con el aviso por mail, los modos y el recordatorio. Verificar que los endpoints documentados coincidan con `routes/api.php`.

## 5. Verificación integrada

- [x] 5.1 Ejecutar `docker compose exec backend php artisan test` y `docker compose exec backend vendor/bin/pint --test`. CI del PR #77 quedó verde, Pint local pasa y `ApiDocsContractTest` verifica cobertura de las rutas en el contrato.
- [x] 5.2 Probar en local con el worker (`docker compose restart queue`): publicar una convocatoria y verificar en Mailpit (`http://localhost:8025`) un mail por dirección con los links personales. Después, pedir el recordatorio y verificar que llegue solo a los pendientes.

## Workflow follow-up

- Pasar TIZ-37 a In Review en Linear cuando se abra el PR, y a Done cuando se mergee.
- Archivar el change con `/opsx:archive` después del merge.
