# Proposal

## Why

La API no tiene un entorno local para inspeccionar emails ni una configuración de envío de producción lista, por lo que las funcionalidades que necesitan correo no pueden probarse de punta a punta. Se necesita una infraestructura reutilizable, con identidad visual Sportiva y envío asíncrono, para habilitar las próximas funcionalidades de email.

## What Changes

- Agregar Mailpit al entorno Docker local para capturar correos sin enviarlos a destinatarios reales.
- Configurar Resend como proveedor de producción y documentar sus variables, verificación de dominio y prueba de envío.
- Incorporar una base de correo compartida y encolada, una plantilla Markdown de marca y el comando `mail:test` con opción `--sync`.
- Documentar el uso local y la operación de producción, conservando las variables de front existentes.
- **Fuera de alcance:** adaptar la notificación de restablecimiento de contraseña. El código TIZ-22 no está integrado en `main`; se incorporará cuando esa implementación llegue a la rama principal.

## Capabilities

### New Capabilities

- `mail-delivery`: Envío asíncrono de emails con configuración de proveedor, plantilla de marca Sportiva y herramienta de prueba.

### Modified Capabilities

- `local-dev-environment`: El entorno local incluye Mailpit para capturar e inspeccionar mensajes enviados por la API.

## Impact

- Docker Compose, `.env.example`, configuración Laravel de mail y servicios, y una única dependencia para el SDK de Resend.
- Base Mailable/plantilla Markdown de marca, comando Artisan `mail:test` y tests Feature.
- Guías de desarrollo local y actualización del entorno, más un runbook nuevo de producción `docs/mail-produccion.md`.
- La paleta visual de referencia observada en el front local: fondo `#10100F`, naranja `#D36F45` y beige `#DBC4AA`; no se encontró un archivo de logo exportado en este repo.
- La notificación de recuperación de contraseña permanece fuera de este cambio hasta que TIZ-22 esté integrada en `main`.
