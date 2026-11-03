<?php

namespace App\Http\Controllers\Club;

use App\Club\Access\ClubMembers;
use App\Club\Audit\ClubAudit;
use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreClubUserRequest;
use App\Http\Requests\Club\UpdateClubUserRequest;
use App\Models\Club\ClubUser;
use App\Models\Club\Permission;
use App\Models\Club\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\AuditLogger;
use App\Tenancy\TenantDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Equipo del club (TIZ-34): el administrador suma gente por email, les cambia los roles o les quita
 * el acceso, sin pasar por el panel de plataforma. Nadie asigna roles con más permisos que los suyos.
 */
class ClubUserController extends Controller
{
    use ChecksClubScope;

    private const WEIGHTS = [Permission::SCOPE_ALL => 3, Permission::SCOPE_SPORT => 2, Permission::SCOPE_OWN => 1];

    public function __construct(private ClubMembers $members) {}

    /**
     * Sin parámetros: personas activas, solo id y nombre (para asignar reparaciones o comunicados).
     * Con `?team=1` y `club_users.view`: el equipo completo con email, roles, deportes y estado.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->currentClubUser(), 403);

        if (! $request->boolean('team')) {
            $users = ClubUser::query()->where('status', 'active')->orderBy('display_name')->get(['id', 'display_name']);

            return response()->json(['data' => $users->map(fn (ClubUser $user) => ['id' => $user->id, 'display_name' => $user->display_name])->values()]);
        }

        $this->authorizeClub('club_users.view');
        $users = ClubUser::query()->with(['roles', 'sports'])->orderBy('display_name')->get();

        return response()->json(['data' => $this->present($users, $this->tenant($request))]);
    }

    /**
     * Suma a alguien al equipo. Si no tiene usuario en Sportiva, se crea y le llega un mail para elegir
     * su contraseña. Si ya estaba y se le había quitado el acceso, se reactiva con los roles nuevos.
     */
    public function store(StoreClubUserRequest $request, TenantDatabase $tenantDatabase, AuditLogger $audit): JsonResponse
    {
        $this->authorizeClub('club_users.invite');
        $data = $request->validated();
        $tenant = $this->tenant($request);
        $this->ensureCanAssign($data['roles']);

        $email = mb_strtolower(trim($data['email']));
        $user = User::query()->where('email', $email)->first();
        if (! $user && blank($data['name'] ?? null)) {
            throw ValidationException::withMessages(['name' => 'No tiene usuario en Sportiva: escribí su nombre para crearlo.']);
        }
        if ($user && ClubUser::query()->where('global_user_id', $user->id)->where('status', ClubUser::STATUS_ACTIVE)->exists()
            && TenantMembership::query()->where('tenant_id', $tenant->id)->where('user_id', $user->id)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['email' => 'Ya es parte del equipo: cambiale los roles desde la lista.']);
        }

        $created = false;
        // La base central y la del club en una sola operación: si falla la del club, se revierte la central.
        $clubUser = DB::transaction(function () use ($data, $email, &$user, &$created, $tenant, $audit) {
            if (! $user) {
                $user = new User;
                $user->forceFill([
                    'name' => trim($data['name']),
                    'email' => $email,
                    'password' => Hash::make(Str::random(48)),
                    'is_active' => true,
                ])->save();
                $created = true;
            }

            $membership = TenantMembership::query()->firstOrNew(['tenant_id' => $tenant->id, 'user_id' => $user->id]);
            $membership->is_active = true;
            $membership->save();

            return DB::connection('tenant')->transaction(function () use ($user, $data, $membership, $audit) {
                $clubUser = $this->members->ensure($user, $data['roles'], sportIds: $data['sport_ids'] ?? null);
                $audit->recordMembershipRoles($membership, [], $clubUser->roles()->pluck('slug')->all());
                app(ClubAudit::class)->record('club_user.added', 'club_user', $clubUser->id, ['name' => $clubUser->display_name, 'roles' => ['to' => $clubUser->roles()->pluck('name')->all()]]);

                return $clubUser;
            });
        });

        // La invitación es el mail para elegir contraseña (TIZ-23 lo reemplaza por uno propio).
        if ($created) {
            $user->sendPasswordResetNotification(Password::broker()->createToken($user));
        }

