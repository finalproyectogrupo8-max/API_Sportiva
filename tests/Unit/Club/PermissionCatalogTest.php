<?php

namespace Tests\Unit\Club;

use App\Club\Access\PermissionCatalog;
use LogicException;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_standard_roles_only_use_catalog_permissions(): void
    {
        $catalog = new PermissionCatalog;
        $roles = $catalog->standardRoles();

        $this->assertSame(
            ['admin', 'coordinator', 'sport_coordinator', 'coach', 'physical_trainer', 'inventory_manager', 'maintenance_manager'],
            array_keys($roles),
        );
        $this->assertSame($catalog->names(), array_keys($roles['admin']['permissions']));
        $this->assertSame('sport', $roles['sport_coordinator']['permissions']['players.view']);
        $this->assertSame('own', $roles['coach']['permissions']['players.view']);
        $this->assertArrayNotHasKey('apparel_orders.approve', $roles['coordinator']['permissions']);
        $this->assertArrayNotHasKey('apparel_orders.approve', $roles['sport_coordinator']['permissions']);
    }

    public function test_every_front_module_has_a_view_permission(): void
    {
        $catalog = new PermissionCatalog;

        foreach (['players', 'coaches', 'matches', 'physical_evaluations', 'project', 'apparel_orders', 'inventory', 'facilities', 'categories', 'club_users', 'announcements'] as $resource) {
            $this->assertTrue($catalog->exists("{$resource}.view"), "{$resource}.view");
        }
    }

    public function test_unknown_permission_in_a_role_is_rejected(): void
    {
        config(['club-permissions.roles.coach.permissions' => ['players.fly']]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('players.fly');

        (new PermissionCatalog)->standardRoles();
    }

    public function test_own_scope_on_a_non_scopable_permission_is_rejected(): void
    {
        config(['club-permissions.roles.coach.permissions' => ['sports.view:own']]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('no admite alcance own');

        (new PermissionCatalog)->standardRoles();
    }

    public function test_sport_scope_on_a_non_scopable_permission_is_rejected(): void
    {
        config(['club-permissions.roles.sport_coordinator.permissions' => ['sports.view:sport']]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('no admite alcance sport');

        (new PermissionCatalog)->standardRoles();
    }

    public function test_grid_groups_actions_by_module_and_resource(): void
    {
        $grid = (new PermissionCatalog)->grid();
        $players = collect($grid)->firstWhere('key', 'jugadores');

        $this->assertSame('Jugadores', $players['label']);
        $this->assertSame('players.view', $players['resources'][0]['actions'][0]['name']);
        $this->assertSame('Ver', $players['resources'][0]['actions'][0]['label']);
        $this->assertTrue($players['resources'][0]['actions'][0]['scopable']);
    }
}
