<?php

namespace Tests\Feature\Mail;

use App\Mail\MailTestMailable;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailQueueingTest extends TestCase
{
    public function test_application_mailables_are_queued_by_default(): void
    {
        Mail::fake();

        Mail::to('prueba@example.test')->send(new MailTestMailable);

        Mail::assertQueued(MailTestMailable::class);
    }

    public function test_mail_test_renders_brand_and_frontend_action(): void
    {
        $rendered = (new MailTestMailable)->render();

        $this->assertStringContainsString('sportıva', $rendered);
        $this->assertStringContainsString('lang="es-AR"', $rendered);
        $this->assertStringContainsString('Ingresá a Sportiva', $rendered);
        $this->assertStringContainsString(config('app.frontend_url'), $rendered);
        $this->assertStringNotContainsString('laravel.com/img/notification-logo', $rendered);
    }
}
