# Spec Delta

## Purpose

Expone un endpoint que informa si la API está viva y puede usar su base central. Lo usan los devs, la CI y, más adelante, el monitoreo del deploy, sin revelar detalles internos de la infraestructura.

## ADDED Requirements

### Requirement: Endpoint de health
La API SHALL exponer `GET /api/health` sin autenticación. SHALL responder JSON con un campo `status` y un objeto `checks` que incluye el estado de la base central.

#### Scenario: Todo funciona
- **WHEN** se llama a `GET /api/health` y la base central responde
- **THEN** la respuesta es `200` con `{"status": "ok", "checks": {"database": "ok"}}`

### Requirement: Health refleja una base caída
Si la base central no responde, el endpoint SHALL devolver `503` en lugar de un error genérico, para que un monitor pueda distinguirlo.

#### Scenario: La base no está disponible
- **WHEN** se llama a `GET /api/health` y la conexión a la base central falla
- **THEN** la respuesta es `503` con `{"status": "error", "checks": {"database": "error"}}`

### Requirement: Health no expone información sensible
La respuesta del endpoint de health no SHALL incluir hostnames, nombres de bases, usuarios, versiones de software, rutas, mensajes de excepción ni stack traces, en ningún caso y con ningún valor de `APP_DEBUG`.

#### Scenario: Falla de la base con debug activo
- **WHEN** `APP_DEBUG` es `true`, la conexión a la base central falla y se llama a `GET /api/health`
- **THEN** el cuerpo de la respuesta solo contiene los campos `status` y `checks`, sin el mensaje de la excepción
