# Correo en producción

La aplicación envía los Mailable de forma asíncrona. Este runbook cubre el transporte de salida con Resend, el dominio remitente y la verificación de una entrega en Laravel Cloud. No configures claves en el repositorio ni en archivos compartidos.

## Proveedor: Resend

Se elige Resend porque Laravel ya incluye el transporte nativo y Laravel Cloud puede integrar el proveedor sin configurar credenciales SMTP. Solo hace falta su SDK oficial para el transporte de Laravel. Frente a Postmark o SMTP de Brevo, esta opción evita mantener otro transporte y una configuración SMTP separada.

## Variables de entorno

Configuralas en las variables protegidas del entorno de Laravel Cloud:

| Variable | Valor |
| --- | --- |
| `MAIL_MAILER` | `resend` |
| `RESEND_API_KEY` | API key de Resend, guardada como secreto en Laravel Cloud |
| `MAIL_FROM_ADDRESS` | Dirección dentro del dominio verificado, por ejemplo `notificaciones@tudominio.com` |
| `MAIL_FROM_NAME` | `Sportiva` |
| `FRONTEND_URL` | URL HTTPS del frontend; se usa en los enlaces de los correos |
| `QUEUE_CONNECTION` | La conexión que ya usa el worker del entorno; no la cambies solo para el correo |

Al conectar Resend como recurso en Laravel Cloud, revisá que el entorno tenga los valores de `MAIL_MAILER`, `RESEND_API_KEY`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME`. No hace falta configurar `MAIL_HOST`, `MAIL_PORT` ni credenciales SMTP para el transporte Resend. La URL del frontend ya se configura con `FRONTEND_URL`; no agregues una variable paralela `FRONT_URL`.

Los Mailable implementan `ShouldQueue` y usan la conexión predeterminada. Confirmá en Laravel Cloud que haya un worker o una managed queue activa para esa misma conexión; de lo contrario, los mensajes van a quedar pendientes. Este cambio no crea ni modifica infraestructura de colas ni agrega el SDK de AWS. Revisá la [guía de jobs](./guia-jobs.md#producción-laravel-cloud) antes de cambiar la conexión o el modo de procesamiento.

## Verificar el dominio remitente

1. Agregá el dominio de envío en Resend y copiá los registros DNS que muestra el panel para ese dominio.
2. Publicá los registros SPF y DKIM exactamente como los indica Resend. Si ya existe un registro SPF, actualizá el existente: no publiques varios registros SPF para el mismo dominio.
3. Publicá un registro TXT DMARC en `_dmarc.tudominio.com`. Empezá con una política de monitoreo y un buzón de reportes del equipo; endurecé la política después de revisar los reportes.
4. Esperá la propagación DNS y confirmá en Resend que el dominio figure como verificado antes de usarlo en `MAIL_FROM_ADDRESS`.

Los nombres, destinos y valores DNS dependen del dominio y los genera el proveedor. No uses registros copiados de otro entorno.

## Probar un envío

Después de configurar las variables y desplegar:

1. Desde la consola del entorno de Laravel Cloud, ejecutá el comando encolado:

   ```bash
   php artisan mail:test tu-direccion@tudominio.com
   ```

   Confirmá que la cola procese el mensaje y revisá el evento de entrega en el panel de Resend.

2. Para comprobar el transporte sin depender del worker, ejecutá el envío síncrono:

   ```bash
   php artisan mail:test tu-direccion@tudominio.com --sync
   ```

3. Confirmá la recepción, el remitente visible, el enlace al frontend y que Resend registre el resultado. Si falla, revisá la verificación del dominio, la API key del entorno y los logs de la aplicación; no copies credenciales a los logs ni al ticket.

Usá una dirección de prueba autorizada y verificá la entrega antes de habilitar envíos a usuarios.
