## Contexto

El modelo propuesto está en `docs/modelo-datos-club/02-deportes-y-planteles.md`. La base operativa es una base por club, sin `tenant_id` ni `club_id` en sus tablas. Las convenciones generales de migrations y bajas están en `docs/guia-bases-de-datos.md` y `docs/guia-abm.md`.

## Esquema propuesto

Tabla `players`, ubicada en `database/migrations/tenant/` cuando exista esa carpeta:

| Campo | Tipo Laravel propuesto | Null | Decisión |
|---|---|---:|---|
| `id` | `$table->id()` | no | PK estándar |
| `first_name` | `$table->string('first_name', 100)` | no | Nombre |
| `last_name` | `$table->string('last_name', 100)` | no | Apellido |
| `birth_date` | `$table->date('birth_date')` | sí | Puede faltar al registrar; no se usa para calcular edad persistida |
| `document_number` | `$table->string('document_number', 30)` | sí | Identificador de la ficha técnica |
| `registration_number` | `$table->string('registration_number', 50)` | sí | Ficha/pase federativo, si aplica |
| `preferred_position` | `$table->string('preferred_position', 50)` | sí | Texto extensible; evitar ENUM MySQL |
| `dominant_side` | `$table->string('dominant_side', 20)` | sí | Texto extensible; evitar ENUM MySQL |
| `status` | `$table->string('status', 20)->default('active')` | no | Valores de aplicación: `active`, `inactive`, `transferred` |
| `notes` | `$table->text('notes')` | sí | Solo observaciones no sensibles; sin información médica |
| `created_at`, `updated_at` | `$table->timestamps()` | sí | Convención Laravel |
| `deleted_at` | `$table->softDeletes()` | sí | Baja lógica para conservar referencias e historial |

Índice propuesto: `(last_name, first_name)` para listados y búsquedas por nombre. No se agrega índice único a `document_number` ni `registration_number`: su alcance, formato y reutilización no están definidos.

## Decisiones

- Incluir `document_number` nullable porque forma parte de la ficha técnica del jugador; no imponer unicidad hasta definir su alcance y reglas de reutilización.
- No guardar `category_id` en `players`: las asignaciones tienen historial y van en `player_category_memberships`.
- No agregar `tenant_id`/`club_id`: el aislamiento lo proporciona la base de datos del club.
- No agregar edad, equipo, estadísticas, datos de salud, tutores, dirección, teléfono ni email personal: no son necesarios para el registro inicial y no están definidos en el esquema aprobado.
- Los estados se guardan como `VARCHAR` y se validan en la aplicación; no usar enum de MySQL.
- Mantener los tipos y el formato de migration que ya usa Laravel en las migrations existentes (`Migration` anónima, `up()`/`down()`, `Schema::create`, `Blueprint`).

## Preguntas para revisión

- Aprobado por el usuario: campos opcionales, `document_number` incluido, estado `active | inactive | transferred` e índice por apellido/nombre sin unicidad de los identificadores.
