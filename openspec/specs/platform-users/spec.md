# platform-users Specification

## Purpose
Define la administración de usuarios de la plataforma, incluyendo su ciclo de vida, acceso a clubes y protección de credenciales.

## Requirements

### Requirement: ABM de usuarios de plataforma
Los endpoints `/api/admin/users` SHALL permitir crear, listar, ver y editar usuarios, y SHALL requerir autenticación de un administrador de plataforma.

#### Scenario: Crear usuario
- **WHEN** un administrador envía nombre, email único y contraseña de al menos 12 caracteres
- **THEN** responde 201 con los datos del usuario y sin incluir la contraseña

#### Scenario: Consultar usuario
- **WHEN** un administrador solicita un usuario existente
- **THEN** responde 200 con sus datos y sin incluir la contraseña

#### Scenario: Sin permisos de plataforma
- **WHEN** un usuario autenticado que no es administrador llama a un endpoint de usuarios
- **THEN** responde 403 con `code: forbidden`

#### Scenario: Email duplicado
- **WHEN** se intenta crear o actualizar un usuario con un email ya utilizado por otro usuario
- **THEN** responde 422

### Requirement: Protección de `is_platform_admin`
La API MUST NOT permitir asignar ni modificar `is_platform_admin`; la creación de administradores de plataforma SHALL continuar limitada al comando `platform:create-admin`.

#### Scenario: Intento de asignar el privilegio
- **WHEN** un request de alta o edición incluye `is_platform_admin`
- **THEN** responde 422 y el privilegio del usuario no cambia

### Requirement: Desactivación de usuarios
Un usuario SHALL poder ser activado o desactivado mediante `is_active`; un administrador MUST NOT desactivarse a sí mismo. Los usuarios inactivos MUST NOT iniciar sesión ni usar tokens existentes.

#### Scenario: Login de usuario inactivo
- **WHEN** un usuario inactivo envía credenciales correctas a `POST /api/auth/login`
- **THEN** responde 401 con un mensaje que no revela información adicional de la cuenta

#### Scenario: Token de usuario desactivado
- **WHEN** se desactiva un usuario que tiene tokens emitidos
- **THEN** sus tokens se revocan y dejan de autenticar requests

#### Scenario: Auto-desactivación
- **WHEN** un administrador intenta desactivar su propia cuenta
- **THEN** responde 422 y la cuenta permanece activa

#### Scenario: Reactivación
- **WHEN** un administrador activa a un usuario inactivo
- **THEN** el usuario puede volver a iniciar sesión con credenciales válidas

### Requirement: Protección y cambio de contraseña
La contraseña SHALL requerir al menos 12 caracteres al crear o cambiar un usuario, MUST NOT aparecer en respuestas ni en registros de auditoría, y su cambio SHALL revocar todos los tokens del usuario.

#### Scenario: Cambio de contraseña
- **WHEN** un administrador cambia la contraseña de un usuario
- **THEN** responde sin exponer la contraseña y revoca todos los tokens de ese usuario

### Requirement: Listado paginado de usuarios
El listado SHALL paginar con 20 elementos por defecto y un máximo de 100, y SHALL aceptar filtros `search` e `is_active` validados por el endpoint.

#### Scenario: Búsqueda
- **WHEN** se llama con `?search=gomez`
- **THEN** devuelve solo usuarios cuyo nombre o email coincide

#### Scenario: Filtro por estado
- **WHEN** se llama con `?is_active=false`
- **THEN** devuelve solo usuarios inactivos

#### Scenario: `per_page` fuera de rango
- **WHEN** se llama con `per_page` menor que 1 o mayor que 100
- **THEN** el tamaño se limita al rango 1..100 y responde 200
