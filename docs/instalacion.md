# Guía de instalación

Esta guía deja Sportiva andando en una PC con Windows desde cero: no hace falta tener nada instalado. Al final quedan la API, la app, una base con datos de ejemplo, un visor de base y un buzón de mails de prueba.

Para el día a día (comandos, tests, migraciones) está [desarrollo-local.md](desarrollo-local.md). Las cuentas y los datos de ejemplo están en [datos-demo.md](datos-demo.md).

## 1. Qué necesitás

| Requisito | Detalle |
| --- | --- |
| Windows | 10 versión 2004 (build 19041) o posterior, u 11, de 64 bits |
| Virtualización | Activada en el BIOS/UEFI (Intel VT-x o AMD-V/SVM). Casi todas las PCs la traen activada; el script avisa si no |
| Memoria | 8 GB de RAM o más |
| Espacio en disco | Unos 20 GB libres (Ubuntu, Docker, las imágenes y las dependencias) |
| Internet | Para bajar WSL, Ubuntu, Docker Desktop y las imágenes |
| Permisos | Correr el script con una cuenta normal, pero que pueda **aprobar el aviso de Windows (UAC)** una vez: ser administrador o saber la contraseña de uno |
| Cuenta de GitHub | **No hace falta**: los repos son públicos. Solo sirve si vas a hacer commits |

No instales nada a mano: el script se encarga de WSL, Ubuntu, Docker Desktop, Git y VS Code.

## 2. Instalación en un comando

Abrí **PowerShell normal** (no "Ejecutar como administrador") y pegá:

```powershell
irm https://raw.githubusercontent.com/finalproyectogrupo8-max/API_Sportiva/main/scripts/setup-windows.ps1 | iex
```

### Qué te va a preguntar

| Paso | Qué hace | Qué tenés que hacer vos |
| --- | --- | --- |
| WSL 2 | Activa WSL y la Plataforma de máquina virtual si faltan | Responder **s** y tocar **Sí** en el aviso de Windows (UAC). Si pide reiniciar, aceptá: al volver a iniciar sesión el script sigue solo |
| Ubuntu | Lo instala sin la ventana de primer uso | Elegir **usuario y contraseña de Linux** (la contraseña es para `sudo`) |
| Docker Desktop | Lo instala y lo arranca | Nada |
| Git | Verifica el acceso a los repos y pregunta tu usuario de GitHub | **Opcional:** dejalo vacío y tocá Enter si no vas a hacer commits |
| Proyectos | Clona la API y el front dentro de WSL | Nada |
| VS Code | Lo instala con la extensión WSL | Aceptar las extensiones recomendadas cuando las ofrezca |

Después de esas preguntas no tenés que hacer nada más. El script instala, levanta todo y al final muestra las URLs.

### Si preferís leerlo antes

```powershell
irm https://raw.githubusercontent.com/finalproyectogrupo8-max/API_Sportiva/main/scripts/setup-windows.ps1 -OutFile setup-windows.ps1
notepad setup-windows.ps1
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1
```

`-ExecutionPolicy Bypass` aplica solo a ese proceso y no cambia la configuración de tu Windows.

## 3. Cómo saber que terminó bien

Al final vas a ver un resumen así:

```
Listo: todo quedo instalado y levantado.

Donde esta cada cosa:
  App (front):         http://127.0.0.1:3000
  API (salud):         http://127.0.0.1:8000/api/health
  Visor de base:       http://127.0.0.1:8080
  Mails de prueba:     http://127.0.0.1:8025
  Usuarios y links:    \\wsl$\Ubuntu\home\<usuario>\repos\api-sportiva\ACCESOS.txt
```

Si algo quedó pendiente, en vez de "Listo" aparece una lista con lo que faltó y el comando para reintentarlo. **El script nunca se corta por un error recuperable**: lo que sí se completó queda funcionando.

Además, el script deja un archivo **`ACCESOS.txt`** en la carpeta de la API con todos los links, usuarios, datos del visor de base y de Mailpit. Abrilo cuando no te acuerdes de una URL o una contraseña. No se sube al repositorio: es solo de tu máquina.

## 4. Entrar a la app

Abrí <http://127.0.0.1:3000> y usá cualquiera de estas cuentas. La base ya viene con datos: no hace falta crear nada.

| Email | Contraseña | Qué es | Dónde entra |
| --- | --- | --- | --- |
| `admin.club@sportiva.com` | `demo1234` | Administrador del club | Club **Demo Sportiva** (`/c/demo`) |
| `coordinador@sportiva.com` | `demo1234` | Coordinador deportivo | Club Demo |
| `coordinador.futbol@sportiva.com` | `demo1234` | Coordinador de Fútbol | Club Demo |
| `profe@sportiva.com` | `demo1234` | Profe (Sub-13 y Sub-15) | Club Demo |
| `preparador@sportiva.com` | `demo1234` | Preparador físico | Club Demo |
| `utilero@sportiva.com` | `demo1234` | Utilero (stock e indumentaria) | Club Demo |
| `mantenimiento@sportiva.com` | `demo1234` | Mantenimiento (canchas) | Club Demo |
| `admin@sportiva.com` | `admin123` | Admin de plataforma | Panel `/admin` |
| `general@sportiva.com` | `general123` | Usuario sin club | Muestra "no tenés clubes asignados" |

El club demo tiene 18 jugadores, partidos, entrenamientos, stock, pedidos de indumentaria y de materiales, anuncios y evaluaciones. El detalle está en [datos-demo.md](datos-demo.md).

## 5. Mails: Mailpit

La app **no manda mails a internet** en local: los atrapa **Mailpit**, un buzón de prueba dentro de Docker.

