<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Comunicado del club (TIZ-24). Recibe los textos ya resueltos, no modelos de club: se arma dentro
 * de la base del club y se manda en el momento (`sendNow`). Las respuestas van a quien lo envió.
 */
class AnnouncementMail extends BaseMailable
{
    public function __construct(
        public string $clubName,
        public string $subjectLine,
        public string $body,
        public string $recipientName,
        public ?string $replyToEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->clubName}] {$this->subjectLine}",
            replyTo: $this->replyToEmail ? [new Address($this->replyToEmail)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.announcement');
    }
}
