<?php

namespace App\Club\Access;

use App\Models\Club\ClubUser;
use App\Models\Club\Role;
use App\Models\Club\Sport;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\ApiErrorException;
use Illuminate\Validation\ValidationException;

/**
 * Altas del perfil local (club_user) y asignación de roles. Requiere la conexión "tenant"
 * apuntando al club (TenantDatabase::run).
 */
class ClubMembers
{
    /**
     * Crea el club_user del usuario (o lo reactiva, con $reactivate). Si vienen roles, reemplaza
     * los que tenía. Si vienen sportIds, sincroniza sus deportes.
     *
     * @param  list<string>|null  $roleSlugs
     * @param  list<int>|null  $sportIds
     */
    public function ensure(User $user, ?array $roleSlugs = null, bool $reactivate = true, ?array $sportIds = null): ClubUser
    {
        $roleIds = $roleSlugs === null ? null : $this->roleIds($roleSlugs);
        $validSportIds = $sportIds === null ? null : $this->validateSportIds($sportIds);

        $clubUser = ClubUser::query()->firstOrNew(['global_user_id' => $user->id]);
        $clubUser->display_name = $clubUser->exists ? $clubUser->display_name : $user->name;
        if (! $clubUser->exists || $reactivate) {
            $clubUser->status = ClubUser::STATUS_ACTIVE;
        }
        $clubUser->save();

        if ($roleIds !== null) {
            $this->syncRoles($clubUser, $roleIds);
        }

        if ($validSportIds !== null) {
            $clubUser->sports()->sync($validSportIds);
        }

        return $clubUser;
    }

    /**
     * Crea el club_user de las membresías activas que todavía no lo tienen, con el rol admin.
     * Son las membresías creadas antes de que exista la base del club (el admin inicial del alta)
     * y las de antes de TIZ-11, cuando el único rol de membresía era "admin".
     */
    public function backfillAdmins(Tenant $tenant): int
    {
        $existing = ClubUser::query()->pluck('global_user_id')->all();

        $memberships = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotIn('user_id', $existing)
            ->with('user')
            ->get();

        foreach ($memberships as $membership) {
            $this->ensure($membership->user, ['admin']);
        }

        return $memberships->count();
    }

    /**
     * Un club nunca se queda sin Administrador del club activo (docs/roles-y-permisos.md §6).
     * Falla con 409 last_admin si el usuario es admin activo, el cambio le saca el rol o el acceso,
     * y no queda otro admin activo (club_user activo con membresía central activa).
     *
     * @param  list<string>|null  $newRoleSlugs  null si no cambian los roles
     */
    public function ensureAnotherAdminRemains(Tenant $tenant, int $userId, ?array $newRoleSlugs, bool $losesAccess): void
    {
        $keepsAdmin = ! $losesAccess && ($newRoleSlugs === null || in_array('admin', $newRoleSlugs, true));
        if ($keepsAdmin) {
            return;
        }

        $adminUserIds = ClubUser::query()
            ->where('status', ClubUser::STATUS_ACTIVE)
            ->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))
            ->pluck('global_user_id')
            ->map(fn ($id) => (int) $id);

        if (! $adminUserIds->contains($userId)) {
            return;
        }

        $others = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereIn('user_id', $adminUserIds->reject(fn (int $id) => $id === $userId)->all())
            ->exists();

        if (! $others) {
            throw new ApiErrorException('last_admin', 409);
        }
    }

    /** @param list<int> $roleIds */
    private function syncRoles(ClubUser $clubUser, array $roleIds): void
    {
        // No se usa sync() con atributos: pisaría el created_at de los roles que ya tenía.
        $current = $clubUser->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->all();

        $clubUser->roles()->detach(array_diff($current, $roleIds));
        $clubUser->roles()->attach(array_fill_keys(array_diff($roleIds, $current), ['created_at' => now()]));
    }

    /**
     * @param  list<string>  $slugs
     * @return list<int>
     */
    private function roleIds(array $slugs): array
    {
        $slugs = array_values(array_unique($slugs));
        $roles = Role::query()->whereIn('slug', $slugs)->pluck('id', 'slug');

        $unknown = array_diff($slugs, $roles->keys()->all());
        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'roles' => 'Estos roles no existen en el club: '.implode(', ', $unknown).'.',
            ]);
        }

        if ($slugs === []) {
            throw ValidationException::withMessages(['roles' => 'Elegí al menos un rol.']);
        }

        return $roles->values()->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  list<int>  $sportIds
     * @return list<int>
     */
    public function validateSportIds(array $sportIds): array
    {
        $sportIds = array_values(array_unique(array_map('intval', $sportIds)));
        if ($sportIds === []) {
            return [];
        }

        $existing = Sport::query()->whereIn('id', $sportIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $unknown = array_diff($sportIds, $existing);

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'sport_ids' => 'Estos deportes no existen en el club: '.implode(', ', $unknown).'.',
            ]);
        }

        return $existing;
    }
}
