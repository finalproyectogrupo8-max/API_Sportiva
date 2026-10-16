# api-error-responses Specification

## Purpose

Garantiza que todas las rutas `/api/*` respondan JSON, también en los errores. Así, el cliente API del frontend puede mapear los status (401, 403, 404, 422, 5xx) sin tener que parsear HTML.

## Requirements

### Requirement: Los errores de la API son JSON
Toda respuesta de error en rutas bajo `/api/` SHALL tener `Content-Type: application/json` y un campo `message`, aunque el request no envíe `Accept: application/json`.

#### Scenario: Ruta inexistente
- **WHEN** un cliente pide `GET /api/no-existe` sin el header `Accept`
- **THEN** la respuesta es `404` en JSON con un campo `message`

#### Scenario: Método no permitido
- **WHEN** un cliente usa en una ruta existente de `/api/` un método HTTP que no soporta
- **THEN** la respuesta es `405` en JSON con un campo `message`

### Requirement: Los errores de validación usan el formato de Laravel
Los errores de validación en rutas `/api/` SHALL responder `422` con `{"message": ..., "errors": {"campo": [...]}}`, que es el formato que espera el frontend.

#### Scenario: Request inválido
- **WHEN** un request a un endpoint de `/api/` falla la validación de un campo
- **THEN** la respuesta es `422` con `message` y con `errors`, indexado por el nombre del campo

### Requirement: Los errores internos no filtran detalles fuera de local
Con `APP_DEBUG=false`, un error no controlado en `/api/` SHALL responder `500` en JSON con un mensaje genérico, sin stack trace, rutas de archivos ni mensajes de excepción.

#### Scenario: Excepción no controlada en producción
- **WHEN** `APP_DEBUG` es `false` y un endpoint de `/api/` lanza una excepción no controlada
- **THEN** la respuesta es `500` en JSON con un `message` genérico
- **AND** el cuerpo no contiene `trace`, `file`, `line` ni `exception`
