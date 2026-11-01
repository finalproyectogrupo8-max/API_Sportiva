# Proposal

## Why

Los usuarios que olvidan su contraseña no tienen un flujo de recuperación y dependen de asistencia manual para volver a acceder. Se necesita un flujo seguro por email, compatible con la arquitectura de autenticación central de Sportiva y listo para que lo consuma el frontend.

## What Changes

- Agregar endpoints públicos para solicitar y completar el restablecimiento de contraseña, con respuestas que no revelen si un email tiene cuenta.
- Enviar enlaces de restablecimiento de un solo uso, con vencimiento de 60 minutos y destino en el frontend configurado.
- Permitir que un administrador de plataforma solicite el envío del enlace a un usuario activo.
- Revocar los tokens Sanctum del usuario tras cambiar la contraseña y registrar los eventos de recuperación en `audit_logs`, sin guardar credenciales ni tokens.
- Documentar los endpoints en el contrato OpenAPI generado por Scramble.

## Capabilities

### New Capabilities

- `password-reset`: Solicitud pública, restablecimiento de contraseña y envío iniciado por un administrador de plataforma.

### Modified Capabilities

Ninguna.

## Impact

- API Laravel: rutas públicas de auth y ruta administrativa protegida existente; Form Requests, controllers, notificación propia y `User`.
- Configuración de expiración del broker, URL del frontend compatible con `FRONTEND_URL` y `FRONT_URL`, y rate limiter; tabla central existente `password_reset_tokens`, sin cambios de esquema.
- Auditoría central, tests Feature de auth y administración, y `docs/api/openapi.json`.
- El envío usa la infraestructura de correo disponible y el diseño de correo de marca definido por la tarea 38; la URL existente `FRONTEND_URL` se conserva para no romper otros enlaces del frontend y `FRONT_URL` se agrega como alias de configuración.
