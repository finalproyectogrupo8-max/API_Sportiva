<?php

namespace App\Club\Access;

use App\Models\Club\ClubUser;
use App\Models\Club\Permission;
use Illuminate\Support\Facades\DB;

/**
 * Lo que el usuario puede hacer en el club del request: su club_user, sus roles y sus permisos
 * (con el alcance más amplio si dos roles dan el mismo: all > sport > own). Se arma una vez por request (ver
 * User::clubAccess()) y siempre se lee de la base del club, nunca del token ni del cliente.
 */
class ClubAccess
{
    /** @var list<int>|null */
    private ?array $ownCategoryIds = null;

    /** @var list<int>|null */
    private ?array $sportIds = null;

    /** @var list<int>|null */
    private ?array $sportCategoryIds = null;

    /**
     * @param  array<string, string>  $permissions  nombre => all | sport | own
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
            ->with(['roles.permissions', 'sports'])
            ->first();

        if (! $clubUser || ! $clubUser->isActive()) {
            return new self($clubUser, []);
        }

        $weights = [
            Permission::SCOPE_ALL => 3,
            Permission::SCOPE_SPORT => 2,
            Permission::SCOPE_OWN => 1,
        ];

        $permissions = [];

        foreach ($clubUser->roles as $role) {
            foreach ($role->permissions as $permission) {
                $scope = $permission->pivot->scope;
                $current = $permissions[$permission->name] ?? null;

                // Varios roles: gana el alcance más amplio (all > sport > own).
                if (($weights[$scope] ?? 0) > ($weights[$current] ?? 0)) {
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

    /** all, sport, own o null si no tiene el permiso. */
    public function scope(string $permission): ?string
    {
        return $this->permissions[$permission] ?? null;
    }

    public function isOwnScope(string $permission): bool
    {
        return $this->scope($permission) === Permission::SCOPE_OWN;
    }

    public function isSportScope(string $permission): bool
    {
        return $this->scope($permission) === Permission::SCOPE_SPORT;
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
     * Deportes asignados al usuario en el club.
     *
     * @return list<int>
     */
    public function sportIds(): array
    {
        if ($this->sportIds !== null) {
            return $this->sportIds;
        }

        if (! $this->clubUser) {
            return $this->sportIds = [];
        }

        if ($this->clubUser->relationLoaded('sports')) {
            return $this->sportIds = $this->clubUser->sports
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return $this->sportIds = DB::connection('tenant')
            ->table('club_user_sports')
            ->where('club_user_id', $this->clubUser->id)
            ->pluck('sport_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Deportes asignados al usuario con id y nombre (para el selector de /api/me).
     *
     * @return list<array{id: int, name: string}>
     */
    public function assignedSports(): array
    {
        if (! $this->clubUser) {
            return [];
        }

        if ($this->clubUser->relationLoaded('sports')) {
            return $this->clubUser->sports
                ->sortBy('name')
                ->map(fn ($sport) => [
                    'id' => (int) $sport->id,
                    'name' => (string) $sport->name,
                ])
                ->values()
                ->all();
        }

        return DB::connection('tenant')
            ->table('club_user_sports')
            ->join('sports', 'sports.id', '=', 'club_user_sports.sport_id')
            ->where('club_user_sports.club_user_id', $this->clubUser->id)
            ->select('sports.id', 'sports.name')
            ->orderBy('sports.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
            ])
            ->values()
            ->all();
    }

    /**
     * IDs de categorías alcanzables para el permiso dado:
     * - null: alcance "all" (todo el club, no filtrar).
     * - list<int>: alcance "sport" (categorías de sus deportes) u "own" (sus categorías como profe).
     *   Si no tiene deportes o categorías asignadas, devuelve [] (no ve nada).
     * - []: no tiene el permiso.
     *
     * @return list<int>|null
     */
    public function scopedCategoryIds(string $permission): ?array
    {
        $scope = $this->scope($permission);

        if ($scope === null) {
            return [];
        }

        if ($scope === Permission::SCOPE_ALL) {
            return null;
        }

        if ($scope === Permission::SCOPE_SPORT) {
            if ($this->sportCategoryIds !== null) {
                return $this->sportCategoryIds;
            }

            $sportIds = $this->sportIds();
            if ($sportIds === []) {
                return $this->sportCategoryIds = [];
            }

            return $this->sportCategoryIds = DB::connection('tenant')
                ->table('categories')
                ->whereIn('sport_id', $sportIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        if ($scope === Permission::SCOPE_OWN) {
            return $this->ownCategoryIds();
        }

        return [];
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
