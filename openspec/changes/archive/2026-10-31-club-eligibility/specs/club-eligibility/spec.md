## ADDED Requirements

### Requirement: Habilitación de jugadores

La API SHALL calcular, para cada jugador, los requisitos de las competencias de sus categorías activas y SHALL considerarlo habilitado cuando tenga aprobados y vigentes todos los obligatorios que le aplican. Los requisitos "solo menores" SHALL no aplicar a mayores de 18.

#### Scenario: Jugador completo

- **WHEN** se aprueban todos los requisitos obligatorios de un jugador
- **THEN** la grilla lo muestra habilitado y el resumen de la categoría lo cuenta

#### Scenario: Apto vencido

- **WHEN** pasa la fecha de vencimiento de un requisito aprobado
- **THEN** el requisito figura vencido y el jugador deja de estar habilitado

#### Scenario: Requisito con archivo

- **WHEN** se presenta un requisito que pide archivo sin `document_id`
- **THEN** responde 422

### Requirement: Revisión de papeles

La API SHALL permitir presentar requisitos (`player_requirements.update`) y aprobarlos o rechazarlos con motivo (`player_requirements.approve`), con alcance "sus categorías" para presentar y ver.

#### Scenario: El profe no aprueba

- **WHEN** un profe intenta aprobar un requisito de un jugador suyo
- **THEN** recibe 403
