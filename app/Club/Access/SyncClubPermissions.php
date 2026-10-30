<?php

namespace App\Club\Access;

use App\Models\Club\Permission;
use App\Models\Club\Role;
use App\Tenancy\TenantDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Copia el catálogo a la base del club actual (la conexión "tenant" ya tiene que apuntar a ella).
 * Idempotente: corre en el alta del club y en cada tenants:migrate.
 *
 * - Crea los permisos nuevos y borra los que salieron del catálogo (las asignaciones caen por CASCADE).
 * - Crea los roles estándar que falten y resincroniza sus permisos con la matriz.
 * - No toca los roles custom, salvo que pierden los permisos borrados.
 */
class SyncClubPermissions
{
    public function __construct(private PermissionCatalog $catalog) {}

    public function sync(): void
    {
        DB::connection(TenantDatabase::CONNECTION)->transaction(function () {
            $ids = $this->syncPermissions();

            foreach ($this->catalog->standardRoles() as $slug => $definition) {
                $role = Role::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $definition['name'], 'description' => $definition['description'], 'is_system' => true],
                );

                $role->permissions()->sync(collect($definition['permissions'])
                    ->mapWithKeys(fn (string $scope, string $name) => [$ids[$name] => ['scope' => $scope]])
                    ->all());
            }
        });
    }

    /** @return array<string, int> nombre => id */
    private function syncPermissions(): array
    {
        $catalog = $this->catalog->permissions();

        Permission::query()->whereNotIn('name', array_keys($catalog))->delete();

        Permission::query()->upsert(
            array_map(fn (array $permission) => [
                'name' => $permission['name'],
                'module' => $permission['module'],
                'description' => $permission['description'],
            ], array_values($catalog)),
            ['name'],
            ['module', 'description'],
        );

        return Permission::query()->pluck('id', 'name')->all();
    }
}
