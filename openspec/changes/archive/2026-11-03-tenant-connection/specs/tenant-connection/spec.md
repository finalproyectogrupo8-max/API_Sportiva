# Spec Delta

## Purpose

Garantiza que cada request y cada job de un club lean y escriban solo en la base de ese club, y que el aislamiento entre clubes se pueda probar con tests automáticos.

## ADDED Requirements

### Requirement: La base del club sale del token

En las rutas de club, la API SHALL apuntar la conexión del club a la base registrada en `tenants.database_name` para el club del token, después de validar el token, la membresía activa y el estado `active` del club. MUST NOT tomar la base, el club ni su slug del body, la URL o los headers.

#### Scenario: Request de un club

- **WHEN** un usuario con token del club A llama a una ruta de club
- **THEN** las consultas a la conexión del club van a la base del club A

#### Scenario: El cliente intenta elegir otro club

- **WHEN** un usuario con token del club A envía el slug o el id del club B en el body, la URL o un header
- **THEN** las consultas siguen yendo a la base del club A

#### Scenario: Validación fallida

- **WHEN** el token no tiene club, la membresía está inactiva o el club no está `active`
- **THEN** responde 403 con `tenant_required`, `membership_inactive` o `tenant_inactive`, sin abrir una conexión a ninguna base de club

### Requirement: Aislamiento entre clubes

Un registro de la base de un club MUST NOT ser visible ni modificable con un token de otro club.

#### Scenario: Registro de otro club

- **WHEN** con el token del club A se pide un registro que existe solo en la base del club B
- **THEN** responde 404, y el registro del club B no cambia

#### Scenario: Requests consecutivos de clubes distintos

- **WHEN** el mismo proceso atiende un request del club A y después uno del club B
- **THEN** el segundo request consulta solo la base del club B

### Requirement: Sin contexto de club no hay base de club

Fuera de un request o un job de club, la conexión del club SHALL no apuntar a ninguna base. Una consulta sin contexto MUST fallar en lugar de usar la base del último club.

#### Scenario: Consulta después de un request

- **WHEN** termina un request de club y se consulta la conexión del club sin volver a establecer el contexto
- **THEN** la consulta falla con un error de conexión sin base seleccionada

### Requirement: Nombre de base validado

Antes de conectarse, la API SHALL validar que el nombre de la base del club cumpla la convención `club_<slug>` (minúsculas, dígitos y `_`, como máximo 64 caracteres). Si no la cumple, MUST NOT conectarse y SHALL registrar el error sin exponer el nombre al cliente.

#### Scenario: Nombre inválido en la base central

- **WHEN** `tenants.database_name` de un club tiene un valor fuera de la convención
- **THEN** el request responde 500 con el formato de error de la API y no se abre ninguna conexión de club

### Requirement: Jobs de club

Un job de club SHALL recibir el id del club y establecer la conexión del club al empezar, validando que el club exista. Al terminar, con éxito o con error, la conexión SHALL quedar sin base.

#### Scenario: Job de un club

- **WHEN** un worker procesa un job del club A y después uno del club B
- **THEN** cada job consulta solo la base de su club

### Requirement: Operaciones sobre la base de un club

La API SHALL ofrecer, para uso interno (alta de clubes, comandos y tests), crear la base de un club, aplicar en ella las migraciones de club y borrarla. Crear y migrar SHALL ser idempotentes. Las migraciones de club MUST NOT aplicarse en la base central.

#### Scenario: Crear y migrar dos veces

- **WHEN** se crea y migra la base de un club que ya existe y ya está migrada
- **THEN** la operación termina sin error y sin cambiar los datos

#### Scenario: Migraciones de club separadas

- **WHEN** se migra la base de un club
- **THEN** solo se aplican las migraciones de club, y la tabla `migrations` de la base central no cambia

### Requirement: Tests multi-club

La suite de tests SHALL poder crear dos clubes de prueba con sus bases migradas, actuar como un usuario de cada uno y empezar cada test con las tablas de club vacías.

#### Scenario: Test de aislamiento

- **WHEN** un test crea un registro en el club B y lo pide con el token del club A
- **THEN** recibe 404

#### Scenario: Tests independientes

- **WHEN** un test deja registros en la base de un club de prueba
- **THEN** el siguiente test empieza con esas tablas vacías

### Requirement: Los tests no tocan bases de club reales

En el entorno `testing`, la conexión del club MUST NOT apuntar a una base que no empiece con `club_test_`. Si lo intenta, el test SHALL fallar con un mensaje que explique la causa.

#### Scenario: Club de desarrollo en un test

- **WHEN** un test intenta usar un club cuya base es `club_river`
- **THEN** el test falla antes de conectarse
