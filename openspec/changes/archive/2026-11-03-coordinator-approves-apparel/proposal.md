# Proposal

## Why

Los pedidos de indumentaria se traban porque solo los aprueba el Administrador del club, y la directiva entra poco al sistema. El coordinador es quien está en el día a día con las categorías, así que pasa a aprobarlos él (TIZ-30). Materiales, stock y reparaciones siguen siendo del Administrador.

## What Changes

- El rol estándar `coordinator` ("Coordinador deportivo") suma `apparel_orders.approve`, con alcance todo el club.
- `sport_coordinator` **no** lo recibe. `approve` no tiene alcance en el catálogo, así que dárselo le permitiría aprobar pedidos de deportes que no son suyos. Si hace falta, se define en TIZ-35, que es donde se trabaja el alcance "sus deportes".
- `material_orders.approve` sigue siendo solo del Administrador.
- `docs/roles-y-permisos.md`: la matriz y la nota "Aprobar pedidos" pasan a decir que el coordinador aprueba indumentaria. La nota de `sport_coordinator` ("exactamente los mismos permisos que el coordinador") suma esta excepción.
- Los clubes existentes reciben el permiso con `tenants:migrate`, que ya corre `SyncClubPermissions` y resincroniza los roles estándar. Los roles custom no cambian.

El resto de lo que pide el ticket ya existe en `config/club-permissions.php`: los permisos de competencias, requisitos, documentos, contactos y convocatorias, con los alcances de la tabla de TIZ-30. Entraron con TIZ-31, TIZ-32 y TIZ-33. Este change no los toca. Solo verifica, con un test, que la matriz coincida con la del ticket.

## Capabilities

### New Capabilities

Ninguna.

### Modified Capabilities

Ninguna. Las specs de acceso (`club-access-control`, que todavía no está archivada) definen cómo se evalúan los permisos y los roles, no qué permisos tiene cada rol estándar. Esa matriz vive en `config/club-permissions.php` y en `docs/roles-y-permisos.md`. Por eso el change usa `skip_specs: true`.

## Impact

- **Código:** `config/club-permissions.php` (una línea en `coordinator`).
- **Tests:** `tests/Unit/Club/PermissionCatalogTest.php` (hoy afirma que el coordinador **no** tiene `apparel_orders.approve`: pasa a afirmar que sí, que `sport_coordinator` no, y que nadie salvo el admin tiene `material_orders.approve`) y un test en `OrderApiTest` que aprueba pedidos reales de los dos tipos.
- **Docs:** `docs/roles-y-permisos.md`.
- **API:** sin endpoints nuevos ni cambios de contrato. `GET /api/me` y `GET /api/roles` devuelven el permiso nuevo para el coordinador. `openapi.json` no cambia.
- **Efecto real con la API de pedidos (TIZ-18, ya en `main`):** `POST /api/orders/{order}/approve` autoriza con `apparel_orders.approve` o `material_orders.approve` según el tipo del pedido (`OrderPolicy`). Con este cambio, el coordinador aprueba pedidos de indumentaria y recibe 403 en los de materiales.
- **Coordinación:** TIZ-35 (Tiziano) también toca `config/club-permissions.php`. El cambio es de una línea, así que se resuelve fácil si hay conflicto de merge.
