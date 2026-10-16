# Spec Delta

## ADDED Requirements

### Requirement: La CI verifica que el contrato versionado está al día
El pipeline SHALL regenerar el contrato OpenAPI a partir del código y SHALL fallar si difiere del archivo versionado en el repo. El contrato no SHALL publicarse como artefacto ni en ningún registry.

#### Scenario: Un endpoint cambia sin actualizar el contrato
- **WHEN** un PR modifica una ruta, un Form Request o un Resource sin regenerar el contrato versionado
- **THEN** la CI falla e indica el comando para regenerarlo

#### Scenario: Contrato actualizado
- **WHEN** el contrato versionado coincide con el generado desde el código
- **THEN** ese paso de la CI pasa
