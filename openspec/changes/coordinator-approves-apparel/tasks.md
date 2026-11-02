# Tasks

## 1. Permiso y tests

- [ ] 1.1 Agregar `apparel_orders.approve` a los permisos del rol `coordinator` en `config/club-permissions.php`. En `tests/Unit/Club/PermissionCatalogTest.php`, cambiar la aserción: el coordinador **tiene** `apparel_orders.approve` con alcance `all`, `sport_coordinator` no lo tiene, y ningún rol estándar salvo `admin` tiene `material_orders.approve`. Verificar con `php artisan test --filter=PermissionCatalogTest`.
- [ ] 1.2 Agregar un test que compare la matriz de TIZ-30 con la configuración (competencias, requisitos, documentos, contactos y convocatorias por rol, con sus alcances). Si algún permiso no coincide con el ticket, frenar y avisar antes de cambiarlo, porque el resto de la matriz ya está en uso. Verificar que el test pase.
- [ ] 1.3 Agregar un test de feature: después de `SyncClubPermissions` en un club existente, un usuario con rol `coordinator` ve `apparel_orders.approve` en `GET /api/me` y uno con `sport_coordinator` no. Verificar con `php artisan test --filter=ClubPermissionsTest`.

## 2. Documentación

- [ ] 2.1 Actualizar `docs/roles-y-permisos.md`: en la fila `apparel_orders` de la matriz, el coordinador suma "aprobar"; la nota "Aprobar pedidos" dice que el coordinador aprueba indumentaria y que materiales sigue siendo solo del Administrador; la nota de `sport_coordinator` aclara que no recibe `apparel_orders.approve`. Verificar que la matriz coincida con `config/club-permissions.php`.

## 3. Verificación integrada

- [ ] 3.1 Ejecutar `docker compose exec backend php artisan test` y `docker compose exec backend vendor/bin/pint --test`. Verificar que todo pase y que `scramble:export` no genere cambios en `docs/api/openapi.json`.
- [ ] 3.2 En local, correr `docker compose exec backend php artisan tenants:migrate` y verificar con `GET /api/roles`, con un token de un club existente, que el rol "Coordinador deportivo" incluya `apparel_orders.approve`.

## Workflow follow-up

- Avisarle a Tiziano antes de mergear, porque TIZ-35 toca el mismo archivo.
- Pasar TIZ-30 a In Review en Linear cuando se abra el PR, y a Done cuando se mergee.
- Archivar el change con `/opsx:archive` después del merge.
