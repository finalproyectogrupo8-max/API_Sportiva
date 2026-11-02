# Tasks

## 1. Datos

- [ ] 1.1 Crear las migraciones de tenant `add_branch_to_categories_table` (`string(10)` nullable) y `add_gender_to_players_table` (`string(10)` nullable), con `down()`. Agregar `Category::BRANCHES`, `Player::GENDERS` y los `@property` en los modelos, y actualizar las factories con `null` por defecto. Verificar con `php artisan test --filter=CategoryApiTest` y `--filter=PlayerApiTest` que los tests existentes sigan pasando.
- [ ] 1.2 Crear `App\Club\Squads\BranchRule` con un test unitario que cubra la tabla completa de rama por género (incluidos `null`, `mixed` y `other`). Verificar que el test pase.

## 2. Categorías

- [ ] 2.1 Agregar `branch` a `StoreCategoryRequest` y `UpdateCategoryRequest` (`nullable`, `Rule::in(Category::BRANCHES)`), a `CategoryResource` y el filtro `branch` a `IndexCategoryRequest` y `CategoryController::index`. Tests en `CategoryApiTest`: crear con rama, filtrar, rama inválida (422) y categoría sin rama (`null`).
- [ ] 2.2 En `CategoryController::update`, si cambia `branch`, responder 422 en `branch` cuando haya jugadores activos de la rama opuesta. Tests: cambio bloqueado y cambio permitido a `mixed`.

## 3. Jugadores

- [ ] 3.1 Agregar `gender` a `StorePlayerRequest` y `UpdatePlayerRequest` (`nullable`, `Rule::in(Player::GENDERS)`), a `PlayerResource` y el filtro `gender` a `IndexPlayerRequest` y `PlayerController::index`. Tests en `PlayerApiTest`: alta con género, filtro, valor inválido (422) y jugador sin género (`null`).
- [ ] 3.2 En `PlayerController::update`, si cambia `gender`, responder 422 en `gender` cuando el jugador esté activo en una categoría de la rama opuesta. Tests: cambio bloqueado y cambio permitido a `other`.

## 4. Plantel

- [ ] 4.1 En `CategoryMembershipController::store`, dentro de la transacción, responder 422 en `player_id` si `BranchRule` detecta conflicto. Tests en el test de memberships: masculino en femenina (422), masculino en mixta (OK), sin género en femenina (OK) y categoría sin rama (OK).

## 5. Contrato y documentación

- [ ] 5.1 Regenerar `docs/api/openapi.json` con `scramble:export` y verificar que el diff tenga `branch` y `gender` en los schemas de categoría y jugador y los dos filtros nuevos.
- [ ] 5.2 Documentar `branch`, `gender` y la regla del plantel en `docs/modelo-datos-club/` (en la sección de categorías y jugadores). Verificar que los valores documentados coincidan con `Category::BRANCHES` y `Player::GENDERS`.

## 6. Verificación integrada

- [ ] 6.1 Ejecutar `docker compose exec backend php artisan test` y `docker compose exec backend vendor/bin/pint --test`, y verificar que todo pase.
- [ ] 6.2 En local, correr `docker compose exec backend php artisan tenants:migrate` y verificar en un club existente que las categorías y los jugadores devuelvan `branch` y `gender` en `null`, sin errores.

## Workflow follow-up

- Avisar en FRO-40 que la API está lista, con los nombres de los campos y de los filtros.
- Pasar TIZ-40 a In Review en Linear cuando se abra el PR, y a Done cuando se mergee.
- Archivar el change con `/opsx:archive` después del merge.
