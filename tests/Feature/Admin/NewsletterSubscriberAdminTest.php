<?php

namespace Tests\Feature\Admin;

use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Suscriptores del newsletter en el panel de plataforma (FRO-27). */
class NewsletterSubscriberAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_searches_and_exports_csv(): void
    {
        Newsletter::create(['email' => 'ana@club.test']);
        Newsletter::create(['email' => 'beto@otro.test']);
        Sanctum::actingAs(User::factory()->platformAdmin()->create(), ['platform:admin']);

        $this->getJson('/api/admin/newsletter-subscribers')->assertOk()->assertJsonPath('meta.total', 2)->assertJsonCount(2, 'data');
        $this->getJson('/api/admin/newsletter-subscribers?search=club')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'ana@club.test');

        $csv = $this->get('/api/admin/newsletter-subscribers?format=csv');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $body = $csv->streamedContent();
        $this->assertStringContainsString('email,suscripto_el', $body);
        $this->assertStringContainsString('ana@club.test', $body);
        $this->assertStringContainsString('beto@otro.test', $body);
    }

    public function test_only_platform_admins(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['tenant:operate']);
        $this->getJson('/api/admin/newsletter-subscribers')->assertForbidden();
    }
}
