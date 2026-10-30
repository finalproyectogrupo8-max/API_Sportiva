## Decisiones

**Un servicio para los dos caminos.** `App\Club\Roles\ManageClubRoles` crea, edita, borra y duplica roles sobre la conexión `tenant` ya apuntada al club.
- `RoleController` (club) lo llama con el `ClubAccess` del usuario como actor.
- `TenantRoleController` (admin) lo llama dentro de `TenantDatabase::run()` y sin actor.

**Sin escalada.** Con actor, cada permiso pedido tiene que estar en los permisos del actor. Si el actor lo tiene con alcance `own`, solo puede darlo con `own`. Duplicar pasa por la misma validación: un coordinador no puede duplicar el rol `admin`. El admin de plataforma no tiene este límite, porque prepara el club.

**Slug estable.** Se genera del nombre al crear (`profe_con_indumentaria`, con `_2`, `_3` si se repite) y no cambia al renombrar. Las rutas usan el slug. El nombre es único por club.

**Errores de negocio con `code`.** `ApiErrorException(code, status)` se renderiza con el formato de `ApiError`. Los usa el servicio para `role_is_system` (403), `role_in_use` (409) y `last_admin` (409).

**Último administrador.** `ClubMembers::ensureAnotherAdminRemains()` corre antes de cambiar roles o desactivar una membresía activa en un club activo.
- Si el usuario es admin activo y el cambio le saca el rol o el acceso, tiene que quedar otro.
- "Otro admin" es un `club_user` activo con rol `admin` y membresía central activa.
