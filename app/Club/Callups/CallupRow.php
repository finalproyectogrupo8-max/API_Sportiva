<?php

namespace App\Club\Callups;

/** Un convocado con su respuesta y sus links. */
final readonly class CallupRow
{
    public function __construct(
        public int $player_id,
        public string $player_name,
        public ?int $shirt_number,
        /** `pending`, `going` o `not_going`. */
        public string $response,
        public ?string $response_note,
        public ?string $responded_at,
        /** Link personal para confirmar (página del front). */
        public string $personal_url,
        /** WhatsApp al contacto principal con el mensaje armado; null si no tiene teléfono. */
        public ?string $whatsapp_url,
    ) {}
}
