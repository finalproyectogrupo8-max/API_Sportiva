# newsletter Specification

## Purpose

Permitir que visitantes se suscriban al newsletter y que el equipo de plataforma consulte y exporte las direcciones registradas.

## Requirements

### Requirement: Suscripción pública

La API SHALL aceptar un email válido sin autenticación en `POST /api/newsletter`, guardar la suscripción y responder 201. Un email ya registrado MUST NOT crear una fila duplicada.

#### Scenario: Suscripción válida

- **WHEN** un visitante envía un email válido no registrado
- **THEN** responde 201 con un mensaje de confirmación y persiste la suscripción

#### Scenario: Email faltante, inválido o repetido

- **WHEN** el email falta, no tiene formato válido o ya existe
- **THEN** responde 422 con un error de validación para `email`

### Requirement: Consulta administrativa de suscriptores

Solo un administrador de plataforma SHALL poder consultar `/api/admin/newsletter-subscribers`. La respuesta JSON SHALL paginar hasta 100 filas y permitir buscar por email; `format=csv` SHALL descargar el listado completo.

#### Scenario: Búsqueda paginada

- **WHEN** un administrador busca suscriptores por una parte del email
- **THEN** recibe únicamente coincidencias en una respuesta paginada

#### Scenario: Exportación CSV

- **WHEN** un administrador solicita `format=csv`
- **THEN** recibe un archivo CSV con el email y la fecha de suscripción

#### Scenario: Sin permisos de plataforma

- **WHEN** un usuario que no es administrador de plataforma consulta el listado
- **THEN** responde 403
