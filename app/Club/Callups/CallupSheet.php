<?php

namespace App\Club\Callups;

/** La convocatoria de un partido: convocados, respuestas, plantel y textos para compartir. */
final readonly class CallupSheet
{
    /**
     * @param  list<CallupRow>  $callups
     * @param  list<SquadOption>  $squad
     */
    public function __construct(
        public int $match_id,
        public ?string $published_at,
        /** Cambió la fecha, la hora o el lugar después de publicar: hay que volver a avisar. */
        public bool $updated_after_publish,
        /** Página pública de la convocatoria; null hasta publicar. */
        public ?string $public_url,
        /** Mensaje para el grupo de WhatsApp; null hasta publicar. */
        public ?string $group_message,
        public int $going,
        public int $not_going,
        public int $pending,
        public array $callups,
        public array $squad,
    ) {}
}
