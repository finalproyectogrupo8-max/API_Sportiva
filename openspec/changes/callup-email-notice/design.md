# Design

## Context

- La convocatoria (TIZ-33) ya se arma, se publica y se comparte por WhatsApp. `CallupService::publish` marca `callup_published_at`, genera el `public_token` y pone `updated_after_publish` en `false`. `MatchController::update` lo vuelve a `true` si cambia algo que ya se avisó.
- `CallupMessages` arma los textos y los links (`personalUrl()`, `summary()`). Su docblock ya anticipa que el mail tiene que decir lo mismo que WhatsApp.
- El contacto que recibe los avisos es el `PlayerContact` con `is_primary = true`. Su `email` es opcional.
- La infraestructura de mail (change `mail-infrastructure`) dejó `App\Mail\BaseMailable` (`ShouldQueue`), la plantilla Markdown con la marca y Mailpit en local. Todavía no hay ningún job de club: este es el primero.
- Reglas de [docs/guia-jobs.md](../../../docs/guia-jobs.md): IDs y no modelos, el trabajo del club dentro de `TenantDatabase::run`, idempotencia, `$timeout` menor que 90 s, sin datos personales en el payload, una sola cola (sin `onQueue`).
- En los tests la cola es `sync`: un job despachado desde un request corre en el mismo proceso, en el medio del request.

## Goals / Non-Goals

**Goals:**

- Que el payload de los jobs tenga solo IDs: ni emails ni nombres en la tabla `jobs`.
- Que cada intento de envío sea chico (un mail) y se pueda reintentar sin duplicar.
- Reutilizar el texto de `CallupMessages` para que WhatsApp y mail digan lo mismo.

**Non-Goals:**

- `Reply-To` con el email del club o del profe, y la baja de los avisos (unsubscribe). Se definen en TIZ-24 (comunicados), que tiene la política de baja.
- Avisar por mail a más de un contacto por jugador. Sigue siendo solo el principal, como en WhatsApp.
- Seguimiento de entrega (rebotes o aperturas). `notified_at` registra que el mail se entregó al proveedor, no que llegó.
- Cambios en el front.

## Decisions

### 1. Dos jobs: uno reparte y otro manda

- `App\Jobs\Club\NotifyCallup(tenantId, matchId, mode, requestedAt)` corre dentro de `TenantDatabase::run`. Elige a qué convocados avisar según el `mode`, agrupa por email normalizado (`mb_strtolower(trim())`) y despacha un `SendCallupNotice` por dirección.
- `App\Jobs\Club\SendCallupNotice(tenantId, matchId, callupIds, mode, requestedAt)` vuelve a leer los convocados y el email del contacto, manda **un** mail con `Mail::to(...)->sendNow(...)` y marca `notified_at = requestedAt` en esos convocados.

**Por qué:** con 60 convocados, mandar todo en un solo job puede pasar los 90 s de los workers Flex de Laravel Cloud. Cada reintento, además, reenviaría los mails que ya salieron. Encolar el Mailable armado (`Mail::queue`) dejaría emails y nombres de menores en texto plano en la tabla `jobs`, y la guía lo prohíbe.

**Alternativa descartada:** un solo job con un bucle de `sendNow`. Es más simple, pero tiene el riesgo de timeout y de reenvío parcial.

### 2. Modos y a quién se avisa

| `mode` | Cuándo | A quién | Asunto |
|---|---|---|---|
| `published` | Primera publicación, o republicación sin cambios en el partido | Convocados con `notified_at` null | "Convocatoria …" |
| `updated` | Republicación con `updated_after_publish = true` | Todos los convocados | "Hubo cambios: convocatoria …" |
| `reminder` | `POST …/callup/remind` | Convocados con `response = pending` | "Recordatorio: convocatoria …" |

`CallupService::publish` decide el modo **antes** de poner `updated_after_publish` en `false`, y devuelve el modo para que lo despache el controller.

Un grupo (una dirección) se manda con el modo del job, aunque mezcle convocados. En modo `reminder` el grupo incluye solo a los pendientes de esa dirección: si un tutor tiene un hijo que confirmó y otro pendiente, el mail menciona solo al pendiente.

### 3. Idempotencia con `notified_at` y `requestedAt`

