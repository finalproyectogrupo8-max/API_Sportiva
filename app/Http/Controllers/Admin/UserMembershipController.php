<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTenantMembershipRequest;
use App\Http\Resources\Admin\TenantMembershipResource;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UserMembershipController extends Controller
{
    /**
     * Clubes de un usuario: sus membresías, con el club en cada una. No incluye clubes dados de baja.
     */
    public function index(IndexTenantMembershipRequest $request, User $user): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TenantMembership::class);

        $filters = $request->validated();

        $memberships = TenantMembership::query()
            ->where('user_id', $user->id)
            ->whereHas('tenant')
            ->with('tenant')
            ->when(
                array_key_exists('is_active', $filters),
                fn ($query) => $query->where('is_active', $filters['is_active'])
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));

        return TenantMembershipResource::collection($memberships);
    }
}
