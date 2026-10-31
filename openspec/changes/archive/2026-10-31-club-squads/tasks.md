## 1. Jugadores

- [x] 1.1 Modelo `Player`, factory, requests, resource y scope `visibleTo`.
- [x] 1.2 `apiResource('players')` con filtros `search`, `category_id`, `status` y orden permitido.
- [x] 1.3 Baja lógica que cierra las asignaciones activas.
- [x] 1.4 Tests de los 10 casos de la guía ABM y del alcance del profe.

## 2. Contactos

- [x] 2.1 Migración `player_contacts`, modelo, request y resource.
- [x] 2.2 Endpoints anidados con un solo contacto principal.
- [x] 2.3 Permisos `player_contacts.view` y `.update` en el catálogo.
- [x] 2.4 Tests.

## 3. Plantel y profes por categoría

- [x] 3.1 Asignar, cambiar número y cerrar asignaciones de jugadores; pase con `close_previous`.
- [x] 3.2 Asignar y cerrar profes por categoría.
- [x] 3.3 Tests del pase de categoría, número de camiseta, alcance y aislamiento.

## 4. Cierre

- [x] 4.1 Documentación del modelo, roles y D15.
- [x] 4.2 Regenerar `docs/api/openapi.json`, Pint y PHPUnit.
