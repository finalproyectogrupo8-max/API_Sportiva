## 1. Deportes

- [x] 1.1 Modelo `Sport`, factory, requests, resource y policy.
- [x] 1.2 Controller y `apiResource`; filtros permitidos y paginación.
- [x] 1.3 Baja por desactivación.
- [ ] 1.4 Tests ABM/aislamiento.

## 2. Temporadas

- [x] 2.1 Modelo `Season`, factory, requests, resource y policy.
- [x] 2.2 Controller y `apiResource`; filtros permitidos y paginación.
- [x] 2.3 Borrado con manejo 409 si tiene categorías.
- [ ] 2.4 Tests ABM/aislamiento.

## 3. Categorías

- [x] 3.1 Modelo `Category`, factory, requests, resource y policy.
- [x] 3.2 Controller y `apiResource`; filtros por deporte/temporada y paginación.
- [x] 3.3 Baja por desactivación.
- [ ] 3.4 Tests ABM/aislamiento.

## 4. Profesores

- [x] 4.1 Modelo `Coach`, factory, requests, resource y policy.
- [x] 4.2 Controller y `apiResource`; filtros permitidos y paginación.
- [x] 4.3 Soft delete.
- [ ] 4.4 Tests ABM/aislamiento.

## 5. Integración y cierre

- [ ] 5.1 Integrar/verificar dependencias `tenant-connection`, `club-access-control` y migrations de las cuatro tablas en el entorno correspondiente.
- [ ] 5.2 Regenerar `docs/api/openapi.json`.
- [x] 5.3 `openspec validate club-catalog-crud --strict`.
- [ ] 5.4 Pint y suite PHPUnit.