        return response()->json(['data' => $this->present(collect([$clubUser->load(['roles', 'sports'])]), $tenant)[0], 'meta' => ['invited' => $created]], 201);
    }

    public function update(UpdateClubUserRequest $request, ClubUser $clubUser, AuditLogger $audit): JsonResponse
    {
        $data = $request->validated();
        $tenant = $this->tenant($request);
        $this->authorizeClub(($data['status'] ?? null) === ClubUser::STATUS_DISABLED ? 'club_users.disable' : 'club_users.update');

        // No se toca a alguien con más permisos que uno, ni se le dan roles de más.
        $this->ensureCanAssign($clubUser->roles()->pluck('slug')->all());
        if (isset($data['roles'])) {
            $this->ensureCanAssign($data['roles']);
        }

        $losesAccess = ($data['status'] ?? null) === ClubUser::STATUS_DISABLED && $clubUser->status === ClubUser::STATUS_ACTIVE;
        $this->members->ensureAnotherAdminRemains($tenant, (int) $clubUser->global_user_id, $data['roles'] ?? null, $losesAccess);

        $membership = TenantMembership::query()->where('tenant_id', $tenant->id)->where('user_id', $clubUser->global_user_id)->first();
        DB::transaction(function () use ($data, $clubUser, $membership, $audit) {
            if (isset($data['status'])) {
                $membership?->forceFill(['is_active' => $data['status'] === ClubUser::STATUS_ACTIVE])->save();
            }

            DB::connection('tenant')->transaction(function () use ($data, $clubUser, $membership, $audit) {
                $before = $clubUser->roles()->pluck('slug')->all();
                if (isset($data['status'])) {
                    $clubUser->update(['status' => $data['status']]);
                }
                if (isset($data['roles']) || array_key_exists('sport_ids', $data)) {
                    $user = User::findOrFail($clubUser->global_user_id);
                    $this->members->ensure($user, $data['roles'] ?? null, reactivate: false, sportIds: $data['sport_ids'] ?? null);
                }
                if ($membership && isset($data['roles'])) {
                    $audit->recordMembershipRoles($membership, $before, $clubUser->roles()->pluck('slug')->all());
                }
                if (isset($data['roles'])) {
                    $beforeNames = Role::query()->whereIn('slug', $before)->pluck('name')->all();
                    app(ClubAudit::class)->record('club_user.roles_updated', 'club_user', $clubUser->id, ['name' => $clubUser->display_name, 'roles' => ['from' => $beforeNames, 'to' => $clubUser->roles()->pluck('name')->all()]]);
                }
                if (isset($data['status'])) {
                    app(ClubAudit::class)->record($data['status'] === ClubUser::STATUS_ACTIVE ? 'club_user.enabled' : 'club_user.disabled', 'club_user', $clubUser->id, ['name' => $clubUser->display_name]);
                }
            });
        });

        return response()->json(['data' => $this->present(collect([$clubUser->refresh()->load(['roles', 'sports'])]), $tenant)[0]]);
    }

    /**
     * Cada permiso de los roles tiene que estar entre los del usuario actual, con el mismo alcance o
     * uno más amplio (all > sport > own). Si no, 403.
     *
     * @param  list<string>  $slugs
     */
    private function ensureCanAssign(array $slugs): void
    {
        $access = $this->access();
        foreach (Role::query()->whereIn('slug', $slugs)->with('permissions')->get() as $role) {
            foreach ($role->permissions as $permission) {
                $mine = $access->scope($permission->name);
                if ($mine === null || (self::WEIGHTS[$mine] ?? 0) < (self::WEIGHTS[$permission->pivot->scope] ?? 0)) {
                    abort(403, "No podés asignar el rol {$role->name}: tiene permisos que vos no tenés.");
                }
            }
        }
    }

    /**
     * @param  Collection<int, ClubUser>  $users
     * @return list<array<string, mixed>>
     */
    private function present($users, Tenant $tenant): array
    {
        $globalIds = $users->pluck('global_user_id')->all();
        $emails = User::query()->whereKey($globalIds)->pluck('email', 'id');
        $access = TenantMembership::query()->where('tenant_id', $tenant->id)->whereIn('user_id', $globalIds)->pluck('is_active', 'user_id');
        $me = request()->user()->id;

        return $users->map(fn (ClubUser $user) => [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'email' => $emails->get($user->global_user_id),
            /** `active` o `disabled` (sin acceso al club). */
            'status' => $user->isActive() && ($access->get($user->global_user_id) ?? false) ? ClubUser::STATUS_ACTIVE : ClubUser::STATUS_DISABLED,
            'roles' => $user->roles->map(fn (Role $role) => ['slug' => $role->slug, 'name' => $role->name])->values(),
            'sport_ids' => $user->sports->pluck('id')->values(),
            'is_self' => (int) $user->global_user_id === (int) $me,
            'created_at' => $user->created_at?->toIso8601String(),
        ])->values()->all();
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }
}
