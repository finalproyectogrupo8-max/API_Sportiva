# Spec Delta

## Purpose

Permite que usuarios de Sportiva recuperen el acceso a su cuenta mediante un enlace temporal enviado por email, y que un administrador de plataforma inicie ese envío cuando sea necesario.

## ADDED Requirements

### Requirement: Solicitud pública de restablecimiento
El sistema SHALL aceptar solicitudes de restablecimiento por email sin revelar si existe una cuenta asociada. SHALL enviar el enlace solo a cuentas activas.

#### Scenario: Email asociado a una cuenta activa
- **WHEN** se envía un email válido a `POST /api/auth/forgot-password`
- **THEN** responde HTTP 202 con un mensaje genérico y envía un email de restablecimiento

#### Scenario: Email inexistente
- **WHEN** se solicita el restablecimiento para un email sin cuenta
- **THEN** responde HTTP 202 con el mismo mensaje y no envía ningún email

#### Scenario: Cuenta inactiva
- **WHEN** se solicita el restablecimiento para una cuenta inactiva
- **THEN** responde HTTP 202 con el mismo mensaje y no envía ningún email

#### Scenario: Email con formato inválido
- **WHEN** se envía un email con formato inválido
- **THEN** responde HTTP 422 con un error de validación para `email`

### Requirement: Limitación de solicitudes de restablecimiento
El sistema SHALL limitar las solicitudes a tres por minuto por combinación de IP y email.

#### Scenario: Se supera el límite
- **WHEN** una IP solicita cuatro veces en un minuto el restablecimiento para el mismo email
- **THEN** la cuarta solicitud responde HTTP 429

### Requirement: Enlace temporal de restablecimiento
El sistema SHALL enviar el enlace por email en español, con la plantilla de marca de Sportiva, vencimiento de 60 minutos y destino en el frontend configurado.

#### Scenario: Destino del enlace
- **WHEN** se genera un email de restablecimiento
- **THEN** el enlace apunta a `{FRONT_URL}/restablecer-contrasena` e incluye `token` y `email` como parámetros

#### Scenario: Enlace vencido
- **WHEN** se intenta utilizar un enlace luego de 60 minutos
- **THEN** el restablecimiento es rechazado con HTTP 422

### Requirement: Restablecimiento de contraseña
El sistema SHALL aceptar un token vigente junto con email y contraseña confirmada que cumpla `Illuminate\Validation\Rules\Password::defaults()`, y SHALL invalidar las sesiones API del usuario al completar el cambio.

#### Scenario: Restablecimiento exitoso
- **WHEN** se envían email, token vigente, contraseña válida y confirmación coincidente a `POST /api/auth/reset-password`
- **THEN** responde HTTP 204, la nueva contraseña permite autenticar y todos los tokens API emitidos previamente dejan de ser válidos

#### Scenario: Token inválido, vencido o ya utilizado
- **WHEN** se intenta restablecer la contraseña con un token inválido, vencido o ya utilizado
- **THEN** responde HTTP 422

#### Scenario: Confirmación o política de contraseña inválida
- **WHEN** la contraseña no cumple `Password::defaults()` o no coincide con su confirmación
- **THEN** responde HTTP 422 con errores de validación para `password`

### Requirement: Restablecimiento iniciado por administrador
El sistema SHALL permitir que un administrador de plataforma autenticado solicite el envío del enlace a un usuario mediante `POST /api/admin/users/{user}/send-password-reset`.

#### Scenario: Envío a una cuenta activa
- **WHEN** un administrador solicita el restablecimiento para un usuario activo
- **THEN** responde HTTP 202 y envía el email de restablecimiento

#### Scenario: Usuario inactivo
- **WHEN** un administrador solicita el restablecimiento para un usuario inactivo
- **THEN** responde HTTP 202 y no envía ningún email

#### Scenario: Usuario sin permisos de plataforma
- **WHEN** un usuario que no es administrador de plataforma solicita el envío
- **THEN** responde HTTP 403

### Requirement: Auditoría de eventos de restablecimiento
El sistema SHALL registrar las solicitudes válidas, los cambios de contraseña exitosos y los envíos iniciados por administradores en `audit_logs`, sin incluir emails, tokens, contraseñas ni hashes.

#### Scenario: Solicitud y cambio de contraseña
- **WHEN** un usuario solicita un enlace y luego completa correctamente el restablecimiento
- **THEN** quedan registrados ambos eventos asociados a la cuenta sin datos secretos

#### Scenario: Solicitud para un email sin cuenta
- **WHEN** se solicita el restablecimiento para un email sin cuenta
- **THEN** queda registrado el evento sin guardar el email ni asociarlo a una cuenta

#### Scenario: Envío iniciado por administrador
- **WHEN** un administrador solicita el envío del enlace a una cuenta activa
- **THEN** queda registrado el evento asociado al usuario objetivo y al actor administrador sin datos secretos
