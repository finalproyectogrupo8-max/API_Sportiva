## Decisiones

- **Una asignación activa por deporte.** Un jugador puede jugar en categorías de deportes distintos, pero no en dos del mismo. El pase de categoría se hace en un solo request con `close_previous: true`, que cierra la anterior con la fecha de inicio de la nueva. El historial no se borra.
- **Número de camiseta** único entre las asignaciones activas de la categoría. Al cerrar una asignación, el número queda libre.
- **Contacto principal:** uno por jugador. El primero que se carga lo es; marcar otro desmarca al anterior; si se borra el principal, el más antiguo que queda pasa a serlo. Un contacto necesita teléfono o mail.
- **Alcance "sus categorías"** (`own`): jugadores con asignación activa en las categorías del usuario. Fuera del alcance se responde 404. La asignación de un profe a una categoría (`category_coaches`) es lo que define ese alcance, así que asignar un profe le da acceso al plantel.
- **Baja de jugador:** soft delete y cierre de sus asignaciones activas.
- **Permisos:** `players.*`; `categories.manage_players` arma el plantel; `categories.manage_coaches` asigna profes; `player_contacts.view` y `.update`. Los chequeos van en el trait `ChecksClubScope`.
- **D15 (parcial):** `document_number` opcional; nada médico en `notes`.
