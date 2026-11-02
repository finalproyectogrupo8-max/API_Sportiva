# Proposal

## Why

Hoy "Sub 21 femenino" y "Sub 21 masculino" se distinguen solo por el nombre. El sistema no sabe la rama de cada categoría ni el género de cada jugador, así que no puede filtrar por rama ni avisar cuando alguien se suma a la rama equivocada. El caso que lo pide es armar, para la temporada "2027 - primera mitad", una competencia para Sub 21 femenino y otra para Sub 21 masculino (TIZ-40). El front lo espera para FRO-40.

## What Changes

- **Categorías:** campo opcional `branch` (`female`, `male` o `mixed`) en el alta, la edición y la respuesta. Filtro `branch` en `GET /api/categories`. Las categorías existentes quedan sin rama (`null`).
- **Jugadores:** campo opcional `gender` (`female`, `male` u `other`) en el alta, la edición y la respuesta. Filtro `gender` en `GET /api/players`. Los jugadores existentes quedan sin género (`null`).
- **Plantel:** al sumar un jugador a una categoría `female` o `male`, si el género del jugador es el opuesto, responde 422 con un mensaje claro. No bloquean una categoría `mixed` o sin rama, un jugador sin género cargado ni el género `other`.
- **Coherencia al editar:** responden 422 si generan el mismo conflicto con asignaciones **activas**:
  - cambiar la rama de una categoría que ya tiene jugadores del género opuesto;
  - cambiar el género de un jugador que ya está en una categoría de la rama opuesta.
- Ningún campo es obligatorio: los clientes actuales siguen funcionando sin mandarlos.

## Capabilities

### New Capabilities

Ninguna.

### Modified Capabilities

- `club-categories-crud`: la categoría tiene rama opcional, filtrable, y no se puede cambiar a una rama que contradiga su plantel activo.
- `club-players-crud`: el jugador tiene género opcional, filtrable, y no se puede cambiar a uno que contradiga sus categorías activas.
- `club-squads`: sumar un jugador a una categoría de la rama opuesta a su género responde 422.

## Impact

- **Base del club:** dos migraciones de tenant con columnas nullable: `categories.branch` y `players.gender`. Se aplican con `tenants:migrate`.
- **Código:** modelos `Category` y `Player` (`@property` y constantes), `Store/UpdateCategoryRequest`, `Store/UpdatePlayerRequest`, `IndexCategoryRequest`, `IndexPlayerRequest`, `CategoryResource`, `PlayerResource`, `CategoryController`, `PlayerController` y `CategoryMembershipController::store`.
- **API:** campos y filtros nuevos, todos opcionales, sin cambios incompatibles. Hay que regenerar `docs/api/openapi.json`.
- **Docs:** la sección de categorías y jugadores del modelo de datos del club (`docs/modelo-datos-club/`).
- **Fuera de alcance:** la parte de front de FRO-40, incluido duplicar una competencia en la misma temporada. La API ya lo permite: `POST /api/competitions/{competition}/copy` acepta la misma `season_id` y un `name` nuevo. Tampoco se completa la rama ni el género de los datos existentes.
