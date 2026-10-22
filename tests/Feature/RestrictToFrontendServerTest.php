<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RestrictToFrontendServerTest extends TestCase
{
    public function test_health_check_is_always_accessible(): void
    {
        $this->getJson('/api/health')->assertOk();
    }

    public function test_frontend_restriction_blocks_unauthorized_when_enforced(): void
    {
        Config::set('services.frontend.restriction_enabled', true);
        Config::set('services.frontend.bff_secret', 'secret_token_123');
        $this->app['env'] = 'production';

        $this->getJson('/api/health')->assertOk();

        $this->getJson('/api/me/tenants')
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden_origin');

        $this->withHeader('X-Sportiva-BFF-Secret', 'secret_token_123')
            ->getJson('/api/me/tenants')
            ->assertUnauthorized(); // Supera el middleware de frontend y llega al auth:sanctum
    }

    public function test_frontend_restriction_allows_whitelisted_ip(): void
    {
        Config::set('services.frontend.restriction_enabled', true);
        Config::set('services.frontend.server_ips', ['203.0.113.10']);
        $this->app['env'] = 'production';

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/me/tenants')
            ->assertUnauthorized(); // Supera el middleware de frontend y llega al auth:sanctum
    }
}
