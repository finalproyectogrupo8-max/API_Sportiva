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
El listado SHALL paginar con 20 elementos por defecto y limitar `per_page` al rango 1..100. SHALL validar sus filtros con un Form Request, responder con `TenantResource` y aceptar `sort` mediante la lista blanca `name`, `slug`, `status`, `created_at` (con prefijo `-` para orden descendente).

#### Scenario: Filtro por estado
- **WHEN** se llama con `?status=active`
- **THEN** devuelve solo clubes activos

#### Scenario: per_page fuera de rango
- **WHEN** se llama con `per_page` mayor a 100 o menor a 1
- **THEN** se acota a 100 o a 1 y responde 200

#### Scenario: sort fuera de la lista blanca
- **WHEN** se llama con `sort` que no es `name`, `slug`, `status` ni `created_at` (con o sin `-`)
- **THEN** responde 422

### Requirement: Baja
La baja de un club SHALL ser un soft delete y MUST NOT eliminar la base `club_*`. Un club dado de baja MUST NOT aparecer en listados ni permitir login ni selección de club, y su `slug` SHALL seguir reservado.

#### Scenario: Baja de un club
- **WHEN** un admin elimina un club
- **THEN** responde 204, la fila queda con `deleted_at` y la base no se borra