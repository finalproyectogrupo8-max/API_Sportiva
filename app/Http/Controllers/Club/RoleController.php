<?php

namespace App\Http\Controllers\Club;

use App\Club\Access\PermissionCatalog;
use App\Club\Roles\ManageClubRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreRoleRequest;
use App\Http\Requests\Club\UpdateRoleRequest;
use App\Http\Resources\Admin\ClubRoleResource;
use App\Http\Resources\Club\PermissionModuleResource;
use App\Models\Club\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Roles del club actual (docs/roles-y-permisos.md §5 y §7). */
class RoleController extends Controller
{
    public function __construct(private ManageClubRoles $roles) {}

    /**
     * Catálogo de permisos agrupado por módulo, para armar la grilla de checks.
     */
    public function permissions(PermissionCatalog $catalog): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Role::class);

        return PermissionModuleResource::collection($catalog->grid());
    }

    /**
     * Roles del club con sus permisos: primero los estándar.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Role::class);

        return ClubRoleResource::collection(
            Role::query()->with('permissions')->withCount('clubUsers')->orderByDesc('is_system')->orderBy('id')->get()
        );
    }

    public function show(Role $role): ClubRoleResource
    {
        Gate::authorize('view', $role);

        return ClubRoleResource::make($role->load('permissions')->loadCount('clubUsers'));
    }

    /**
     * Crea un rol custom. Solo se pueden dar permisos (y alcances) que el usuario tiene.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        Gate::authorize('create', Role::class);

        $role = $this->roles->create($request->validated(), $request->user()->clubAccess());

        return ClubRoleResource::make($role->load('permissions')->loadCount('clubUsers'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Edita un rol custom. Los estándar responden 403 `role_is_system`.
     */
    public function update(UpdateRoleRequest $request, Role $role): ClubRoleResource
    {
        Gate::authorize('update', $role);

        $this->roles->update($role, $request->validated(), $request->user()->clubAccess());

        return ClubRoleResource::make($role->load('permissions')->loadCount('clubUsers'));
    }

    /**
     * Borra un rol custom. 403 `role_is_system` si es estándar; 409 `role_in_use` si alguien lo tiene.
     */
    public function destroy(Role $role): Response
    {
        Gate::authorize('delete', $role);

        $this->roles->delete($role);

        return response()->noContent();
    }

    /**
     * Copia un rol (estándar o custom) como rol custom nuevo.
     */
    public function duplicate(Request $request, Role $role): JsonResponse
    {
        Gate::authorize('create', Role::class);

        $copy = $this->roles->duplicate($role->load('permissions'), $request->user()->clubAccess());

        return ClubRoleResource::make($copy->load('permissions')->loadCount('clubUsers'))
            ->response()
            ->setStatusCode(201);
    }
}
