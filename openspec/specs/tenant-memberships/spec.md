# tenant-memberships Specification

## Purpose

Define cómo los administradores de plataforma asocian usuarios con clubes y gestionan el rol y el estado de cada membresía.

## Requirements

### Requirement: Asociar un usuario a un club

`POST /api/admin/tenants/{tenant}/memberships` SHALL crear una membresía para un usuario existente y requerir uno o más `roles` válidos del club. También puede aceptar `sport_ids` para asignar el alcance de un coordinador deportivo. Cada usuario MUST NOT tener más de una membresía por club.

#### Scenario: Asociación válida

- **WHEN** un administrador envía el `user_id` y roles válidos para un club activo
- **THEN** responde 201 con la membresía activa, sus roles y sus deportes asignados

#### Scenario: Membresía duplicada

- **WHEN** el usuario ya tiene una membresía en ese club
- **THEN** responde 422 y no crea otra membresía

#### Scenario: Rol inexistente

- **WHEN** el request incluye un rol que no existe en el catálogo del club
- **THEN** responde 422

#### Scenario: Club dado de baja

- **WHEN** se intenta crear una membresía para un club dado de baja
- **THEN** responde 404

### Requirement: Modificar una membresía

`PATCH /api/admin/tenants/{tenant}/memberships/{membership}` SHALL permitir reemplazar `roles` o `sport_ids` y cambiar `is_active` en la membresía del club indicado; no SHALL permitir cambiar el usuario ni el club asociados. Desactivar la membresía SHALL revocar los tokens del usuario asociados a ese club sin revocar los de otros clubes.

#### Scenario: Cambiar roles y deportes

- **WHEN** un administrador reemplaza los roles o deportes de una membresía
- **THEN** responde 200 con la membresía actualizada y el perfil del club refleja el nuevo acceso

#### Scenario: Membresía de otro club

- **WHEN** la membresía pertenece a un club distinto del indicado en la URL
- **THEN** responde 404

#### Scenario: Intento de cambiar usuario o club

- **WHEN** el request incluye `user_id` o `tenant_id`
- **THEN** responde 422 y la asociación permanece sin cambios

### Requirement: Revocación al desactivar una membresía

Desactivar una membresía SHALL revocar los tokens del usuario asociados al club de esa membresía, sin revocar tokens del mismo usuario asociados a otros clubes.

#### Scenario: Token del club desactivado

- **WHEN** se desactiva una membresía cuyo usuario tiene un token para ese club
- **THEN** el token deja de autenticar requests y responde 401

#### Scenario: Token de otro club

- **WHEN** se desactiva una membresía y el usuario tiene un token para otro club
- **THEN** ese token del otro club continúa siendo válido

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
