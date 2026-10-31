<?php

namespace Tests\Feature\Club;

use App\Models\Club\Sport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Perfil del club (TIZ-13): alta y edición desde la plataforma y desde el club. */
class ClubProfileTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private const PROFILE = [
        'short_name' => 'CARP',
        'city' => 'Buenos Aires',
        'founded_year' => 1901,
        'primary_color' => '#d6001c',
    ];

    public function test_platform_admin_creates_and_edits_the_profile(): void
    {
        Queue::fake();
        Sanctum::actingAs(User::factory()->platformAdmin()->create(), ['*']);

        $id = $this->postJson('/api/admin/tenants', ['name' => 'River Plate', 'slug' => 'river_plate', ...self::PROFILE])
            ->assertCreated()
            ->assertJsonPath('data.short_name', 'CARP')
            ->assertJsonPath('data.founded_year', 1901)
            ->assertJsonPath('data.primary_color', '#d6001c')
            ->json('data.id');

        $this->patchJson("/api/admin/tenants/{$id}", ['city' => 'Núñez', 'primary_color' => null])
            ->assertOk()
            ->assertJsonPath('data.city', 'Núñez')
            ->assertJsonPath('data.primary_color', null)
            ->assertJsonPath('data.short_name', 'CARP');

        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'tenant', 'subject_id' => $id, 'action' => 'tenant.updated']);
    }

    public function test_profile_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->platformAdmin()->create(), ['*']);

        $this->postJson('/api/admin/tenants', [
            'name' => 'Club',
            'slug' => 'club_x',
            'short_name' => 'DEMASIADO',
            'founded_year' => now()->year + 1,
            'primary_color' => 'rojo',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'short_name' => 'La sigla puede tener hasta 6 caracteres.',
                'founded_year' => 'El año de fundación no puede ser futuro.',
                'primary_color' => 'El color tiene que ser un hexadecimal como #1d4ed8.',
            ]);
    }

    public function test_session_endpoints_expose_the_profile(): void
    {
        $tenant = $this->tenantA();
        $tenant->update(self::PROFILE);
        $token = $this->clubToken($tenant, roles: ['coach']);

        $this->withClubToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('tenant.short_name', 'CARP')
            ->assertJsonPath('tenant.city', 'Buenos Aires');

        $this->withClubToken($token)->getJson('/api/me/tenants')
            ->assertOk()
            ->assertJsonPath('tenants.0.primary_color', '#d6001c');
    }

    public function test_any_member_sees_the_club_with_its_stats(): void
    {
        $tenant = $this->tenantA();
        $tenant->update(self::PROFILE);

        $this->inTenant($tenant, function () {
            Sport::create(['name' => 'Vóley', 'is_active' => true]);
            Sport::create(['name' => 'Básquet', 'is_active' => true]);
            Sport::create(['name' => 'Hockey', 'is_active' => false]);
            $player = ['first_name' => 'Ana', 'last_name' => 'Pérez', 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null];
            DB::connection('tenant')->table('players')->insert([
                [...$player, 'status' => 'active'],
                [...$player, 'status' => 'active'],
                [...$player, 'status' => 'inactive'],
                [...$player, 'status' => 'active', 'deleted_at' => now()],
            ]);
        });

        $this->withClubToken($this->clubToken($tenant, roles: ['inventory_manager']))
            ->getJson('/api/club')
            ->assertOk()
            ->assertJsonPath('data.name', $tenant->name)
            ->assertJsonPath('data.short_name', 'CARP')
            ->assertJsonPath('data.stats.active_players', 2)
            ->assertJsonPath('data.stats.sports', ['Básquet', 'Vóley']);
    }

    public function test_club_admin_edits_the_profile(): void
    {
        $tenant = $this->tenantA();
        $token = $this->clubToken($tenant, roles: ['admin']);

        $this->withClubToken($token)
            ->patchJson('/api/club', ['name' => 'Club Atlético River', ...self::PROFILE])
            ->assertOk()
            ->assertJsonPath('data.name', 'Club Atlético River')
            ->assertJsonPath('data.founded_year', 1901);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'short_name' => 'CARP', 'city' => 'Buenos Aires']);

        $this->withClubToken($token)
            ->patchJson('/api/club', ['slug' => 'otro', 'status' => 'suspended'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug', 'status']);
    }

    public function test_editing_requires_club_update(): void
    {
        $tenant = $this->tenantA();

        $this->withClubToken($this->clubToken($tenant, roles: ['coordinator']))
            ->patchJson('/api/club', ['city' => 'Rosario'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->assertNull(Tenant::find($tenant->id)->city);
    }

    public function test_each_club_only_edits_itself(): void
    {
        $this->withClubToken($this->clubToken($this->tenantA(), roles: ['admin']))
            ->patchJson('/api/club', ['city' => 'Rosario'])
            ->assertOk();

        $this->assertNull(Tenant::find($this->tenantB()->id)->city);
    }
}
