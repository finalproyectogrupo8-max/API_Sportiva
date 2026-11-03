# club-alerts-notifications Specification

## Purpose

Generar alertas operativas por club y permitir que cada integrante consulte y gestione sus propias notificaciones.

## Requirements

### Requirement: Evaluar reglas de alertas por club

El comando `alerts:run` SHALL evaluar por separado cada club activo y crear alertas y notificaciones para los destinatarios correspondientes. Las reglas actuales cubren ausencias y caídas de asistencia, requisitos próximos a vencer, cierres de inscripción con jugadores no habilitados y convocatorias pendientes próximas al partido.

#### Scenario: Ejecución repetida

- **WHEN** la condición de una alerta persiste y se ejecuta el comando más de una vez
- **THEN** no duplica el registro ni la notificación

#### Scenario: Condición resuelta

- **WHEN** deja de cumplirse la condición que generó una alerta
- **THEN** el registro de alerta queda resuelto

#### Scenario: Simulación

- **WHEN** el operador ejecuta el comando en modo de simulación
- **THEN** no se persisten alertas ni notificaciones

#### Scenario: Fallo aislado

- **WHEN** ocurre un error procesando un club
- **THEN** se informa el fallo y el procesamiento continúa con los otros clubes

### Requirement: Consultar notificaciones propias

`GET /api/notifications` SHALL devolver solo las notificaciones del integrante asociado al token del club, paginadas (20 por defecto, 100 como máximo), con filtro `unread=1`.

#### Scenario: Filtrar no leídas

- **WHEN** el usuario consulta `?unread=1`
- **THEN** recibe únicamente sus notificaciones sin `read_at`

#### Scenario: Aislamiento entre integrantes

- **WHEN** un usuario consulta o modifica la notificación de otro integrante
- **THEN** la notificación ajena no se muestra y el detalle responde 404

### Requirement: Marcar notificaciones como leídas

El usuario SHALL poder marcar una notificación propia mediante `PATCH /api/notifications/{id}/read` o todas sus pendientes mediante `POST /api/notifications/read-all`.

#### Scenario: Marcar una notificación

- **WHEN** el usuario marca una notificación propia como leída
- **THEN** se guarda `read_at` y se devuelve la notificación actualizada

#### Scenario: Marcar todas

- **WHEN** el usuario marca todas como leídas
- **THEN** se actualizan solo sus pendientes y se devuelve la cantidad afectada
