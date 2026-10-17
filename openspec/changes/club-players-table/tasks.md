## 1. Revisión del esquema

- [x] 1.1 Aprobar campos, nulabilidad, estados e índices de `players`.
- [ ] 1.2 Actualizar `docs/modelo-datos-club/02-deportes-y-planteles.md` si la revisión cambia el diseño.

## 2. Migration

- [x] 2.1 Crear la migration de `players` bajo `database/migrations/tenant/` sin modificar migrations existentes.
- [x] 2.2 Revisar que `down()` elimine solo `players` y que la migration no agregue columnas de tenant.
- [ ] 2.3 Aplicar/validar la migration cuando el soporte de conexión y migraciones de club esté disponible.

## 3. Cierre

- [ ] 3.1 Validar el change con `openspec validate club-players-table --strict`.
- [ ] 3.2 Proponer el ABM de jugadores en un change separado antes de implementar endpoints.
