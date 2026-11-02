<?php

namespace App\Mail;

use App\Club\Callups\CallupNoticeMode;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Aviso de la convocatoria al contacto principal (TIZ-37). Recibe los textos ya resueltos, no modelos
 * de club: se arma dentro de la base del club y se manda en el momento (`sendNow`).
 */
class CallupNoticeMail extends BaseMailable
{
    /**
     * @param  list<string>  $summary  Los datos del partido, una línea por dato.
     * @param  list<array{name: string, url: string}>  $players  Un convocado por botón "Confirmar".
     */
    public function __construct(
        public string $subjectLine,
        public CallupNoticeMode $mode,
        public string $clubName,
        public string $categoryName,
        public array $summary,
        public array $players,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.callup-notice');
    }
}
