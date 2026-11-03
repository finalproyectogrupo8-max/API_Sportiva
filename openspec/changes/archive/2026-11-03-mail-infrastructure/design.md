# Design

## Context

Ver `proposal.md` para la motivación y los límites de alcance. Laravel 13 ya incluye los mailers SMTP y Resend, y la configuración de Resend (`config/mail.php` y `config/services.php`) ya existe. Falta instalar su SDK, cambiar el default local de `log` a SMTP y agregar Mailpit al Compose existente.

Compose ya tiene un worker `queue` que procesa la cola `database`. En Laravel Cloud la aplicación usa las colas administradas documentadas en `docs/guia-jobs.md`. La URL del frontend ya está centralizada en `FRONTEND_URL` / `config('app.frontend_url')`; se conserva para construir el enlace del correo de prueba.

El frontend local muestra el wordmark Sportiva como texto y dos trazos CSS, con la paleta `#10100F`, `#D36F45` y `#DBC4AA`. No hay un archivo de logo disponible en este repositorio ni se necesita depender de un recurso remoto para renderizar el encabezado del email.

## Goals / Non-Goals

**Goals:**
- Usar el transporte SMTP de Mailpit localmente y Resend en producción, sin duplicar configuraciones existentes.
- Hacer que los mailables de aplicación sean encolables por defecto y permitir que el comando de prueba los envíe sin cola de forma explícita.
- Reutilizar la configuración existente del frontend y el worker/cola disponibles.
- Mantener la plantilla de email independiente de assets remotos y legible con estilos compatibles con clientes de correo.

**Non-Goals:**
- Incorporar la recuperación de contraseña de TIZ-22, que no está integrada en `main`.
- Añadir proveedores alternativos, una segunda cola o cambios a la arquitectura de jobs.
- Agregar un logo remoto o una dependencia para renderizar imágenes.

## Decisions

### Usar Resend con el SDK oficial requerido por Laravel

Se mantiene el mailer `resend` que Laravel ya configura y se agrega únicamente `resend/resend-php`, SDK que requiere el transporte nativo. Resend se elige frente a Postmark o SMTP de Brevo porque Laravel soporta el transporte directamente y Laravel Cloud puede aprovisionar sus variables al conectar el recurso, evitando configurar credenciales SMTP adicionales. La entrega requiere verificar el dominio remitente y publicar SPF, DKIM y DMARC.

### Capturar el correo local con Mailpit en Compose

Se agrega `axllent/mailpit:v1.33@sha256:9b3a463be15354c0f0eba91697d80e3e0d8a5623ef0e627cacc836bc17fd5231`, fijado por versión y digest según la convención del repositorio. La API se conecta a `mailpit:1025` desde la red default de Compose; la interfaz se publica en `localhost:8025`. Si Laravel corre directamente en el host, se usa `127.0.0.1:1025`.

El default local pasa a `smtp`, con remitente de ejemplo Sportiva. Producción selecciona `resend` mediante `MAIL_MAILER`; la clave existente `RESEND_API_KEY` permanece solo en el entorno de Laravel Cloud.

### Extender una base Mailable que implemente ShouldQueue

Los correos de aplicación extienden una clase base propia que implementa `ShouldQueue`, para que el envío normal se procese mediante la cola ya configurada y no se repita la declaración en cada Mailable. El envío del comando `mail:test` usa esa ruta por defecto. Con `--sync`, usa `sendNow` para evitar la cola sin modificar la configuración global ni la conexión `QUEUE_CONNECTION`.

No se agrega una cola específica de emails. En local se conserva la cola `database` y el worker de Compose; en producción se siguen los límites del plan y las managed queues documentadas para Laravel Cloud.

### Personalizar las vistas Markdown publicadas de Laravel

Se publica el conjunto estándar de vistas con `vendor:publish --tag=laravel-mail` y se personaliza el header, el botón, los links y el footer. El header recrea el wordmark existente con texto y los dos trazos de marca, usando estilos inline compatibles con clientes de email; no depende de una URL de imagen ni de un asset que no está en este repo. Se usan el fondo oscuro `#10100F`, beige `#DBC4AA` y naranja `#D36F45`; el texto oscuro sobre el botón naranja mantiene contraste alto.

La plantilla y el Mailable de prueba usan español rioplatense. El enlace se deriva de `config('app.frontend_url')`, sin introducir `FRONT_URL` ni duplicar `FRONTEND_URL`.

### Exponer el correo mediante un comando Artisan de prueba

`mail:test {email}` valida el destinatario con las reglas estándar de Laravel antes de construir o enviar el mensaje. El Mailable de prueba contiene asunto, párrafo, acción dirigida a `config('app.frontend_url')` y pie. La salida informa mailer, host y remitente obtenidos de configuración, pero nunca lee ni muestra credenciales. El comando usa la cola por defecto y `--sync` para el envío inmediato.

### Documentar operación y conservar la separación de tareas

Las guías locales explican Mailpit, las variables SMTP, la URL de inspección y el comando. El runbook nuevo de producción documenta las variables de Resend, DNS, Laravel Cloud y la prueba de entrega. También se corrige la afirmación obsoleta en la guía de jobs que dice que el worker local no existe. No se modifica la guía de recuperación ni su notificación hasta integrar TIZ-22.

## Risks / Trade-offs

- [El digest de Mailpit identifica una versión concreta y debe actualizarse deliberadamente para recibir futuras correcciones] → Mantener tag y digest sincronizados y actualizar ambos en una tarea explícita.
- [Un dominio o remitente sin verificación DNS impide o degrada la entrega] → El runbook exige completar SPF y DKIM y recomienda DMARC antes de la prueba de producción.
- [Los clientes de correo soportan CSS de forma desigual] → Usar HTML simple y estilos inline; comprobar el render local en Mailpit y usar colores de alto contraste.
- [Un worker detenido deja los correos en la cola] → Documentar cómo comprobar y reiniciar el worker local, y cómo verificar la cola administrada en Laravel Cloud.

## Migration Plan

1. Agregar el SDK de Resend y configurar el transporte existente; preparar Mailpit, el remitente local y las vistas de marca.
2. Desplegar la aplicación y configurar en Laravel Cloud `MAIL_MAILER=resend`, `RESEND_API_KEY`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME`, conectando la cola administrada existente.
3. Verificar SPF, DKIM y DMARC en el proveedor antes de enviar a destinatarios reales; ejecutar `php artisan mail:test` y revisar el resultado y los eventos de entrega.
4. Rollback: volver a la revisión anterior y restaurar el mailer anterior en el entorno. Las tareas ya encoladas deben inspeccionarse antes de descartar o reprocesar la cola; no hay migraciones de base de datos en este cambio.
