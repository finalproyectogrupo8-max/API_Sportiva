# Tasks

## 1. Permiso y tests

- [x] 1.1 Agregar `apparel_orders.approve` a los permisos del rol `coordinator` en `config/club-permissions.php`. En `tests/Unit/Club/PermissionCatalogTest.php`, cambiar la aserción: el coordinador **tiene** `apparel_orders.approve` con alcance `all`, `sport_coordinator` no lo tiene, y ningún rol estándar salvo `admin` tiene `material_orders.approve`. Verificar con `php artisan test --filter=PermissionCatalogTest`.
- [x] 1.2 Agregar a `tests/Feature/Club/OrderApiTest.php` un test con pedidos reales (la API de pedidos de TIZ-18 ya está en `main`): el coordinador aprueba un pedido de indumentaria enviado (200, `approved`) y recibe 403 en uno de materiales, que sigue en `submitted`. Verificar con `php artisan test --filter=OrderApiTest`.

## 2. Documentación

- [x] 2.1 Actualizar `docs/roles-y-permisos.md`: en la fila `apparel_orders` de la matriz, el coordinador suma "aprobar"; la nota "Aprobar pedidos" dice que el coordinador aprueba indumentaria y que materiales sigue siendo solo del Administrador; la nota de `sport_coordinator` aclara que no recibe `apparel_orders.approve`. Verificar que la matriz coincida con `config/club-permissions.php`.

## 3. Verificación

- [x] 3.1 Ejecutar `docker compose exec backend php artisan test --filter='PermissionCatalogTest|OrderApiTest|ClubPermissionsTest'` y `docker compose exec backend vendor/bin/pint --test`, y verificar que pasen. `openapi.json` no cambia: no hay rutas, Form Requests ni Resources nuevos.

## Workflow follow-up

- Avisarle a Tiziano antes de mergear, porque TIZ-35 y TIZ-18 tocan el mismo archivo.
- Pasar TIZ-30 a In Review en Linear cuando se abra el PR, y a Done cuando se mergee.
- Archivar el change con `/opsx:archive` después del merge.
