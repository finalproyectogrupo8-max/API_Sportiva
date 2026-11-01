<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MailTestMailable extends BaseMailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Probá el correo de Sportiva',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.test',
            with: [
                'actionUrl' => config('app.frontend_url'),
            ],
        );
    }
}
