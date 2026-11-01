# Tasks

## 1. Configuración de correo local y Resend

- [x] 1.1 Agregar `resend/resend-php` como única dependencia nueva y configurar SMTP local con Mailpit, Resend para producción y remitente Sportiva en `.env.example`; verificar `composer validate` y que `docker compose config` resuelva el servicio y sus puertos.
- [x] 1.2 Incorporar Mailpit `axllent/mailpit:v1.33` fijado por digest a Compose, conectado a la red default y con los puertos 1025 y 8025 publicados; verificar que `docker compose up -d mailpit` levante el servicio y que `http://localhost:8025` responda.
- [x] 1.3 Documentar Mailpit, variables locales y `127.0.0.1` para Laravel fuera de Docker en `docs/desarrollo-local.md` y `docs/actualizar-entorno.md`; verificar que la guía coincida con Compose y `.env.example`.

## 2. Mailables y plantilla Sportiva

- [x] 2.1 Crear una clase base Mailable que implemente `ShouldQueue` y un Mailable de prueba con asunto, contenido en español rioplatense, acción a `config('app.frontend_url')` y pie; agregar un test `Mail::fake()` que confirme que el Mailable se encola.
- [x] 2.2 Publicar y personalizar las vistas Markdown de Laravel con el wordmark Sportiva, la paleta existente, estilos compatibles con clientes de email y contraste suficiente; verificar el render del Mailable de prueba con la plantilla de marca.

## 3. Comando Artisan de prueba

- [x] 3.1 Implementar `mail:test {email}` con opción `--sync`, validación de email y salida de mailer, host y remitente sin credenciales; agregar tests Feature para envío encolado, envío síncrono y destinatario inválido, y verificar que pasen.
- [x] 3.2 Documentar en `docs/desarrollo-local.md` y `docs/actualizar-entorno.md` cómo ejecutar `mail:test` con y sin worker; verificar que los comandos documentados coincidan con el comando implementado.

## 4. Operación de producción

- [x] 4.1 Crear `docs/mail-produccion.md` con Resend, todas las variables requeridas, verificación SPF/DKIM/DMARC, configuración de cola en Laravel Cloud y pasos para probar la entrega; verificar que la guía no incluya claves reales y que los comandos puedan seguirse sin Docker.
- [x] 4.2 Corregir en `docs/guia-jobs.md` la afirmación obsoleta de que no existe worker local, manteniendo coherencia con el servicio `queue` de Compose y el worker de Laravel Cloud.

## 5. Verificación integrada

- [x] 5.1 Ejecutar `docker compose exec backend php artisan test` y `docker compose exec backend vendor/bin/pint --test`; confirmar que todos los tests y el lint pasan y que no se requiere regenerar OpenAPI porque no cambian rutas, Form Requests ni Resources.
