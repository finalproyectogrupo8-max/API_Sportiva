# Proposal

## Why

El rol `coordinator` concentra facultades generales que deben quedar bajo el Administrador del club, mientras que `sport_coordinator` necesita un acceso mucho más acotado. Además, `preferred_position` es texto libre y no permite administrar posiciones por deporte ni asociar varias posiciones a un jugador de forma consistente.

## What Changes

- **BREAKING:** retirar `coordinator` del catálogo de roles estándar y eliminar sus asignaciones existentes sin reasignarlas automáticamente. El Administrador del club reasignará a cada usuario después de revisar su acceso.
- Reducir `sport_coordinator` a consulta de temporadas, categorías y competencias de sus deportes, tanto en permisos/API como en las solapas visibles del frontend. La administración general queda en manos del Administrador del club.
- Incorporar un catálogo administrable de posiciones asociado a cada deporte y permitir asociar varias posiciones por deporte a un jugador, incluso al darlo de alta.
- Mantener la capacidad de que un usuario tenga membresías activas en varios clubes. Ya está soportada por la clave única usuario-club; se cubrirá con pruebas de regresión, sin rediseñar el modelo.

## Capabilities

### New Capabilities

- `club-sport-positions`: administrar posiciones por deporte y asociar posiciones deportivas a jugadores.

### Modified Capabilities

- `club-access-control`: retirar el rol estándar `coordinator` y limitar los permisos de `sport_coordinator`.
- `club-sport-scope`: precisar el acceso de solo lectura del coordinador de deporte a temporadas, categorías y competencias de sus deportes.
- `club-players-crud`: asociar una o más posiciones deportivas a un jugador durante su alta y edición.
- `club-alerts-notifications`: dejar de buscar destinatarios con el rol retirado y conservar las alertas para administradores y coordinadores de deporte según su alcance.

### Unchanged Capabilities

- `tenant-memberships`: ya permite que un mismo usuario pertenezca a distintos clubes. Se preservará y verificará con pruebas de regresión; no se cambia su comportamiento especificado.

## Impact

- Backend Laravel: `config/club-permissions.php`, sincronización y datos de roles en cada base de club, permisos/policies, modelos, requests, resources y rutas de deportes, posiciones y jugadores.
- Base de cada club: migraciones para el catálogo `positions` asociado a deportes y la asociación de múltiples posiciones a jugadores. Se debe conservar el dato histórico actualmente almacenado en `players.preferred_position` durante la transición.
- Pruebas y documentación del backend: autorizaciones por rol/alcance, destinatarios de alertas, catálogo por deporte, alta y edición de jugadores, limpieza de asignaciones antiguas y acceso multiclub.
- Frontend `Front-Sportiva` (repositorio no presente en este workspace): mostrar al `sport_coordinator` únicamente las solapas Temporadas, Categorías y Competencias y seleccionar posiciones del deporte correspondiente en el alta/edición de jugadores.
- Contrato API: incluir posiciones y su deporte en los recursos de jugador y publicar los endpoints necesarios para administrar el catálogo y consultarlo por deporte.