- Migración de tenant: `match_callups.notified_at` (timestamp nullable).
- `requestedAt` es el momento en que se pidió el aviso (lo fija quien despacha) y viaja en los dos jobs.
- `SendCallupNotice` descarta los convocados con `notified_at >= requestedAt`: ya se les mandó **este** aviso. Si no queda ninguno, termina sin mandar.
- `notified_at` guarda el `requestedAt` del aviso, no la hora del envío: las dos columnas tienen precisión de segundos, y con la hora del envío un aviso pedido en el mismo segundo que el anterior se descartaría. Así, un reintento del mismo aviso da igual y se descarta, uno pedido después es mayor y sale, y dos pedidos en el mismo segundo cuentan como el mismo aviso.
- Marca `notified_at` **después** de `sendNow`. Si el proceso se corta entre el envío y la marca, el reintento manda otra vez. Esa ventana es mínima y es aceptable: la entrega de la cola es "al menos una vez" igual.
- `NotifyCallup` es idempotente porque solo reparte: si se ejecuta dos veces, los `SendCallupNotice` repetidos descartan lo que ya se mandó.

### 4. Texto del mail

- `CallupMessages` suma `mailSubject(GameMatch, Tenant, mode)` y hace pública la parte de `summary()` que arma los datos del partido, para que el mail use las mismas líneas que WhatsApp.
- `App\Mail\CallupNoticeMail extends BaseMailable`, con la vista Markdown `resources/views/mail/callup-notice.blade.php`: saludo, aviso de "Hubo cambios" si corresponde, datos del partido, y un botón "Confirmar" por jugador con su `personalUrl()`.
- El Mailable recibe valores ya resueltos (strings y una lista de `{nombre, url}`), no modelos de club, porque se arma dentro de `TenantDatabase::run` y se manda en el momento con `sendNow`.

### 5. Despacho desde el controller

- `publish`: después de `CallupService::publish`, `NotifyCallup::dispatch($tenant->id, $match->id, $mode, now())->afterCommit()`. El club sale del contexto autenticado (`$request->attributes->get('tenant')`), nunca del body.
- `remind`: valida con `authorizeClub('callups.publish')` y `ensureCategoryInScope`. Si la convocatoria no está publicada, responde 422 (`ValidationException` en `callup`). Si está publicada, despacha con `mode = reminder` y devuelve `CallupSheetResource` igual que `publish`.
- No hace falta un permiso nuevo: recordar es parte de publicar.

### 6. `TenantDatabase::run` restaura la base anterior

Con la cola `sync` (tests), `NotifyCallup` corre adentro del request. Hoy, al salir de `run`, la conexión `tenant` queda **sin base**, y el request falla después al armar la convocatoria con "No database selected".

**Cambio:** `run` guarda la base a la que apuntaba la conexión antes de entrar y vuelve a apuntarla ahí al salir. Si antes no había base (el caso del worker), queda sin base como hoy, así que la garantía de la guía se mantiene.

**Alternativa descartada:** despachar recién después de armar la respuesta. Funciona, pero deja una trampa para el próximo job de club que se despache desde un request.

### 7. Rate limit del proveedor

Resend limita los pedidos por segundo. `SendCallupNotice` usa el middleware `RateLimited('mail')`. El limiter `mail` se define en `AppServiceProvider` con `Limit::perSecond(2)`, y el job lo libera para más tarde en lugar de fallar. Parámetros del job: `retryUntil()` de 1 hora y `$maxExceptions = 3` (no `$tries`: cada vez que el límite lo devuelve a la cola cuenta como intento), `$backoff = [10, 60]` y `$timeout = 30`.

### 8. Contrato de la convocatoria

`CallupRow` suma `has_email` (bool: el contacto principal tiene email) y `notified_at` (ISO 8601 o null). Se regenera `docs/api/openapi.json`.

## Risks / Trade-offs

- [El profe toca "Recordar" varias veces seguidas] → cada pedido tiene su `requestedAt`, así que manda de nuevo. Mitigación: el front deshabilita el botón mientras procesa. Si hace falta, se agrega un mínimo entre recordatorios en otro change.
- [Un email mal cargado hace fallar `SendCallupNotice`] → falla solo ese job, después de 3 intentos. `failed()` loguea `tenant_id`, `match_id` y los `callupIds`, sin la dirección. Los demás mails salen igual.
- [El cambio en `TenantDatabase::run` toca código central de tenancy] → es acotado y tiene tests: anidado dentro de un request con club (vuelve a ese club) y en el worker (queda sin base, como hoy).
- [Sin worker no sale nada] → en local, el servicio `queue` de Compose. En producción depende del worker de la cola de Laravel Cloud (TIZ-26, runbook de deploy).

## Migration Plan

1. Migración de tenant (`notified_at` nullable): se aplica con `tenants:migrate`. Los convocados existentes quedan en null.
2. Las convocatorias que ya están publicadas no mandan nada solas. Si el profe republica, como `notified_at` es null para todos, avisa a todos con el modo `published`.
3. Rollback: revertir el código. La columna puede quedar: es nullable y nadie más la lee.
