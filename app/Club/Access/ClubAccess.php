<?php

namespace App\Club\Access;

use App\Models\Club\ClubUser;
use App\Models\Club\Permission;
use Illuminate\Support\Facades\DB;

/**
 * Lo que el usuario puede hacer en el club del request: su club_user, sus roles y sus permisos
 * (con el alcance más amplio si dos roles dan el mismo). Se arma una vez por request (ver
 * User::clubAccess()) y siempre se lee de la base del club, nunca del token ni del cliente.
 */
class ClubAccess
{
    /** @var list<int>|null */
    private ?array $ownCategoryIds = null;

    /**
     * @param  array<string, string>  $permissions  nombre => all | own
     */
    public function __construct(
        public readonly ?ClubUser $clubUser,
        private readonly array $permissions,
    ) {}

    public static function none(): self
    {
        return new self(null, []);
    }

    /** Requiere la conexión "tenant" apuntando al club. */
    public static function forGlobalUser(int $userId): self
    {
        $clubUser = ClubUser::query()
            ->where('global_user_id', $userId)
            ->with('roles.permissions')
            ->first();

        if (! $clubUser || ! $clubUser->isActive()) {
            return new self($clubUser, []);
        }

        $permissions = [];

        foreach ($clubUser->roles as $role) {
            foreach ($role->permissions as $permission) {
                $scope = $permission->pivot->scope;
                $current = $permissions[$permission->name] ?? null;

                // Varios roles: gana el alcance más amplio.
                if ($current !== Permission::SCOPE_ALL) {
                    $permissions[$permission->name] = $scope;
                }
            }
        }

        ksort($permissions);

        return new self($clubUser, $permissions);
    }

    public function has(string $permission): bool
    {
        return isset($this->permissions[$permission]);
    }

    /** all, own o null si no tiene el permiso. */
    public function scope(string $permission): ?string
    {
        return $this->permissions[$permission] ?? null;
    }

    public function isOwnScope(string $permission): bool
    {
        return $this->scope($permission) === Permission::SCOPE_OWN;
    }

    /** @return list<string> */
    public function permissionNames(): array
    {
        return array_keys($this->permissions);
    }

    /** @return array<string, string> */
    public function scopes(): array
    {
        return $this->permissions;
    }

    /**
     * "Sus categorías": donde el usuario tiene una asignación vigente como entrenador.
     *
     * @return list<int>
     */
    public function ownCategoryIds(): array
    {
        if ($this->ownCategoryIds !== null) {
            return $this->ownCategoryIds;
        }

        if (! $this->clubUser) {
            return $this->ownCategoryIds = [];
        }

        return $this->ownCategoryIds = DB::connection('tenant')
            ->table('category_coaches')
            ->join('coaches', 'coaches.id', '=', 'category_coaches.coach_id')
            ->where('coaches.club_user_id', $this->clubUser->id)
            ->whereNull('coaches.deleted_at')
            ->where(fn ($query) => $query
                ->whereNull('category_coaches.end_date')
                ->orWhere('category_coaches.end_date', '>=', today()->toDateString()))
            ->distinct()
            ->pluck('category_coaches.category_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
