## ADDED Requirements

### Requirement: Listar los miembros de un club
El sistema SHALL exponer `GET /api/admin/tenants/{tenant}/memberships` solo para admins de plataforma. SHALL devolver únicamente las membresías de ese club, paginadas, con el `id`, `name` y `email` del usuario en cada fila, y SHALL aceptar el filtro opcional `is_active`.

#### Scenario: Listado del club
- **WHEN** un admin de plataforma lista las membresías de un club
- **THEN** responde 200 con `{ data, links, meta }`, solo con membresías de ese club y con `user` en cada una

#### Scenario: Filtro por estado
- **WHEN** el admin lista con `is_active=false`
- **THEN** responde solo las membresías desactivadas

#### Scenario: Club inexistente o dado de baja
- **WHEN** el admin lista las membresías de un club que no existe o está dado de baja
- **THEN** responde 404

#### Scenario: Sin permisos de plataforma
- **WHEN** un usuario que no es admin de plataforma lista las membresías
- **THEN** responde 403 con `code: forbidden`

### Requirement: Listar los clubes de un usuario
El sistema SHALL exponer `GET /api/admin/users/{user}/memberships` solo para admins de plataforma. SHALL devolver las membresías del usuario, paginadas, con el club (`tenant`) en cada fila, sin incluir clubes dados de baja, y SHALL aceptar el filtro opcional `is_active`.

#### Scenario: Clubes del usuario
- **WHEN** un admin de plataforma lista las membresías de un usuario
- **THEN** responde 200 con sus membresías y el club de cada una, sin los clubes dados de baja

#### Scenario: Usuario inexistente
- **WHEN** el admin lista las membresías de un usuario que no existe
- **THEN** responde 404

### Requirement: Alta del club con su administrador
`POST /api/admin/tenants` SHALL aceptar un `admin` opcional con `name`, `email` y `password` (mismas reglas que el alta de usuarios). Si viene, SHALL crear el usuario y su membresía `admin` activa en el club, en la misma transacción que el club.

#### Scenario: Club con administrador
- **WHEN** el admin de plataforma crea un club con `admin`
- **THEN** responde 201 y existen el usuario y su membresía activa con rol `admin` en ese club

#### Scenario: Administrador inválido
- **WHEN** el `admin` tiene un email ya registrado o una contraseña corta
- **THEN** responde 422 con el error en `admin.email` o `admin.password` y no se crea ni el club ni el usuario
