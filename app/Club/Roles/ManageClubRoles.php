<?php

namespace App\Club\Roles;

use App\Club\Access\ClubAccess;
use App\Club\Access\PermissionCatalog;
use App\Models\Club\Permission;
use App\Models\Club\Role;
use App\Support\ApiErrorException;
use App\Tenancy\TenantDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Alta, edición, baja y duplicado de roles custom (docs/roles-y-permisos.md §5). Lo usan los
 * endpoints del club y los del admin de plataforma. Requiere la conexión "tenant" apuntando al club.
 *
 * $actor es lo que puede hacer quien opera: un usuario del club solo puede dar permisos (y alcances)
 * que él mismo tiene. El admin de plataforma no tiene ese límite (null).
 */
class ManageClubRoles
{
    public function __construct(private PermissionCatalog $catalog) {}

    /**
     * @param  array{name: string, description?: string|null, permissions: list<array{name: string, scope: string}>}  $data
     */
    public function create(array $data, ?ClubAccess $actor = null): Role
    {
        $permissions = $this->resolvePermissions($data['permissions'], $actor);
        $this->ensureUniqueName($data['name']);

        return DB::connection(TenantDatabase::CONNECTION)->transaction(function () use ($data, $permissions) {
            $role = Role::query()->create([
                'slug' => $this->uniqueSlug($data['name']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_system' => false,
            ]);
            $role->permissions()->sync($permissions);

            return $role;
        });
    }

    /**
     * @param  array{name?: string, description?: string|null, permissions?: list<array{name: string, scope: string}>}  $data
     */
    public function update(Role $role, array $data, ?ClubAccess $actor = null): Role
    {
        $this->ensureCustom($role);

        $permissions = array_key_exists('permissions', $data)
            ? $this->resolvePermissions($data['permissions'], $actor)
            : null;

        if (array_key_exists('name', $data)) {
            $this->ensureUniqueName($data['name'], $role);
        }

        DB::connection(TenantDatabase::CONNECTION)->transaction(function () use ($role, $data, $permissions) {
            $role->fill(array_intersect_key($data, ['name' => true, 'description' => true]))->save();

            if ($permissions !== null) {
                $role->permissions()->sync($permissions);
            }
        });

        return $role;
    }

    public function delete(Role $role): void
    {
        $this->ensureCustom($role);

        if ($role->clubUsers()->exists()) {
            throw new ApiErrorException('role_in_use', 409);
        }

        $role->delete();
    }

    /** Copia de un rol (estándar o custom) como rol custom. Es la forma de "modificar" un estándar. */
    public function duplicate(Role $role, ?ClubAccess $actor = null): Role
    {
        $permissions = $role->permissions
            ->map(fn (Permission $permission) => ['name' => $permission->name, 'scope' => $permission->pivot->scope])
            ->values()
            ->all();

        return $this->create([
            'name' => $this->copyName($role->name),
            'description' => $role->description,
            'permissions' => $permissions,
        ], $actor);
    }

    /** Busca el rol por slug con sus permisos; 404 si no existe. */
    public function find(string $slug): Role
    {
        return Role::query()->where('slug', $slug)->with('permissions')->withCount('clubUsers')->firstOrFail();
    }

    private function ensureCustom(Role $role): void
    {
        if ($role->is_system) {
            throw new ApiErrorException('role_is_system', 403);
        }
    }

    /**
     * Valida contra el catálogo y contra lo que puede dar el actor.
     *
     * @param  list<array{name: string, scope: string}>  $permissions
     * @return array<int, array{scope: string}> id => pivot
     */
    private function resolvePermissions(array $permissions, ?ClubAccess $actor): array
    {
        $errors = [];

        foreach ($permissions as $index => $permission) {
            $name = $permission['name'];
            $scope = $permission['scope'];

            if (! $this->catalog->exists($name)) {
                $errors["permissions.{$index}.name"] = "El permiso {$name} no existe.";

                continue;
            }

            if ($scope === Permission::SCOPE_OWN && ! $this->catalog->isScopable($name)) {
                $errors["permissions.{$index}.scope"] = "El permiso {$name} no admite \"sus categorías\".";

                continue;
            }

            // Sin escalada: no se da un permiso que no se tiene, ni "todo el club" teniendo solo "sus categorías".
            if ($actor !== null) {
                $own = $actor->scope($name);
                if ($own === null || ($own === Permission::SCOPE_OWN && $scope === Permission::SCOPE_ALL)) {
                    $errors["permissions.{$index}.name"] = "No podés dar el permiso {$name}".($own ? ' con alcance en todo el club' : '').'.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $ids = Permission::query()
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('id', 'name');

        $pivot = [];
        foreach ($permissions as $permission) {
            $pivot[$ids[$permission['name']]] = ['scope' => $permission['scope']];
        }

        return $pivot;
    }

    private function ensureUniqueName(string $name, ?Role $ignore = null): void
    {
        $exists = Role::query()
            ->where('name', $name)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['name' => 'Ya existe un rol con ese nombre en el club.']);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_') ?: 'rol', 70, '');
        $slug = $base;

        for ($i = 2; Role::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}_{$i}";
        }

        return $slug;
    }

    private function copyName(string $name): string
    {
        $base = Str::limit("Copia de {$name}", 72, '');
        $candidate = $base;

        for ($i = 2; Role::query()->where('name', $candidate)->exists(); $i++) {
            $candidate = "{$base} ({$i})";
        }

        return $candidate;
    }
}
