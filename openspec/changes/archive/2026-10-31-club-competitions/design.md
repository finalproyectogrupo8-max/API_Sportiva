## Decisiones

- **Requisitos configurables:** nombre, meses de validez (null = no vence), si pide archivo, si es solo para menores y si es obligatorio. El orden es el de la lista.
- **Edición como lista:** `requirements` en `PATCH` reemplaza la lista (con `id` edita, sin `id` crea, los que faltan se borran). Si no viene, no se toca. Igual con `category_ids`.
- **Categorías del mismo deporte** que la competencia: si no, 422 con los nombres de las que sobran.
- **Copiar para otra temporada:** trae los requisitos y no las categorías, que son de la temporada anterior.
- **Alcance:** `competitions.view` con `own` = las competencias que juega alguna de sus categorías. Fuera del alcance, 404.
- **Baja:** soft delete. Cuando existan los requisitos por jugador (TIZ-32) se evaluará si borrar una competencia con jugadores evaluados responde 409.
