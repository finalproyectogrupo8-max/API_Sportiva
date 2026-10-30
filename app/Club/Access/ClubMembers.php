<?php

namespace App\Club\Access;

use App\Models\Club\ClubUser;
use App\Models\Club\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Altas del perfil local (club_user) y asignación de roles. Requiere la conexión "tenant"
 * apuntando al club (TenantDatabase::run).
 */
class ClubMembers
{
    /**
     * Crea el club_user del usuario (o lo reactiva, con $reactivate). Si vienen roles, reemplaza
     * los que tenía.
     *
     * @param  list<string>|null  $roleSlugs
     */
    public function ensure(User $user, ?array $roleSlugs = null, bool $reactivate = true): ClubUser
    {
        $roleIds = $roleSlugs === null ? null : $this->roleIds($roleSlugs);

        $clubUser = ClubUser::query()->firstOrNew(['global_user_id' => $user->id]);
        $clubUser->display_name = $clubUser->exists ? $clubUser->display_name : $user->name;
        if (! $clubUser->exists || $reactivate) {
            $clubUser->status = ClubUser::STATUS_ACTIVE;
        }
        $clubUser->save();

        if ($roleIds !== null) {
            $this->syncRoles($clubUser, $roleIds);
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
}
