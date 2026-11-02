# Design

## Context

- `CategoryMembershipController::store` ya valida las reglas del plantel (una categoría activa por deporte y número único) dentro de una transacción con `lockForUpdate`, y responde 422 con `ValidationException` en `player_id`.
- Los listados filtran con `when(...)` sobre los campos validados en `IndexCategoryRequest` e `IndexPlayerRequest`.
- Los demás valores fijos de los modelos de club se guardan como `string` con constantes en el modelo (`Player::STATUSES` o `PlayerContact::RELATIONSHIPS`) y se validan con `Rule::in`, no con enums de MySQL.

## Goals / Non-Goals

**Goals:**

- Que la regla de rama y género viva en un solo lugar y la usen las tres validaciones: sumar al plantel, cambiar la rama y cambiar el género.

**Non-Goals:**

- Inferir la rama desde el nombre de la categoría ("femenino" o "masculino") en los datos existentes.
- Reglas de rama en competencias o en convocatorias: se heredan, porque solo se convoca a quien está en el plantel.

## Decisions

### 1. Columnas `string` con constantes, no `enum` de MySQL

- `categories.branch` es `string(10)` nullable, con `Category::BRANCHES = ['female', 'male', 'mixed']`.
- `players.gender` es `string(10)` nullable, con `Player::GENDERS = ['female', 'male', 'other']`.
- Se validan con `Rule::in`.

**Por qué:** sigue el estilo del resto del esquema del club. Además, sumar un valor no necesita una migración que altere la columna en todas las bases de club.

**Alternativa descartada:** `enum` de MySQL, como dice el ticket. Da el mismo resultado visible, pero agregar un valor obliga a un `ALTER TABLE` en cada club.

### 2. Una sola regla: `BranchRule`

`App\Club\Squads\BranchRule::conflicts(?string $branch, ?string $gender): bool` es verdadero solo para `female` con `male` y para `male` con `female`. Cualquier `null`, `mixed` u `other` da falso.

La usan tres lugares:

- `CategoryMembershipController::store`: dentro de la transacción existente, antes de crear la asignación.
- `CategoryController::update`: si cambia `branch`, revisa los jugadores con asignación activa en la categoría.
- `PlayerController::update`: si cambia `gender`, revisa las categorías activas del jugador.

**Por qué `other` no bloquea:** el ticket dice "si el género no coincide", pero bloquear `other` en las categorías femeninas o masculinas dejaría sin categoría a esos jugadores, salvo que el club cree una mixta. La regla bloquea solo la contradicción explícita, y el coordinador decide en los demás casos.

### 3. Los mensajes de error

Cada mensaje nombra la rama y al jugador, por ejemplo: "Juan Pérez no puede sumarse a Sub 21 femenino: la categoría es de rama femenina." Los errores van en `player_id` (plantel), `branch` (categoría) y `gender` (jugador), igual que los 422 existentes.

## Risks / Trade-offs

- [Revisar las asignaciones activas al editar suma una consulta] → solo cuando el campo cambia y se resuelve con un `whereHas` sobre las asignaciones activas, que ya tienen índice por categoría.
- [Datos existentes sin rama ni género] → no bloquean nada. Cargarlos es una tarea de cada club desde el front.

## Migration Plan

1. Dos migraciones de tenant con columnas nullable y sin valor por defecto. Se aplican con `tenants:migrate` y no tocan los datos existentes.
2. Rollback: las migraciones tienen `down()` (borran las columnas). Revertir el código no rompe nada, porque las columnas son opcionales.
