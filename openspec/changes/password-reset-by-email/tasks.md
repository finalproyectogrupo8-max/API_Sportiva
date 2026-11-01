# Tasks

## 1. Compatibilidad con la rama actual

- [ ] 1.1 Antes de tocar código, actualizar la rama de trabajo respecto de `origin/main` sin descartar los cambios locales; verificar el historial y los archivos compartidos (`User`, `AppServiceProvider`, `AuditLogger`, `config/app.php` y rutas) para conservar las modificaciones recientes.
- [ ] 1.2 Agregar `app.front_url` respaldado por `FRONT_URL` con fallback a `FRONTEND_URL`, conservar `app.frontend_url` y documentar la compatibilidad en `.env.example`; confirmar ambos valores y que `auth.passwords.users.expire` permanece en 60 minutos mediante tests de configuración.
- [ ] 1.3 Implementar un generador de URL compartido para `ResetPasswordNotification` y `ResetPassword::createUrlUsing`, registrar la notificación propia en `User` y redactarla en español con la plantilla de marca de la tarea 38; verificar clase, URL, parámetros y contenido con tests `Notification::fake()`.

## 2. Solicitud pública de restablecimiento

- [ ] 2.1 Implementar Form Request, endpoint, respuesta genérica HTTP 202 y limiter de tres solicitudes por minuto por IP y email normalizado; agregar tests de formato inválido, cuenta existente/inexistente/inactiva, mismo mensaje y respuesta 429 en la cuarta llamada.
- [ ] 2.2 Extender `AuditLogger` sin eliminar su soporte reciente y registrar solicitudes válidas sin email ni secretos; verificar que cuentas existentes usan sujeto `user` y emails desconocidos usan `auth/0` con solo la IP.

## 3. Cambio de contraseña

- [ ] 3.1 Implementar validación mediante `Illuminate\Validation\Rules\Password::defaults()` y confirmación, consumir el token con `Password::reset`, actualizar contraseña y `remember_token`, revocar todos los tokens Sanctum y responder HTTP 204/422; agregar tests de éxito, reglas de password, token inválido/vencido/reutilizado, evento `PasswordReset` y revocación total.
- [ ] 3.2 Registrar el cambio exitoso en `audit_logs` como evento específico, sin valores secretos; verificar el sujeto/actor y la ausencia de contraseña, hash y token en tests Feature.

## 4. Envío administrativo y contrato API

- [ ] 4.1 Agregar `POST /api/admin/users/{user}/send-password-reset` al grupo admin existente con route model binding; garantizar el envío a usuarios activos incluso si existe un token reciente, omitir usuarios inactivos y registrar actor; agregar tests de autorización 403, envío, bypass del throttle público, inactivo y auditoría.
- [ ] 4.2 Regenerar `docs/api/openapi.json` con `php artisan scramble:export` y verificar que documenta los tres endpoints de recuperación y sus respuestas.

## 5. Verificación integral

- [ ] 5.1 Ejecutar `php artisan test` y `vendor/bin/pint --test` en Docker sobre la rama ya actualizada, resolver regresiones causadas por el cambio y confirmar que ambos comandos terminan correctamente.
