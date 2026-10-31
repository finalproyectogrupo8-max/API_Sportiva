# 9. Competencias y habilitación

[← Índice](README.md)

Ligas y torneos del club, qué categorías los juegan y qué papeles pide cada uno para habilitar a un jugador (TIZ-31). Los requisitos cambian según el deporte y la liga, así que no están fijos en el sistema: los configura cada club. El estado de cada requisito por jugador llega con TIZ-32.

```mermaid
erDiagram
    sports ||--o{ competitions : de
    seasons ||--o{ competitions : en
    competitions ||--o{ competition_requirements : pide
    competitions ||--o{ category_competitions : juegan
    categories ||--o{ category_competitions : juega
    competitions |o--o{ matches : partido
```

## `competitions`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | |
| `sport_id` | BIGINT UNSIGNED | no | FK → `sports.id` | |
| `season_id` | BIGINT UNSIGNED | no | FK → `seasons.id` | |
| `name` | VARCHAR(150) | no | | "Liga Metropolitana de Básquet" |
| `organizer` | VARCHAR(150) | sí | | Liga o federación |
| `registration_deadline` | DATE | sí | | Cierre de inscripción |
| `created_at`, `updated_at` | TIMESTAMP | sí | | |
| `deleted_at` | TIMESTAMP | sí | | *(convención ABM)* |

- Índice: `(season_id, sport_id)`.
- Para la temporada siguiente se copia la competencia (`POST /competitions/{id}/copy`): trae los requisitos, no las categorías.

## `category_competitions`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | |
| `category_id` | BIGINT UNSIGNED | no | FK → `categories.id` | |
| `competition_id` | BIGINT UNSIGNED | no | FK → `competitions.id`, `ON DELETE CASCADE` | |

- `UNIQUE (category_id, competition_id)`. Una categoría puede jugar varias competencias; se le piden los requisitos de todas.
- Las categorías tienen que ser del deporte de la competencia (se valida en la aplicación).

## `competition_requirements`

| Columna | Tipo | Null | Restricciones | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK | |
| `competition_id` | BIGINT UNSIGNED | no | FK → `competitions.id`, `ON DELETE CASCADE` | |
| `name` | VARCHAR(120) | no | | Ficha federativa, apto médico, DNI... |
| `validity_months` | SMALLINT UNSIGNED | sí | | Meses de validez; `NULL` = no vence |
| `requires_file` | BOOLEAN | no | default `false` | Pide un archivo (TIZ-29) |
| `minors_only` | BOOLEAN | no | default `false` | Solo a menores de 18, según `players.birth_date` |
| `is_required` | BOOLEAN | no | default `true` | Obligatorio para quedar habilitado |
| `sort` | SMALLINT UNSIGNED | no | default `0` | Orden en la grilla |
| `created_at`, `updated_at` | TIMESTAMP | sí | | |

- La API recibe la lista completa: con `id` edita, sin `id` crea y los que no vienen se borran.

## Cambio en `matches`

`competition_id` (nullable, FK → `competitions.id`, `ON DELETE SET NULL`): la competencia del partido.
