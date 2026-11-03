## Contexto

La decisión funcional está en [docs/roles-y-permisos.md](../../../docs/roles-y-permisos.md). Acá van las decisiones de implementación.

## Decisiones

**El catálogo vive en config, no en una tabla editable.** `config/club-permissions.php` define módulos → recursos → acciones, qué acciones admiten alcance `own` y la matriz de los roles estándar (`"*"` para el admin, `"<permiso>:own"` para el alcance). `PermissionCatalog` lo resuelve y falla con `LogicException` si un rol usa un permiso inexistente o un `own` no permitido: un error de configuración rompe el deploy y los tests, no un request.

**Sincronización idempotente por club.** `SyncClubPermissions` hace upsert de `permissions` por `name`, borra los que salieron del catálogo (sus asignaciones caen por `CASCADE`), y hace `updateOrCreate` de los roles estándar por `slug` con `sync()` de sus permisos. No toca los roles custom. Corre dentro de una transacción de la conexión `tenant`.

**Permisos por request, desde la base del club.** `User::clubAccess()` arma un `ClubAccess` (club_user, roles y mapa `permiso => all|own`) y lo guarda en los atributos del request. No se cachea entre requests: un cambio de roles aplica en el siguiente. Si dos roles dan el mismo permiso, gana `all`. Un `club_user` deshabilitado no tiene permisos y el middleware lo rechaza con 403 `club_user_disabled`.

**Membresías sin rol central.** El acceso sigue siendo `tenant_memberships.is_active`; lo que el usuario puede hacer sale de `club_user_roles`. Al crear una membresía desde `/admin`, en la misma transacción central se crea (o reactiva) el `club_user` con los roles pedidos. Si un rol no existe en el club, `ValidationException` revierte la membresía. Solo se permite en clubes activos, porque antes no existe la base.

**Admin inicial y membresías anteriores.** El admin que se crea con el club tiene membresía antes de que exista la base. `ProvisionTenant`, después de migrar, sincroniza permisos y le da `admin` a toda membresía activa sin `club_user` (`ClubMembers::backfillAdmins`). Lo mismo cubre las membresías anteriores a este change, cuyo único rol posible era `admin`.

**Alcance `own` en profes.** `Coach::scopeVisibleTo()` filtra por la ficha propia (`coaches.club_user_id`) y los profes con asignación vigente en las categorías del usuario. Un profe fuera de su alcance responde 404, no 403.

## Riesgos

- Una request de club hace una consulta más (club_user, roles y permisos con eager loading). Es una consulta por request, en la base del club.
- `tenants:migrate` ahora falla si la sincronización falla en algún club, y sigue con los demás.
