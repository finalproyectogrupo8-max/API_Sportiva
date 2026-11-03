# Spec Delta

## ADDED Requirements

### Requirement: Destinatarios de alertas sin el rol coordinator

Las alertas operativas SHALL notificar a los profes vigentes de la categoría, al Administrador del club y a los coordinadores de deporte asignados al deporte correspondiente. La resolución de destinatarios SHALL NOT depender del rol retirado `coordinator`.

#### Scenario: Alerta de una categoría

- **WHEN** se genera una alerta para una categoría
- **THEN** la reciben sus profes vigentes, los Administradores del club y los coordinadores asignados al deporte de esa categoría
- **AND** no se duplica una notificación a un usuario que coincida con más de un criterio

#### Scenario: Coordinador de otro deporte

- **WHEN** se genera una alerta para una categoría y el usuario `sport_coordinator` no tiene asignado ese deporte
- **THEN** el usuario no recibe esa alerta
