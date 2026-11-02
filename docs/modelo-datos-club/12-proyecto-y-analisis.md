# Proyecto y Análisis

[← Índice del modelo](README.md)

Datos de los módulos "Proyecto" (objetivos de la temporada y tabla de posiciones) y "Análisis" (indicadores por categoría y por jugador). TIZ-20. Las evaluaciones físicas están en [06-evaluaciones-fisicas.md](06-evaluaciones-fisicas.md).

## Decisiones

| Tema | Decisión | Por qué |
|---|---|---|
| Tabla de posiciones | Se **carga a mano**, por competencia y categoría (`competition_standings`). Se guarda entera: `PUT` reemplaza todas las filas. | El club no juega todos los partidos del torneo, así que no se puede calcular desde `matches`. |
| Objetivos | Tabla `season_objectives`: del club (sin categoría) o de una categoría, con meta y avance numéricos opcionales. | El avance (`progress`, de 0 a 100) lo calcula la API cuando hay meta. Un objetivo sin meta numérica se sigue solo por `status`. |
| Indicadores de Análisis | Se calculan en la API (`GET /analytics/...`), no en el front. Período `?from=&to=`, por defecto los últimos 90 días. | Evita traer toda la asistencia y los partidos al navegador. |
| Qué reemplaza a "eficacia, constancia y práctica" del mock | **Asistencia** (presentes + tardes sobre lo registrado), **resultados** (G/E/P, goles y los últimos 5) y **evaluaciones físicas** cargadas. | Los índices del mock no tenían una fuente de datos. Estos salen de lo que el club ya registra. |
| Permisos | Proyecto: `project.view` / `project.update`. Análisis: `physical_evaluations.view`, con su alcance (el profe ve solo sus categorías). | Usa el catálogo existente; no hay permisos nuevos. |

## `season_objectives`

| Columna | Tipo | Null | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK |
| `season_id` | FK `seasons` | no | |
| `category_id` | FK `categories` | sí | Sin categoría: objetivo del club |
| `title` | VARCHAR(150) | no | |
| `description` | TEXT | sí | |
| `target_value` / `current_value` | DECIMAL | sí | Meta y avance |
| `unit` | VARCHAR(30) | sí | Por ejemplo "partidos ganados" |
| `status` | VARCHAR(20) | no | `pending`, `in_progress` (por defecto), `achieved`, `missed` |
| `due_date` | DATE | sí | |
| timestamps, `deleted_at` | | | Soft delete |

## `competition_standings`

| Columna | Tipo | Null | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | no | PK |
| `competition_id` | FK `competitions` | no | Se borra con la competencia |
| `category_id` | FK `categories` | no | Tiene que jugar esa competencia |
| `position` | SMALLINT | no | `UNIQUE(competition_id, category_id, position)`. La pone la API según el orden de las filas |
| `team_name` | VARCHAR(120) | no | Sin repetir dentro de la tabla |
| `is_own` | BOOLEAN | no | La fila del club, para resaltarla |
| `played`, `won`, `drawn`, `lost`, `goals_for`, `goals_against`, `points` | SMALLINT | no | Por defecto 0 |

## Endpoints

| Método y ruta | Permiso | Notas |
|---|---|---|
| `apiResource('physical-evaluations')` | `physical_evaluations.*` | Mediciones en el mismo payload; en la edición las reemplazan. Filtros `player_id`, `category_id`, `metric_key`, `from`, `to`, `sort` |
| `apiResource('season-objectives')` | `project.view` / `project.update` | Por defecto, la temporada activa. Filtros `season_id`, `category_id` |
| `GET /competitions/{c}/standings` | `project.view` | Filtro `category_id` |
| `PUT /competitions/{c}/standings` | `project.update` | `{category_id, rows: [...]}` en orden |
| `GET /analytics/categories` | `physical_evaluations.view` | Una fila por categoría activa visible: jugadores, sesiones, asistencia, resultados y evaluaciones |
| `GET /analytics/categories/{category}/players` | `physical_evaluations.view` | Una fila por jugador: asistencia, detalle por estado, racha de faltas sin aviso y evaluaciones |
