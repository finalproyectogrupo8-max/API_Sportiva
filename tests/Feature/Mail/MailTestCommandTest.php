<?php

namespace Tests\Feature\Mail;

use App\Mail\MailTestMailable;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTestCommandTest extends TestCase
{
    public function test_it_queues_a_test_email_and_displays_safe_transport_details(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'mailpit',
            'mail.from.name' => 'Sportiva',
            'mail.from.address' => 'no-reply@sportiva.test',
        ]);
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'destinatario@example.test'])
            ->expectsOutputToContain('Mailer: smtp')
            ->expectsOutputToContain('Host: mailpit')
            ->expectsOutputToContain('Remitente: Sportiva <no-reply@sportiva.test>')
            ->expectsOutputToContain('quedó encolado')
            ->assertSuccessful();

        Mail::assertQueued(MailTestMailable::class);
    }

    public function test_it_sends_a_test_email_synchronously_when_requested(): void
    {
        Mail::fake();

        $this->artisan('mail:test', [
            'email' => 'destinatario@example.test',
            '--sync' => true,
        ])->expectsOutputToContain('se envió sin pasar por la cola')
            ->assertSuccessful();

        Mail::assertSent(MailTestMailable::class);
        Mail::assertNothingQueued();
    }

    public function test_it_rejects_an_invalid_email_without_sending(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'no-es-email'])
            ->expectsOutputToContain('La dirección de email no es válida.')
            ->assertFailed();

        Mail::assertNothingOutgoing();
    }
}
