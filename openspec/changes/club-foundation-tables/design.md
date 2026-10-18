## Contexto

Los esquemas se toman de `docs/modelo-datos-club/02-deportes-y-planteles.md`. Son datos operativos aislados en la base de cada club; no llevan `tenant_id` ni `club_id`. Las migrations siguen el patrón Laravel existente: migration anónima con `up()` y `down()` y Schema Builder.

## Decisiones

- `sports`: `name` único, `is_active` con default `true`, timestamps. La desactivación es lógica por estado; no lleva `deleted_at`.
- `seasons`: `name`, `start_date`, `end_date`, `status` como `VARCHAR`; agrega `CHECK (end_date >= start_date)` y timestamps.
- `categories`: FK requeridas a `sports` y `seasons` con borrado restringido por defecto; edades opcionales, `is_active`, timestamps y unicidad `(sport_id, season_id, name)`.
- `coaches`: vínculo nullable y único `club_user_id` con FK a `club_users`; nombre y apellido requeridos, contacto opcional, `status` como `VARCHAR`, timestamps y `deleted_at`.
- `coaches` se ordena después de `club_users`, que aún no tiene migration. Su archivo lleva fecha posterior para conservar el orden de creación cuando se integre el change `club-access-control`.
- No se agregan soft deletes a `sports`, `seasons` ni `categories`: el esquema propuesto usa `is_active` en deportes/categorías y conserva temporadas por estado.
- Los estados se validan en la aplicación; no se usa `ENUM` de MySQL.

## Orden de migración

1. `sports` y `seasons` no dependen de otras tablas.
2. `categories` se crea después de `sports` y `seasons`.
3. `coaches` se crea después de `club_users`; ese requisito queda señalado en la tarea de integración.

## No incluido

Endpoints ABM, modelos, validación HTTP, permisos y asignaciones (`category_coaches`). Esos cambios tendrán sus propias specs antes de implementarse.
