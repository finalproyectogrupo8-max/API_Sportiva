<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_subscribe_to_newsletter(): void
    {
        $response = $this->postJson('/api/newsletter', [
            'email' => 'subscriber@example.com',
        ]);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Suscripción realizada con éxito',
            ]);

        $this->assertDatabaseHas('newsletters', [
            'email' => 'subscriber@example.com',
        ]);
    }

    public function test_email_is_required(): void
    {
        $response = $this->postJson('/api/newsletter', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_valid(): void
    {
        $response = $this->postJson('/api/newsletter', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_unique(): void
    {
        Newsletter::create(['email' => 'duplicate@example.com']);

        $response = $this->postJson('/api/newsletter', [
            'email' => 'duplicate@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
