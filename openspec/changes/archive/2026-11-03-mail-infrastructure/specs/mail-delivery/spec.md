# Spec Delta

## Purpose

Proporciona una forma consistente y reutilizable de enviar emails de Sportiva en forma asíncrona, probarlos localmente sin contactar destinatarios reales y configurar el envío de producción.

## ADDED Requirements

### Requirement: Envío asíncrono de emails
Los emails de la aplicación SHALL encolarse por defecto y procesarse mediante el worker configurado. Una opción explícita de envío síncrono SHALL estar disponible para pruebas operativas cuando no haya worker.

#### Scenario: Envío normal
- **WHEN** la aplicación solicita enviar un email
- **THEN** el email queda encolado para que lo procese el worker existente

#### Scenario: Prueba síncrona solicitada
- **WHEN** un operador ejecuta la prueba de correo con la opción síncrona
- **THEN** el email se envía en el mismo proceso sin requerir worker

### Requirement: Identidad visual y contenido de email
Los emails SHALL usar una plantilla compartida con la marca Sportiva, contenido en español rioplatense y colores legibles en modos claro y oscuro.

#### Scenario: Renderizado de email de marca
- **WHEN** se renderiza un email de la aplicación con la plantilla compartida
- **THEN** muestra la marca Sportiva, enlaces y botón principal con contraste legible, y un pie de página

#### Scenario: Textos del email
- **WHEN** se renderiza el email de prueba
- **THEN** sus textos están en español rioplatense y usan voseo profesional

### Requirement: Comando de prueba de correo
El sistema SHALL ofrecer un comando `mail:test {email}` que envíe un ejemplo con título, párrafo, botón enlazado al frontend y pie; SHALL rechazar direcciones inválidas y mostrar la configuración de transporte sin credenciales.

#### Scenario: Envío de prueba
- **WHEN** un operador ejecuta `mail:test` con un email válido
- **THEN** se envía el ejemplo usando el transporte configurado y la salida informa mailer, host y remitente sin credenciales

#### Scenario: Email inválido
- **WHEN** un operador ejecuta `mail:test` con una dirección inválida
- **THEN** el comando termina con error y explica que el email no es válido

### Requirement: Envío de producción configurado
La configuración de producción SHALL permitir enviar emails mediante Resend una vez configurados el API key y un dominio de remitente verificado.

#### Scenario: Dominio verificado
- **WHEN** producción tiene el proveedor configurado y el dominio remitente verificado
- **THEN** el envío de prueba llega al destinatario y puede verificarse en el proveedor

#### Scenario: Dominio no verificado
- **WHEN** se intenta enviar antes de completar la verificación DNS del dominio
- **THEN** el runbook identifica los pasos de verificación SPF, DKIM y DMARC necesarios para habilitar el envío confiable
