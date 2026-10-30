<?php

namespace App\Http\Controllers\Admin;

use App\Club\Access\ClubMembers;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTenantMembershipRequest;
use App\Http\Requests\Admin\StoreTenantMembershipRequest;
use App\Http\Requests\Admin\UpdateTenantMembershipRequest;
use App\Http\Resources\Admin\TenantMembershipResource;
use App\Models\Club\ClubUser;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Services\AuditLogger;
use App\Tenancy\TenantDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TenantMembershipController extends Controller
{
    public function __construct(
        private TenantDatabase $tenantDatabase,
        private ClubMembers $members,
    ) {}

    /**
     * Miembros de un club, con el nombre y el email de cada usuario.
     */
    public function index(IndexTenantMembershipRequest $request, Tenant $tenant): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TenantMembership::class);

        $filters = $request->validated();

        $memberships = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->with('user')
            ->when(
                array_key_exists('is_active', $filters),
                fn ($query) => $query->where('is_active', $filters['is_active'])
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));

        $this->loadClubRoles($tenant, $memberships->getCollection());

        return TenantMembershipResource::collection($memberships);
    }

    public function store(
        StoreTenantMembershipRequest $request,
        Tenant $tenant,
        AuditLogger $auditLogger,
    ): JsonResponse {
        Gate::authorize('create', TenantMembership::class);

        $membership = DB::transaction(function () use ($request, $tenant, $auditLogger) {
            $membership = TenantMembership::create([
                'user_id' => $request->validated('user_id'),
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]);

            $auditLogger->recordCreated($membership);

            // Si el rol no existe en el club, ValidationException revierte también la membresía.
            $roles = $this->tenantDatabase->run($tenant, fn () => $this->roleSlugs(
                $this->members->ensure($membership->user, $request->validated('roles'))
            ));
            $auditLogger->recordMembershipRoles($membership, [], $roles);

            return $membership;
        });

        $this->loadClubRoles($tenant, collect([$membership]));

        return TenantMembershipResource::make($membership)->response()->setStatusCode(201);
    }

    public function update(
        UpdateTenantMembershipRequest $request,
        Tenant $tenant,
        TenantMembership $membership,
        AuditLogger $auditLogger,
    ): TenantMembershipResource {
        Gate::authorize('update', $membership);

        DB::transaction(function () use ($request, $tenant, $membership, $auditLogger) {
            $wasActive = $membership->is_active;
            $validated = $request->validated();

            if (array_key_exists('roles', $validated)) {
                [$from, $to] = $this->tenantDatabase->run($tenant, function () use ($membership, $validated) {
                    $before = ClubUser::query()->where('global_user_id', $membership->user_id)->with('roles')->first();
                    $from = $before ? $this->roleSlugs($before) : [];

                    return [$from, $this->roleSlugs($this->members->ensure($membership->user, $validated['roles'], reactivate: false))];
                });
                $auditLogger->recordMembershipRoles($membership, $from, $to);
            }

            if (array_key_exists('is_active', $validated)) {
                $membership->is_active = $validated['is_active'];
            }

            $auditLogger->recordUpdated($membership);
            $membership->save();

            if ($wasActive && ! $membership->is_active) {
                $membership->user->tokens()
                    ->where('tenant_id', $tenant->id)
                    ->delete();
            }
        });

        $this->loadClubRoles($tenant, collect([$membership]));

        return TenantMembershipResource::make($membership);
    }

    /** @return list<string> */
    private function roleSlugs(ClubUser $clubUser): array
    {
        return $clubUser->load('roles')->roles->pluck('slug')->sort()->values()->all();
    }

    /**
     * Agrega a cada membresía sus roles del club (relación "clubRoles"), con una sola conexión a
     * la base del club. Un club que todavía no está activo no tiene base: van vacíos.
     *
     * @param  Collection<int, TenantMembership>  $memberships
     */
    private function loadClubRoles(Tenant $tenant, Collection $memberships): void
    {
        $byUser = collect();

        if ($tenant->status === TenantStatus::Active->value && $memberships->isNotEmpty()) {
            $byUser = $this->tenantDatabase->run($tenant, fn () => ClubUser::query()
                ->whereIn('global_user_id', $memberships->pluck('user_id'))
                ->with('roles')
                ->get()
                ->mapWithKeys(fn (ClubUser $clubUser) => [$clubUser->global_user_id => $clubUser->roles
                    ->sortBy('name')
                    ->map(fn ($role) => ['slug' => $role->slug, 'name' => $role->name])
                    ->values()]));
        }

        foreach ($memberships as $membership) {
            $membership->setRelation('clubRoles', $byUser->get($membership->user_id, collect()));
        }
    }
}
