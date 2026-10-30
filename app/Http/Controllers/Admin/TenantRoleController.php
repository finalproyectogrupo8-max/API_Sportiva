<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ClubRoleResource;
use App\Models\Club\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Tenancy\TenantDatabase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TenantRoleController extends Controller
{
    /**
     * Roles del club, para asignarlos a sus miembros. Primero los estándar, después los custom.
     * Un club que todavía no está activo no tiene base: devuelve una lista vacía.
     */
    public function index(Tenant $tenant, TenantDatabase $tenantDatabase): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TenantMembership::class);

        $roles = $tenant->status === TenantStatus::Active->value
            ? $tenantDatabase->run($tenant, fn () => Role::query()
                ->withCount('clubUsers')
                ->orderByDesc('is_system')
                ->orderBy('id')
                ->get())
            : collect();

        return ClubRoleResource::collection($roles);
    }
}
