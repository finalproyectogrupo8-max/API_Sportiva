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

            // Si el rol o los deportes no existen en el club, ValidationException revierte también la membresía.
            $roles = $this->tenantDatabase->run($tenant, fn () => $this->roleSlugs(
                $this->members->ensure(
                    $membership->user,
                    $request->validated('roles'),
                    sportIds: $request->has('sport_ids') ? $request->validated('sport_ids') : null,
                )
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
            $losesAccess = $wasActive && array_key_exists('is_active', $validated) && ! $validated['is_active'];

            // Solo un miembro con acceso cuenta como administrador activo.
            if ($wasActive && $tenant->status === TenantStatus::Active->value && ($losesAccess || array_key_exists('roles', $validated))) {
                $this->tenantDatabase->run($tenant, fn () => $this->members->ensureAnotherAdminRemains(
                    $tenant,
                    $membership->user_id,
                    $validated['roles'] ?? null,
                    $losesAccess,
                ));
            }

            if (array_key_exists('roles', $validated) || array_key_exists('sport_ids', $validated)) {
                $rolesToSet = $validated['roles'] ?? null;
                $sportsToSet = array_key_exists('sport_ids', $validated) ? $validated['sport_ids'] : null;

                [$from, $to] = $this->tenantDatabase->run($tenant, function () use ($membership, $rolesToSet, $sportsToSet) {
                    $before = ClubUser::query()->where('global_user_id', $membership->user_id)->with('roles')->first();
                    $from = $before ? $this->roleSlugs($before) : [];

                    $after = $this->members->ensure(
                        $membership->user,
                        $rolesToSet,
                        reactivate: false,
                        sportIds: $sportsToSet,
                    );

                    return [$from, $this->roleSlugs($after)];
                });

                if (array_key_exists('roles', $validated)) {
                    $auditLogger->recordMembershipRoles($membership, $from, $to);
                }
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
        $rolesByUser = collect();
        $sportsByUser = collect();

        if ($tenant->status === TenantStatus::Active->value && $memberships->isNotEmpty()) {
            [$rolesByUser, $sportsByUser] = $this->tenantDatabase->run($tenant, function () use ($memberships) {
                $clubUsers = ClubUser::query()
                    ->whereIn('global_user_id', $memberships->pluck('user_id'))
                    ->with(['roles', 'sports'])
                    ->get();

                $roles = $clubUsers->mapWithKeys(fn (ClubUser $clubUser) => [$clubUser->global_user_id => $clubUser->roles
                    ->sortBy('name')
                    ->map(fn ($role) => ['slug' => $role->slug, 'name' => $role->name])
                    ->values()]);

                $sports = $clubUsers->mapWithKeys(fn (ClubUser $clubUser) => [$clubUser->global_user_id => $clubUser->sports
                    ->sortBy('name')
                    ->map(fn ($sport) => ['id' => (int) $sport->id, 'name' => (string) $sport->name])
                    ->values()]);

                return [$roles, $sports];
            });
        }

        foreach ($memberships as $membership) {
            $membership->setRelation('clubRoles', $rolesByUser->get($membership->user_id, collect()));
            $membership->setRelation('clubSports', $sportsByUser->get($membership->user_id, collect()));
        }
    }
}
