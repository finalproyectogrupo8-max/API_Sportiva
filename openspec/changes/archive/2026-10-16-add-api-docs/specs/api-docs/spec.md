# Spec Delta

## Purpose

Publica el contrato OpenAPI de la API y una documentación navegable con Swagger UI, para que el equipo explore los endpoints y el frontend genere sus tipos. El acceso queda restringido según el entorno.

## ADDED Requirements

### Requirement: Contrato OpenAPI en JSON
La API SHALL servir en `GET /api/docs/openapi.json` un documento OpenAPI 3.1 válido, generado a partir del código. El documento SHALL incluir todas las rutas de la API bajo `/api`.

#### Scenario: El contrato incluye los endpoints existentes
- **WHEN** un cliente con acceso pide `GET /api/docs/openapi.json`
- **THEN** la respuesta es `200` en JSON, con `openapi` en una versión `3.1.x`
- **AND** `paths` incluye la ruta del endpoint de health

### Requirement: La documentación no se documenta a sí misma
Las rutas `/api/docs` y `/api/docs/openapi.json` no SHALL aparecer en `paths` del contrato.

#### Scenario: Rutas de documentación excluidas
- **WHEN** se genera el contrato
- **THEN** ninguna entrada de `paths` empieza con `/docs`

### Requirement: Swagger UI en /api/docs
La API SHALL servir en `GET /api/docs` una página HTML con Swagger UI que cargue el contrato de `/api/docs/openapi.json`. La versión de Swagger UI SHALL ser fija, y sus recursos SHALL cargarse con verificación de integridad.

#### Scenario: Abrir la documentación en local
- **WHEN** un dev abre `http://127.0.0.1:8000/api/docs` en el entorno local
- **THEN** ve Swagger UI con los endpoints del contrato y puede ejecutar requests con "Try it out"

### Requirement: Acceso abierto en local
En el entorno `local`, la documentación y el contrato SHALL ser accesibles sin credenciales.

#### Scenario: Sin credenciales en local
- **WHEN** `APP_ENV` es `local` y un cliente pide `/api/docs` o `/api/docs/openapi.json` sin credenciales
- **THEN** la respuesta es `200`

### Requirement: Acceso con HTTP Basic en staging
En el entorno `staging`, la documentación y el contrato SHALL requerir HTTP Basic con las credenciales configuradas en variables de entorno. Si las credenciales no están configuradas, SHALL responder `404`.

#### Scenario: Sin credenciales en staging
- **WHEN** `APP_ENV` es `staging`, las credenciales están configuradas y el request no trae `Authorization`
- **THEN** la respuesta es `401` con el header `WWW-Authenticate: Basic`

#### Scenario: Credenciales incorrectas en staging
- **WHEN** `APP_ENV` es `staging` y el request trae credenciales que no coinciden
- **THEN** la respuesta es `401`

#### Scenario: Credenciales correctas en staging
- **WHEN** `APP_ENV` es `staging` y el request trae las credenciales configuradas
- **THEN** la respuesta es `200`

#### Scenario: Staging sin credenciales configuradas
- **WHEN** `APP_ENV` es `staging` y falta alguna de las dos variables de credenciales
- **THEN** la respuesta es `404`, con o sin `Authorization`

### Requirement: Documentación desactivada fuera de local y staging
En cualquier entorno que no sea `local` ni `staging`, incluido `production`, la documentación y el contrato SHALL responder `404`, como si las rutas no existieran.

#### Scenario: Producción
- **WHEN** `APP_ENV` es `production` y un cliente pide `/api/docs` o `/api/docs/openapi.json`, con o sin credenciales
- **THEN** la respuesta es `404`

### Requirement: La documentación no se indexa ni se cachea
Las respuestas de `/api/docs` y `/api/docs/openapi.json` SHALL incluir los headers `X-Robots-Tag: noindex` y `Cache-Control: no-store`.

#### Scenario: Headers de las respuestas
- **WHEN** un cliente con acceso pide `/api/docs`
- **THEN** la respuesta incluye `X-Robots-Tag: noindex` y `Cache-Control: no-store`
