## 1. Migrations de club

- [x] 1.1 Crear `sports` con nombre único, estado activo e timestamps.
- [x] 1.2 Crear `seasons` con fechas, estado, timestamps y restricción del rango de fechas.
- [x] 1.3 Crear `categories` con FK a deporte/temporada, edades opcionales e índice único compuesto.
- [x] 1.4 Crear `coaches` con datos de contacto, baja lógica y vínculo opcional a `club_users`.
- [ ] 1.5 Al integrar `club-access-control`, agregar en una migration posterior a `club_users` la FK `coaches.club_user_id` → `club_users.id` (`tenant-connection` la sacó de la migration de `coaches`, porque sin `club_users` ninguna base de club se podía migrar).

## 2. Especificaciones y validación

- [x] 2.1 Registrar una spec por tabla con columnas, relaciones y escenarios.
- [x] 2.2 Ejecutar `openspec validate club-foundation-tables --strict`.
- [ ] 2.3 Aplicar y verificar las migrations en una base de club cuando exista la infraestructura tenant.

## Fuera de alcance

- ABM HTTP y sus tests; se documentarán en un change posterior.
