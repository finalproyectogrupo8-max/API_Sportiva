<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\InvalidTenantDatabaseName;
use App\Tenancy\TenantDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\InteractsWithTenants;
use Tests\Fixtures\CountTestItems;
use Tests\Fixtures\TestItem;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function tenantConnectionDatabase(): ?string
    {
        return config('database.connections.tenant.database');
    }

    public function test_a_club_request_reads_the_club_database_from_the_token(): void
    {
        $this->inTenant($this->tenantA(), fn () => TestItem::create(['name' => 'de A']));
        $this->inTenant($this->tenantB(), fn () => TestItem::create(['name' => 'de B']));

        $this->withClubToken($this->clubToken($this->tenantA()))
            ->getJson('/api/test-items')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'de A');

        $this->assertNull($this->tenantConnectionDatabase());
    }

    public function test_the_client_cannot_choose_another_club(): void
    {
        $this->inTenant($this->tenantA(), fn () => TestItem::create(['name' => 'de A']));
        $this->inTenant($this->tenantB(), fn () => TestItem::create(['name' => 'de B']));
        $tenantB = $this->tenantB();

        $this->withClubToken($this->clubToken($this->tenantA()))
            ->withHeader('X-Tenant', $tenantB->slug)
            ->getJson("/api/test-items?tenant={$tenantB->slug}&tenant_id={$tenantB->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'de A');
    }

    public function test_a_record_of_another_club_is_not_found_and_not_changed(): void
    {
        $itemB = $this->inTenant($this->tenantB(), fn () => TestItem::create(['name' => 'original']));
        $tokenA = $this->clubToken($this->tenantA());

        $this->withClubToken($tokenA)->getJson("/api/test-items/{$itemB->id}")->assertNotFound();
        $this->withClubToken($tokenA)
            ->patchJson("/api/test-items/{$itemB->id}", ['name' => 'pisado'])
            ->assertNotFound();

        $this->assertSame('original', $this->inTenant($this->tenantB(), fn () => TestItem::find($itemB->id)->name));
    }

    public function test_consecutive_requests_of_different_clubs_read_each_its_database(): void
    {
        $itemA = $this->inTenant($this->tenantA(), fn () => TestItem::create(['name' => 'de A']));
        $itemB = $this->inTenant($this->tenantB(), fn () => TestItem::create(['name' => 'de B']));

        $this->withClubToken($this->clubToken($this->tenantA()))
            ->getJson("/api/test-items/{$itemA->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'de A');

        $this->withClubToken($this->clubToken($this->tenantB()))
            ->getJson("/api/test-items/{$itemB->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'de B');
    }

    public function test_failed_validations_do_not_connect_to_a_club_database(): void
    {
        $spy = $this->spy(TenantDatabase::class);

        $user = User::factory()->create();
        $preTenantToken = $user->createToken('pre-tenant', ['tenant:select'])->plainTextToken;
        $this->withClubToken($preTenantToken)->getJson('/api/test-items')
            ->assertForbidden()->assertJsonPath('code', 'tenant_required');

        $inactiveMember = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $inactiveMember);
        TenantMembership::where('user_id', $inactiveMember->id)->update(['is_active' => false]);
        $this->withClubToken($token)->getJson('/api/test-items')
            ->assertForbidden()->assertJsonPath('code', 'membership_inactive');

        $suspended = Tenant::factory()->create(['status' => 'suspended']);
        $this->withClubToken($this->clubToken($suspended))->getJson('/api/test-items')
            ->assertForbidden()->assertJsonPath('code', 'tenant_inactive');

        $spy->shouldNotHaveReceived('connect');
    }

    public function test_an_invalid_database_name_responds_a_generic_500_and_is_logged(): void
    {
        Log::spy();
        $broken = Tenant::factory()->create(['database_name' => 'club_Roto']);

        $response = $this->withClubToken($this->clubToken($broken))
            ->getJson('/api/test-items')
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Server Error']);

        $this->assertStringNotContainsString('club_Roto', $response->getContent());
        Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => ($context['tenant_id'] ?? null) === $broken->id
            && $context['exception'] instanceof InvalidTenantDatabaseName
            && ! str_contains($message, 'club_Roto'));
        $this->assertNull($this->tenantConnectionDatabase());
    }

    public function test_club_jobs_read_each_its_database_and_leave_no_context(): void
    {
        CountTestItems::$counts = [];
        $this->inTenant($this->tenantA(), fn () => TestItem::create(['name' => 'uno']));
        $this->inTenant($this->tenantB(), function () {
            TestItem::create(['name' => 'uno']);
            TestItem::create(['name' => 'dos']);
        });

        CountTestItems::dispatchSync($this->tenantA()->id);
        CountTestItems::dispatchSync($this->tenantB()->id);

        $this->assertSame([$this->tenantA()->id => 1, $this->tenantB()->id => 2], CountTestItems::$counts);
        $this->assertNull($this->tenantConnectionDatabase());
    }

    public function test_each_test_starts_with_empty_club_tables_first(): void
    {
        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => TestItem::count()));
        $this->inTenant($this->tenantA(), fn () => TestItem::create(['name' => 'queda']));
    }

    public function test_each_test_starts_with_empty_club_tables_second(): void
    {
        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => TestItem::count()));
    }
}
