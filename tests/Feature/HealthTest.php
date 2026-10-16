<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_reports_ok_when_the_database_is_reachable(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'checks' => ['database' => 'ok'],
            ]);
    }

    public function test_reports_503_when_the_database_is_unreachable(): void
    {
        $this->breakDatabaseConnection();

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertExactJson([
                'status' => 'error',
                'checks' => ['database' => 'error'],
            ]);
    }

    public function test_does_not_leak_connection_details_even_with_debug_enabled(): void
    {
        config(['app.debug' => true]);
        $this->breakDatabaseConnection();

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertExactJson([
                'status' => 'error',
                'checks' => ['database' => 'error'],
            ]);

        $body = $response->getContent();
        foreach (['SQLSTATE', config('database.connections.mysql.host'), config('database.connections.mysql.database'), config('database.connections.mysql.username')] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
    }

    private function breakDatabaseConnection(): void
    {
        // Puerto cerrado en el mismo host: la conexión falla rápido y de forma determinista.
        config(['database.connections.mysql.port' => 1]);
        DB::purge('mysql');
    }
}
