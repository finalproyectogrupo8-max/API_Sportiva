# Spec Delta

## Purpose

Ofrece un catálogo de posiciones administrable por deporte en cada club y permite reutilizarlo para describir la participación deportiva de sus jugadores.

## ADDED Requirements

### Requirement: Catálogo de posiciones por deporte

La API SHALL permitir listar las posiciones de un deporte y administrar su catálogo con permisos `positions.*`. Cada posición SHALL pertenecer a un deporte del mismo club y los nombres SHALL ser únicos dentro de ese deporte.

#### Scenario: Listar posiciones por deporte

- **WHEN** un usuario con `positions.view` consulta las posiciones de un deporte del club
- **THEN** recibe las posiciones activas de ese deporte, sin posiciones de otros deportes

#### Scenario: Crear una posición

- **WHEN** un usuario autorizado crea "Arquero" para el deporte fútbol
- **THEN** la posición queda asociada al fútbol y se puede consultar dentro de ese deporte

#### Scenario: Nombre repetido en el mismo deporte

- **WHEN** se intenta crear una posición con un nombre ya usado en ese deporte
- **THEN** responde 422 y no crea otra posición

#### Scenario: Mismo nombre en otro deporte

- **WHEN** se crea una posición con un nombre usado en otro deporte del club
- **THEN** se permite y queda asociada únicamente al deporte seleccionado

#### Scenario: Deporte inexistente o ajeno

- **WHEN** se intenta crear una posición para un deporte que no existe en el club actual
- **THEN** responde 422 y no crea la posición

### Requirement: Administración de posiciones

La API SHALL permitir crear, editar y desactivar posiciones mediante los permisos `positions.create`, `positions.update` y `positions.delete`. El rol estándar `admin` SHALL tener estos permisos; ningún otro rol estándar SHALL recibirlos.

#### Scenario: Usuario sin permiso de administración

- **WHEN** un usuario sin el permiso requerido intenta crear, editar o desactivar una posición
- **THEN** la API responde 403 con `code: forbidden`

#### Scenario: Desactivar posición en uso

- **WHEN** se desactiva una posición que ya está asociada a jugadores
- **THEN** no aparece entre las opciones activas para nuevas asociaciones
- **AND** se conserva y se devuelve en las asociaciones históricas de esos jugadores

#### Scenario: Asignación de posición inactiva

- **WHEN** se intenta asociar a un jugador una posición inactiva
- **THEN** responde 422 y no cambia las asociaciones del jugador

### Requirement: Aislamiento del catálogo por club

Las posiciones SHALL almacenarse y consultarse en la base del club autenticado, junto con su deporte. Una posición de otro club SHALL ser inaccesible.

#### Scenario: Aislamiento entre clubes

- **WHEN** un usuario del club A intenta consultar o asociar una posición que solo existe en el club B
- **THEN** la API no revela ni modifica los datos del club B
