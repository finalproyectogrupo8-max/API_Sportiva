# Spec Delta

## ADDED Requirements

### Requirement: Captura local de emails
El entorno local SHALL incluir un servicio Mailpit conectado a la red de Compose para capturar emails de la API sin enviarlos a destinatarios reales.

#### Scenario: Inspeccionar un email local
- **WHEN** la aplicación envía un email usando la configuración local
- **THEN** Mailpit lo captura y está disponible en la interfaz `http://localhost:8025`

#### Scenario: Aplicación fuera de Docker
- **WHEN** la aplicación Laravel se ejecuta en el host en lugar de Docker
- **THEN** la configuración SMTP permite conectarse a Mailpit mediante `127.0.0.1:1025`
