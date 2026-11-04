# Tasks

## 1. Roles y autorización del club

- [x] 1.1 Retirar `coordinator` de los roles estándar y dejar a `sport_coordinator` únicamente `seasons.view`, `categories.view:sport` y `competitions.view:sport`; actualizar tests del catálogo y la matriz documentada en `docs/roles-y-permisos.md`, verificando los permisos exactos devueltos por `/api/me`.
- [x] 1.2 Actualizar la sincronización tenant para eliminar el rol estándar `coordinator` y sus asignaciones sin reasignación automática; agregar pruebas de club existente, roles adicionales preservados, sincronización idempotente y rechazo de nuevas asignaciones al slug retirado.
- [x] 1.3 Actualizar políticas y pruebas para confirmar que `sport_coordinator` puede leer únicamente temporadas, categorías y competencias en alcance, no puede mutarlas ni operar recursos de otros módulos, y conserva `permission_scopes` con alcance `sport`; verificar respuestas 403/404.
- [x] 1.4 Actualizar destinatarios de alertas para que las generales lleguen al Administrador y las de categoría también al `sport_coordinator` asignado al deporte; actualizar pruebas y ejemplos de roles en documentación, verificando no duplicación y aislamiento por deporte.

## 2. Catálogo de posiciones por deporte

- [x] 2.1 Crear migraciones tenant para `positions` y `player_positions`, con unicidad del nombre dentro del deporte e integridad referencial en la base del club; actualizar el modelo de datos y verificar migración y rollback en MySQL de testing.
- [x] 2.2 Implementar catálogo paginado y filtrable por `sport_id`, y ABM protegido por `positions.*` con alta, edición y desactivación; cubrir permisos, unicidad, aislamiento entre clubes, deportes inválidos y posiciones inactivas con pruebas feature.
- [x] 2.3 Agregar los permisos `positions.*` al catálogo y otorgarlos al rol estándar `admin` sin incluirlos en los demás roles estándar; verificar con tests de `PermissionCatalog` y documentar el catálogo y su administración.

## 3. Posiciones en jugadores

- [x] 3.1 Integrar las relaciones de posiciones a los requests, modelos y resources de jugadores; aceptar `position_ids` en altas y ediciones, reemplazar al enviarlo y preservar al omitirlo; verificar varias posiciones por deporte y entre deportes, validación de IDs, y compatibilidad con `preferred_position`.
- [x] 3.2 Actualizar la documentación de jugadores y el contrato generado `docs/api/openapi.json`; regenerar OpenAPI y verificar que documenta la lista `position_ids` y las posiciones/devuelve su deporte en el resource.

## 4. Frontend y regresión de membresías

- [ ] 4.1 En el repositorio `Front-Sportiva`, mostrar únicamente Temporadas, Categorías y Competencias para `sport_coordinator`, y agregar selección de múltiples posiciones filtradas por deporte en el alta/edición de jugadores; verificar pruebas del menú y formulario y el build del frontend.
- [x] 4.2 Verificar que un mismo usuario conserva membresías activas en dos clubes y que desactivar una membresía no invalida el acceso al otro club; agregar pruebas de regresión solo si falta cobertura y ejecutarlas contra MySQL.
- [x] 4.3 Ejecutar pruebas backend focalizadas, Pint y `openspec validate club-role-sport-positions --strict`; corregir cualquier desactualización del contrato o escenario antes de archivar el change.
