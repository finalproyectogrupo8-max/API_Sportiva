## Decisiones

- **Sin fila = pendiente.** No se precargan filas al sumar un jugador o un requisito: el estado se calcula al consultar.
- **Vencimiento al momento:** un aprobado con `expires_at` pasado se informa como `expired` aunque el comando diario todavía no haya corrido (`effectiveStatus`). El comando deja el estado guardado al día para filtros y alertas (TIZ-27).
- **Aprobar sin presentar:** se permite (el club recibió el papel en mano). Si el requisito pide archivo, igual hace falta un `document_id` del jugador.
- **Vencimiento al aprobar:** hoy + `validity_months`, salvo que se mande `expires_at` (por ejemplo, la fecha impresa en el apto).
- **Alcance:** el profe ve y presenta en sus categorías; aprobar y rechazar es de `player_requirements.approve`.
- **Tipos para el front:** los resultados se arman con clases `readonly` (`PlayerEligibility`, `RequirementStatus`, `CategoryEligibility`) para que el contrato OpenAPI quede tipado.