- Abrí <http://127.0.0.1:8025>.
- Todo mail que manda la app aparece ahí, sin importar a qué dirección se haya enviado.
- Para probar: en el login usá **"Olvidé mi contraseña"** con cualquier usuario; el mail aparece en segundos. También llegan las convocatorias y los comunicados.

### Mandar mails reales (opcional)

Para una demo donde el mail le llegue al celular de alguien, se cambia el `.env` de la API para que use Gmail por SMTP. Los pasos completos están en [mail-demo.md](mail-demo.md): crear una cuenta de demo, generar una **contraseña de aplicación** y actualizar `MAIL_*`.

Resumen de las variables:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=sportiva.demo@gmail.com
MAIL_PASSWORD=la-clave-de-16-letras
MAIL_FROM_ADDRESS=sportiva.demo@gmail.com
MAIL_FROM_NAME="Sportiva"
```

Después de cambiarlas, desde la carpeta de la API:

```bash
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan queue:restart
```

El `queue:restart` es obligatorio: los mails salen desde el worker de la cola y sigue con la configuración vieja hasta que se reinicia.

En producción se usa **Resend** con el dominio verificado; el runbook está en [mail-produccion.md](mail-produccion.md). Nunca pongas claves reales en `.env.example`, en un PR ni en un ticket.

## 6. Visor de la base de datos (Adminer)

Abrí <http://127.0.0.1:8080> y completá:

| Campo | Valor |
| --- | --- |
| Sistema | MySQL |
| Servidor | `mysql` |
| Usuario | `DB_USERNAME` del `.env` de la API (por defecto `sportiva`) |
| Contraseña | `DB_PASSWORD` del `.env` de la API |
| Base | `sportiva_platform` (plataforma) o `club_demo` (club) |

Los valores exactos de tu máquina están en `ACCESOS.txt`. Adminer queda solo en `127.0.0.1`: no se publica fuera de tu PC.

Si preferís DBeaver u otro cliente, hay que publicar el puerto de MySQL con `compose.override.yaml`; está explicado en [desarrollo-local.md](desarrollo-local.md#acceso-a-mysql-desde-el-host).

## 7. Uso diario

Desde la carpeta de la API (`~/repos/api-sportiva` en WSL):

| Qué querés hacer | Comando |
| --- | --- |
| Levantar todo | `docker compose up -d` |
| Bajar todo | `docker compose down` |
| Ver qué está corriendo | `docker compose ps` |
| Ver logs de la API | `docker compose logs -f backend` |
| Ver logs de la cola | `docker compose logs -f queue` |
| Volver a dejar todo listo | `bash scripts/setup.sh` (es idempotente) |
| Levantar el front | `cd ~/repos/gestion-operativa-club-deportivo && bash scripts/setup.sh` |

La app queda en <http://127.0.0.1:3000> y la API en <http://127.0.0.1:8000>. La documentación interactiva de la API está en <http://127.0.0.1:8000/api/docs>.

## 8. Si algo falla

1. Leé el mensaje y la lista de **pendientes** del resumen: dice exactamente qué quedó y cómo reintentarlo.
2. Resolvé eso y **volvé a correr el mismo comando**. No se repite lo que ya está hecho.
3. Cada corrida deja un log en `%TEMP%\api-sportiva-setup-<fecha>.log`; la ruta aparece al final. No contiene contraseñas.

| Síntoma | Qué hacer |
| --- | --- |
| `EPROTO: The TLS handshake failed` o `ERR_SSL_PROTOCOL_ERROR` | Estás usando `https://` contra un puerto local que habla HTTP. Usá `http://127.0.0.1:3000` y `http://127.0.0.1:8000` |
| `The requested operation requires elevation` | Ese comando necesita una consola de administrador: menú Inicio, escribí **PowerShell**, clic derecho, **Ejecutar como administrador**. Lo más simple es volver a correr el script y tocar **Sí** en el aviso de Windows |
| Docker dice "Virtual Machine Platform is not enabled" | El script activa esa característica de Windows y reinicia; si no lo hizo, corré `Enable-WindowsOptionalFeature -Online -FeatureName VirtualMachinePlatform -All` en una consola de administrador y reiniciá |
| El script avisa que la virtualización está apagada, o WSL da `0x80370102` | Reiniciá, entrá al BIOS/UEFI (F2, F10, Supr o Esc al prender), activá **Intel Virtualization Technology / VT-x** o **SVM / AMD-V**, guardá y volvé a correr el script |
| La PC es una VM y Docker/WSL no arrancan | Habilitá **virtualización anidada** en el hipervisor (VirtualBox: "Enable Nested VT-x/AMD-V"; VMware: "Virtualize Intel VT-x/EPT") |
| Docker no puede bajar una imagen (`context deadline exceeded`) | Es la conexión a Docker Hub. Reintentá; el visor de base es opcional y el resto funciona igual |
| "El puerto 3000 está en uso" | Cerrá el otro programa o levantá el front con `pnpm dev --port 3001` y actualizá `FRONTEND_URL` en la API |
| El club queda en "Aprovisionando" | La cola no corre: `docker compose ps queue` y `docker compose logs queue` |
| No llega ningún mail | Está en Mailpit (<http://127.0.0.1:8025>). Si tampoco, reiniciá la cola: `php artisan queue:restart` |
| Quiero empezar de cero | `docker compose down -v` borra la base y el volumen; después `bash scripts/setup.sh` |

## 9. Desinstalar

- Para borrar los datos y volver a empezar: `docker compose down -v` (desde la API).
- Para borrar todo el entorno: desinstalá Docker Desktop y la distro Ubuntu desde **Configuración → Aplicaciones**, y eliminá la carpeta `~/repos` dentro de WSL.
