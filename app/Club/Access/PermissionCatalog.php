<?php

namespace App\Club\Access;

use App\Models\Club\Permission;
use LogicException;

/**
 * Lee config/club-permissions.php: qué permisos existen, a qué módulo pertenecen, cuáles admiten
 * alcance "sus categorías" y con qué permisos nace cada rol estándar (docs/roles-y-permisos.md).
 */
class PermissionCatalog
{
    /** @var array<string, array{name: string, module: string, resource: string, action: string, scopable: bool, description: string}>|null */
    private ?array $permissions = null;

    /**
     * @return array<string, array{name: string, module: string, resource: string, action: string, scopable: bool, description: string}>
     */
    public function permissions(): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }

        $labels = config('club-permissions.action_labels', []);
        $permissions = [];

        foreach (config('club-permissions.modules', []) as $moduleKey => $module) {
            foreach ($module['resources'] as $resource => $definition) {
                $scoped = $definition['scoped'] ?? [];

                foreach ($definition['actions'] as $action) {
                    $name = "{$resource}.{$action}";

                    if (isset($permissions[$name])) {
                        throw new LogicException("El permiso [{$name}] está repetido en config/club-permissions.php.");
                    }

                    $permissions[$name] = [
                        'name' => $name,
                        'module' => $moduleKey,
                        'resource' => $resource,
                        'action' => $action,
                        'scopable' => in_array($action, $scoped, true),
                        'description' => "{$module['label']} · {$definition['label']}: ".($labels[$action] ?? $action),
                    ];
                }
            }
        }

        return $this->permissions = $permissions;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->permissions());
    }

    public function exists(string $name): bool
    {
        return isset($this->permissions()[$name]);
    }

    public function isScopable(string $name): bool
    {
        return $this->permissions()[$name]['scopable'] ?? false;
    }

    /**
     * Roles estándar con sus permisos ya resueltos ("*" y ":own").
     *
     * @return array<string, array{name: string, description: string, permissions: array<string, string>}>
     */
    public function standardRoles(): array
    {
        $roles = [];

        foreach (config('club-permissions.roles', []) as $slug => $role) {
            $roles[$slug] = [
                'name' => $role['name'],
                'description' => $role['description'],
                'permissions' => $this->resolveRolePermissions($slug, $role['permissions']),
            ];
        }

        return $roles;
    }

    /**
     * Módulos con sus recursos y acciones, para armar la grilla de checks.
     *
     * @return list<array{key: string, label: string, resources: list<array{key: string, label: string, actions: list<array{name: string, action: string, label: string, scopable: bool}>}>}>
     */
    public function grid(): array
    {
        $labels = config('club-permissions.action_labels', []);
        $grid = [];

        foreach (config('club-permissions.modules', []) as $moduleKey => $module) {
            $resources = [];

            foreach ($module['resources'] as $resource => $definition) {
                $resources[] = [
                    'key' => $resource,
                    'label' => $definition['label'],
                    'actions' => array_map(fn (string $action) => [
                        'name' => "{$resource}.{$action}",
                        'action' => $action,
                        'label' => $labels[$action] ?? $action,
                        'scopable' => in_array($action, $definition['scoped'] ?? [], true),
                    ], $definition['actions']),
                ];
            }

            $grid[] = ['key' => $moduleKey, 'label' => $module['label'], 'resources' => $resources];
        }

        return $grid;
    }

    /**
     * @param  string|list<string>  $permissions
     * @return array<string, string> nombre => alcance
     */
    private function resolveRolePermissions(string $slug, string|array $permissions): array
    {
        if ($permissions === '*') {
            return array_fill_keys($this->names(), Permission::SCOPE_ALL);
        }

        $resolved = [];

        foreach ($permissions as $entry) {
            [$name, $scope] = array_pad(explode(':', $entry, 2), 2, Permission::SCOPE_ALL);

            if (! $this->exists($name)) {
                throw new LogicException("El rol [{$slug}] usa el permiso [{$name}], que no está en el catálogo.");
            }

            if (in_array($scope, [Permission::SCOPE_OWN, Permission::SCOPE_SPORT], true) && ! $this->isScopable($name)) {
                throw new LogicException("El permiso [{$name}] del rol [{$slug}] no admite alcance {$scope}.");
            }

            if (! in_array($scope, [Permission::SCOPE_ALL, Permission::SCOPE_SPORT, Permission::SCOPE_OWN], true)) {
                throw new LogicException("Alcance [{$scope}] inválido en el rol [{$slug}].");
            }

            $resolved[$name] = $scope;
        }

        return $resolved;
    }
}
