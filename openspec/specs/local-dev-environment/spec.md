# local-dev-environment Specification

## Purpose

Permite que cualquier dev clone el repo y tenga la API y su base MySQL andando con Docker Compose, sin instalar PHP, Composer ni MySQL en el host, y con datos locales que persisten entre reinicios.

## Requirements

### Requirement: El entorno levanta sin runtimes en el host
El entorno local SHALL levantar la API y MySQL con Docker Compose como única dependencia del host, además de Git. Composer y Artisan SHALL ejecutarse dentro del contenedor de la API.

#### Scenario: Primer arranque desde un clon limpio
- **WHEN** un dev clona el repo, copia `.env.example` a `.env` y levanta los servicios
- **THEN** los servicios `backend` y `mysql` quedan corriendo
- **AND** `composer install`, `php artisan key:generate` y `php artisan migrate` se ejecutan dentro del contenedor `backend` sin errores

#### Scenario: El host no tiene PHP
- **WHEN** el host no tiene PHP, Composer ni MySQL instalados
- **THEN** todos los comandos documentados del flujo de desarrollo funcionan igual

### Requirement: Setup en un solo comando
El repo SHALL proveer un comando de setup idempotente, que deje el entorno levantado y migrado desde un clon. En Windows, SHALL proveer un bootstrap que verifique WSL 2 y Docker Desktop y clone el repo dentro de WSL. Toda instalación o cambio global SHALL pedir confirmación.

#### Scenario: Setup desde un clon
- **WHEN** un dev ejecuta el comando de setup en un clon recién hecho
- **THEN** los servicios quedan corriendo, las dependencias instaladas, la `APP_KEY` generada y las migraciones aplicadas

#### Scenario: Volver a correr el setup
- **WHEN** un dev ejecuta el setup sobre un entorno que ya funciona
- **THEN** no se pierden datos ni se sobrescriben el `.env` ni la `APP_KEY`

#### Scenario: Windows sin prerequisitos
- **WHEN** un dev ejecuta el bootstrap de Windows y le falta WSL o Docker Desktop
- **THEN** el bootstrap ofrece instalarlos y no lo hace sin la confirmación del dev

### Requirement: La API espera a que la base esté lista
El servicio de la API SHALL arrancar recién cuando MySQL acepta conexiones, según un healthcheck. No SHALL alcanzar con que el contenedor de MySQL esté iniciado.

#### Scenario: MySQL tarda en inicializarse
- **WHEN** se levanta el entorno por primera vez y MySQL todavía está creando sus archivos de datos
- **THEN** el servicio `backend` no arranca hasta que el healthcheck de `mysql` pasa

### Requirement: La API se conecta a la base por el nombre del servicio
La API SHALL conectarse a MySQL usando el hostname del servicio de Compose (`mysql`), con los datos de conexión tomados de variables de entorno.

#### Scenario: Configuración por defecto
- **WHEN** un dev usa los valores de `.env.example` sin cambios
- **THEN** la API se conecta a la base `sportiva_platform` en el host `mysql`

### Requirement: Los datos locales persisten
Los datos de MySQL SHALL guardarse en un volumen nombrado de Docker, y SHALL sobrevivir a detener, recrear y reconstruir los contenedores.

#### Scenario: Reinicio del entorno
- **WHEN** un dev detiene y recrea los servicios sin borrar volúmenes
- **THEN** las tablas y los datos migrados siguen presentes

#### Scenario: Reset explícito
- **WHEN** un dev sigue el procedimiento documentado para resetear los datos locales
- **THEN** la documentación advierte antes que esa operación destruye todos los datos locales de MySQL

### Requirement: MySQL no se expone a la red
El puerto de MySQL no SHALL publicarse en el host por defecto. Si se habilita el acceso opcional desde el host, SHALL quedar atado a `127.0.0.1`. La API SHALL publicarse solo en `127.0.0.1`.

#### Scenario: Configuración por defecto
- **WHEN** el entorno corre con la configuración por defecto
- **THEN** ningún puerto de MySQL está publicado en el host
- **AND** la API solo es accesible desde la propia máquina

#### Scenario: Acceso opcional con un cliente de base de datos
- **WHEN** un dev habilita el acceso documentado para un cliente como DBeaver
- **THEN** MySQL queda accesible solo en `127.0.0.1` y no en todas las interfaces

### Requirement: Los secretos no se versionan ni se incluyen en la imagen
El repo SHALL contener solo archivos de ejemplo sin secretos reales. La imagen no SHALL contener `.env`, dumps de base de datos ni credenciales.

#### Scenario: Build de la imagen con un .env local
- **WHEN** un dev tiene un `.env` con valores locales y hace el build de la imagen
- **THEN** la imagen resultante no contiene ese `.env`

#### Scenario: Revisión del repo
- **WHEN** se revisan los archivos versionados
- **THEN** `.env` está ignorado por Git y `.env.example` solo tiene valores de desarrollo no sensibles

### Requirement: La aplicación corre sin privilegios
El proceso de la API SHALL correr con un usuario que no es root, sin contenedores privilegiados y sin capabilities innecesarias. SHALL poder escribir en `storage/` y en `bootstrap/cache/`.

#### Scenario: Usuario del proceso
- **WHEN** se inspecciona el proceso que corre en el contenedor `backend`
- **THEN** su usuario no es root

#### Scenario: Escritura de logs y caché
- **WHEN** la API escribe logs o caché en `storage/` o en `bootstrap/cache/`
- **THEN** la escritura funciona sin errores de permisos
