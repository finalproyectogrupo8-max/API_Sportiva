# Mails en desarrollo y en la demo

Este documento explica cómo salen los mails de Sportiva en cada entorno, y cómo hacer que en la demo lleguen a casillas reales usando una cuenta de Gmail.

Los mails que manda el sistema son:

- recuperar la contraseña;
- aviso de convocatoria a los contactos;
- comunicados del club.

## Los tres entornos

| Entorno | Por dónde sale el mail | Quién lo ve |
|---|---|---|
| Desarrollo (por defecto) | **Mailpit**, dentro de Docker | Solo vos, en http://localhost:8025. No le llega a nadie. |
| Demo con mails reales | **Gmail** por SMTP, desde tu `.env` local | Las casillas reales cargadas en jugadores, tutores y usuarios |
| Producción | **Resend**, con el dominio del club verificado | Las casillas reales. Ver [mail-produccion.md](mail-produccion.md) |

Las familias no instalan nada en ningún caso: reciben un mail normal en la casilla que ya usan (Gmail, Outlook, etc.). Mailpit es una herramienta para desarrollar: atrapa todos los mails para poder probar sin escribirle a gente real.

El código es el mismo en los tres entornos. Solo cambian las variables `MAIL_*` del `.env`.

## Desarrollo: Mailpit

Es lo que viene configurado en `.env.example` y levanta `docker compose up`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@sportiva.test"
```

Los mails se ven en http://localhost:8025, sin importar a qué dirección se mandaron.

## Demo: mails reales con Gmail

Sirve para mostrar en la presentación que el comunicado o la convocatoria le llegan al celular de alguien.

### 1. Preparar la cuenta de Gmail

Conviene una cuenta creada para la demo, por ejemplo `sportiva.demo@gmail.com`, y no una personal.

1. Activá la **verificación en dos pasos**: Cuenta de Google → Seguridad.
2. Creá una **contraseña de aplicación** en https://myaccount.google.com/apppasswords. Ponele un nombre, por ejemplo "Sportiva", y copiá la clave de 16 letras. La contraseña normal de la cuenta **no sirve** para SMTP.

### 2. Cambiar el `.env` de la API

En `api-sportiva/.env`, reemplazá el bloque de Mailpit por:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=sportiva.demo@gmail.com
MAIL_PASSWORD=la-clave-de-16-letras-sin-espacios
MAIL_FROM_ADDRESS=sportiva.demo@gmail.com
MAIL_FROM_NAME="Sportiva"
```

- `MAIL_FROM_ADDRESS` tiene que ser la misma cuenta. Gmail reescribe cualquier otro remitente.
- El `.env` no se sube al repo: **nunca** pongas la clave en `.env.example`, en un PR ni en un ticket.

### 3. Aplicar el cambio

Desde `api-sportiva`:

```bash
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan queue:restart
```

El `queue:restart` hace falta porque los mails salen desde el worker de la cola, y el worker sigue con la configuración vieja hasta que se reinicia.

### 4. Probar

```bash
# Un mail de prueba sin pasar por la cola: confirma la cuenta y la clave.
docker compose exec backend php artisan mail:test tu-direccion@gmail.com --sync

# Lo mismo pasando por la cola, como los mails reales.
docker compose exec backend php artisan mail:test tu-direccion@gmail.com
```

Si llega, ya podés usar la app. Por ejemplo, en **Comunicados**, elegí una categoría donde el jugador o el tutor tenga tu email.

### Antes de la demo

- **Revisá los emails cargados.** Con Gmail configurado, cualquier prueba les manda mails de verdad a los emails de jugadores, tutores y usuarios. Para la demo, cargá emails propios del equipo, no direcciones inventadas ni de terceros.
- **Límite de Gmail:** una cuenta común manda hasta unos 500 destinatarios por día. Alcanza para una demo, no para un club real.
- **Spam:** los primeros mails pueden caer en spam. Mandá uno de prueba antes y marcalo como "No es spam".
- **Si falla un envío**, queda como "Falló" en el historial del comunicado, con el error de Gmail. Los más comunes:

| Error | Causa | Solución |
|---|---|---|
| `535 Username and Password not accepted` | Usaste la contraseña normal, o la clave tiene espacios | Generá una contraseña de aplicación y pegala sin espacios |
| `Connection could not be established` | Puerto o host mal escritos | `smtp.gmail.com` y `587` |
| El mail no llega y no hay error | El worker sigue con la configuración vieja | `php artisan queue:restart` |

### Volver a Mailpit

Restaurá el bloque de Mailpit en el `.env` (o copialo de `.env.example`) y repetí el paso 3.

## Producción

No se usa Gmail: el remitente tiene que ser el dominio del club o de Sportiva, y hace falta un proveedor con volumen. El equipo eligió **Resend**. La configuración, la verificación del dominio y la prueba de entrega están en [mail-produccion.md](mail-produccion.md).
