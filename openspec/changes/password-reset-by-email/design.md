# Design

## Context

La autenticación y `password_reset_tokens` viven en la base central. Las rutas públicas de auth y el grupo `/api/admin` están fuera de `tenant.token`; el grupo admin ya aplica `auth:sanctum`, `abilities:platform:admin` y `platform.admin`.

La versión actual de `origin/main` conserva `FRONTEND_URL` y `app.frontend_url` para enlaces de convocatorias. El `UserController` y las rutas de `/api/admin/users` usan el parámetro `{user}`. `AuditLogger` registra actualmente sujetos `tenant`, `user` y `membership`, y el esquema central de `audit_logs` requiere `subject_type` y `subject_id`, sin FK para el sujeto.

Esta rama parte de una revisión anterior a los cambios actuales de `origin/main`; antes de aplicar el cambio deberá actualizarse sobre la versión vigente, conservando tanto esos cambios de código como los artefactos de esta propuesta. La rama remota con una implementación parcial de recuperación no está integrada y no se toma como fuente de implementación.

Ver [proposal.md](./proposal.md) para la motivación y [specs/password-reset/spec.md](./specs/password-reset/spec.md) para el contrato observable.

## Goals / Non-Goals

**Goals:**
- Usar el broker `users` y el almacenamiento central existentes para emitir y consumir tokens.
- Mantener una respuesta pública indistinguible para emails existentes, inexistentes e inactivos, y revocar todos los tokens Sanctum al cambiar la contraseña.
- Conservar compatibilidad con los enlaces existentes del frontend y reutilizar el middleware y el logger de auditoría actuales sin reemplazar sus cambios recientes.

**Non-Goals:**
- Agregar tablas, dependencias Composer o un mecanismo propio de tokens.
- Cambiar el login, la selección de tenant o el flujo de logout.
- Configurar un proveedor SMTP de producción dentro de este cambio.

## Decisions

### Broker central de Laravel

El endpoint público de solicitud usa `Password::sendResetLink` con `is_active = true`; el endpoint de cambio usa `Password::reset` y las reglas de contraseña `Illuminate\Validation\Rules\Password::defaults()` junto con confirmación. Se conserva `auth.passwords.users.expire = 60`. No se crean tablas ni se consulta una base de club.

**Alternativa considerada:** implementar tokens o almacenamiento propios. Se descarta porque duplicaría el mecanismo de Laravel y podría divergir de la tabla central existente.

### Respuesta privada y control de frecuencia

El Form Request valida el formato del email. El limiter `password-reset` limita a tres solicitudes por minuto por combinación de IP y email normalizado. La respuesta pública es siempre el mismo HTTP 202 y mensaje genérico, independientemente del resultado del broker; las solicitudes inválidas conservan el 422 estándar.

**Alternativa considerada:** responder con el estado del broker. Se descarta porque permitiría enumerar cuentas.

### Compatibilidad de URL y notificación propia

Se conserva `app.frontend_url` respaldado por `FRONTEND_URL`, porque ya lo usan otros enlaces. Se agrega `app.front_url` respaldado por `FRONT_URL`, con fallback a `FRONTEND_URL` y al valor local existente. La URL propia de restablecimiento apunta a `/restablecer-contrasena` con `token` y `email`.

Un helper compartido genera la URL para `ResetPasswordNotification` y para el callback `ResetPassword::createUrlUsing`; así la notificación propia y la notificación estándar de Laravel no divergen. `User::sendPasswordResetNotification` usa la clase propia en español y la plantilla de marca definida por la tarea 38.

**Alternativa considerada:** reemplazar `FRONTEND_URL` o confiar en que `ResetPassword::createUrlUsing` personalice por sí solo la notificación propia. Se descarta porque podría romper enlaces existentes y el callback estándar no controla el contenido de una notificación personalizada.

### Envío iniciado por administrador

La ruta se agrega en el grupo admin existente con el parámetro `{user}` y binding al modelo `User`. Para usuarios activos, el controlador genera un token mediante el broker existente y llama a `sendPasswordResetNotification`; esta operación permite que el admin solicite un nuevo envío aun si el broker público aplicó recientemente su throttle por destinatario. Para usuarios inactivos, responde 202 sin generar token ni enviar email.

**Alternativa considerada:** usar `Password::sendResetLink` también para cada envío administrativo. Se descarta porque el throttle por destinatario puede impedir el envío que el admin solicita; `createToken` sigue usando el broker y su almacenamiento, sin implementar tokens propios.

### Restablecimiento, evento y revocación

El callback de `Password::reset` actualiza la contraseña, rota `remember_token`, revoca `$user->tokens()->delete()` y registra el evento de restablecimiento sin valores secretos. El broker conserva la validación y consumo de token y dispara `PasswordReset`. Los estados de token inválido, vencido o usado se convierten en 422 JSON; el éxito es 204.

**Alternativa considerada:** revocar solo el token usado o cambiar la contraseña fuera del broker. Se descarta porque dejaría sesiones vigentes o evitaría la validación centralizada del token.

### Auditoría aditiva y privacidad

Se extiende `AuditLogger` sin reemplazar su manejo reciente de otros sujetos. Las solicitudes asociadas a cuentas se registran como eventos de `user`; una solicitud válida para email desconocido se registra como `auth.requested`, `subject_type = auth`, `subject_id = 0`, con solo la IP. Los cambios exitosos tienen un evento específico de usuario y el envío administrativo registra el usuario objetivo y el admin como actor. Ningún evento contiene email, token, contraseña ni hash.

**Alternativa considerada:** guardar el email solicitado o descartar solicitudes desconocidas. Se descarta por privacidad y para conservar la auditoría sin modificar el esquema.

## Risks / Trade-offs

- [La infraestructura Mailpit/plantilla de la tarea 38 puede no estar integrada al aplicar este cambio] → Mantener la notificación independiente del proveedor, usar `MAIL_MAILER=log` localmente y `Notification::fake()` en tests; integrar la plantilla compartida cuando esté disponible.
- [Un cambio de contraseña invalida sesiones operativas de todos los clubes] → Es intencional: el usuario deberá iniciar sesión nuevamente.
- [El sujeto `auth` usa ID `0` porque el esquema requiere `subject_id`] → No se almacena el email; el `subject_type` distingue la solicitud sin cuenta de eventos asociados a usuarios.
- [La rama de trabajo puede estar atrasada respecto de `origin/main`] → Actualizarla antes de la implementación y resolver los cambios conservando las modificaciones recientes; no copiar la implementación parcial de otra rama.

## Migration Plan

No hay migraciones de base de datos. Antes de implementar, actualizar la rama respecto de `origin/main` sin descartar los cambios locales. Desplegar la aplicación con `FRONTEND_URL` configurada; `FRONT_URL` es un alias opcional y no reemplaza la variable existente. Mantener el proveedor de correo en la configuración del entorno.

## Open Questions

Ninguna que cambie el contrato o la estrategia definida.
