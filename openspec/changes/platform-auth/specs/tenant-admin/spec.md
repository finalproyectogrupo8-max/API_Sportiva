## ADDED Requirements

### Requirement: Acceso restringido
Los endpoints `/api/admin/*` SHALL requerir un token válido y un usuario con `is_platform_admin`.

#### Scenario: Sin token
- **WHEN** se llama sin token
- **THEN** responde 401

#### Scenario: Usuario sin permisos de plataforma
- **WHEN** un usuario sin `is_platform_admin` llama al endpoint
- **THEN** responde 403

### Requirement: Nombre de base derivado
El servidor SHALL derivar `database_name` como `club_<slug>` y MUST NOT aceptarlo desde el cliente. El `slug` SHALL ser inmutable tras la creación.

#### Scenario: Crear club
- **WHEN** un admin crea un club con slug `river`
- **THEN** el club se guarda con `database_name = club_river`

#### Scenario: Intento de elegir la base
- **THEN** responde 422

### Requirement: Listado paginado
El listado SHALL paginar con 20 elementos por defecto y 100 como máximo, validar sus filtros con un Form Request y responder con `TenantResource`.

#### Scenario: Filtro por estado
- **WHEN** se llama con `?status=active`
- **THEN** devuelve solo clubes activos

### Requirement: Baja
La baja de un club SHALL ser lógica y MUST NOT eliminar la base `club_*`. El mecanismo exacto queda sujeto a la decisión sobre `suspended` (ver `design.md`).

#### Scenario: Baja de un club
- **WHEN** un admin elimina un club
- **THEN** el club deja de figurar como activo y su base no se borra