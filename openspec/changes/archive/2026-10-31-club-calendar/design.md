## Decisiones

- **Modelo `GameMatch`:** `match` es palabra reservada de PHP. La tabla sigue siendo `matches` y la ruta `/api/matches/{match}`.
- **Alcance "sus categorías":** partidos, entrenamientos y asistencia se filtran por `category_id` (trait `ScopedByCategory`). Fuera del alcance se responde 404.
- **Resultado:** `PATCH` con `our_score` y `opponent_score` pasa el partido a `played` si no se manda otro estado.
- **Asistencia:** el `PUT` recibe el lote completo y hace `updateOrCreate` por jugador y evento; los `UNIQUE (player_id, match_id)` y `(player_id, training_id)` lo garantizan. Solo se aceptan jugadores con asignación activa en la categoría, o que ya tengan asistencia cargada en ese evento (para corregirla si cambiaron de categoría). `recorded_by` es el `club_user` del token.
- **Calendario:** cada parte respeta su permiso (`matches.view`, `trainings.view`) y su alcance. Rango máximo de 62 días.
- **Fuera de este change:** `competition_id` (TIZ-31) y los campos de publicación de la convocatoria (TIZ-33).
