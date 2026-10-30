<?php

namespace App\Http\Controllers\Admin;

use App\Club\Access\PermissionCatalog;
use App\Club\Roles\ManageClubRoles;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreRoleRequest;
use App\Http\Requests\Club\UpdateRoleRequest;
use App\Http\Resources\Admin\ClubRoleResource;
use App\Http\Resources\Club\PermissionModuleResource;
use App\Models\Club\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Support\ApiError;
use App\Tenancy\TenantDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Roles de un club desde el panel de plataforma. Mismo contrato que /api/roles, sin el límite de
 * "solo dar permisos que tenés": el admin de plataforma prepara el club.
 */
class TenantRoleController extends Controller
{
    public function __construct(
        private TenantDatabase $tenantDatabase,
        private ManageClubRoles $roles,
    ) {}

    /**
     * Catálogo de permisos agrupado por módulo, para armar la grilla de checks.
     */
    public function permissions(Tenant $tenant, PermissionCatalog $catalog): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TenantMembership::class);

        return PermissionModuleResource::collection($catalog->grid());
    }

    /**
     * Roles del club con sus permisos: primero los estándar. Un club que todavía no está activo no
     * tiene base: devuelve una lista vacía.
     */
    public function index(Tenant $tenant): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TenantMembership::class);

        $roles = $tenant->status === TenantStatus::Active->value
            ? $this->tenantDatabase->run($tenant, fn () => Role::query()
                ->with('permissions')
                ->withCount('clubUsers')
                ->orderByDesc('is_system')
                ->orderBy('id')
                ->get())
            : collect();

        return ClubRoleResource::collection($roles);
    }

    public function show(Tenant $tenant, string $role): ClubRoleResource|JsonResponse
    {
        Gate::authorize('viewAny', TenantMembership::class);

        if ($inactive = $this->inactive($tenant)) {
            return $inactive;
        }

        return ClubRoleResource::make($this->tenantDatabase->run($tenant, fn () => $this->roles->find($role)));
    }

    public function store(StoreRoleRequest $request, Tenant $tenant): JsonResponse
    {
        Gate::authorize('create', TenantMembership::class);

        if ($inactive = $this->inactive($tenant)) {
            return $inactive;
        }

        $role = $this->tenantDatabase->run($tenant, fn () => $this->roles->find(
            $this->roles->create($request->validated())->slug
        ));

        return ClubRoleResource::make($role)->response()->setStatusCode(201);
    }

    public function update(UpdateRoleRequest $request, Tenant $tenant, string $role): ClubRoleResource|JsonResponse
    {
        Gate::authorize('create', TenantMembership::class);

        if ($inactive = $this->inactive($tenant)) {
            return $inactive;
        }

        return ClubRoleResource::make($this->tenantDatabase->run($tenant, function () use ($request, $role) {
            $model = $this->roles->find($role);
            $this->roles->update($model, $request->validated());

            return $this->roles->find($model->slug);
        }));
    }

    public function destroy(Tenant $tenant, string $role): Response|JsonResponse
    {
        Gate::authorize('create', TenantMembership::class);

        if ($inactive = $this->inactive($tenant)) {
            return $inactive;
        }

        $this->tenantDatabase->run($tenant, fn () => $this->roles->delete($this->roles->find($role)));

        return response()->noContent();
    }

    public function duplicate(Tenant $tenant, string $role): JsonResponse
    {
        Gate::authorize('create', TenantMembership::class);

        if ($inactive = $this->inactive($tenant)) {
            return $inactive;
        }

        $copy = $this->tenantDatabase->run($tenant, fn () => $this->roles->find(
            $this->roles->duplicate($this->roles->find($role))->slug
        ));

        return ClubRoleResource::make($copy)->response()->setStatusCode(201);
    }

    /** Sin base todavía (alta en curso o fallida): no hay roles que tocar. */
    private function inactive(Tenant $tenant): ?JsonResponse
    {
        return $tenant->status === TenantStatus::Active->value ? null : ApiError::conflict('tenant_inactive');
    }
}
