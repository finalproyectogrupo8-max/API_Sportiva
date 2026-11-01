<?php

namespace App\Club\Callups;

/** Un jugador del plantel al armar la convocatoria. */
final readonly class SquadOption
{
    /** @param  list<string>  $missing */
    public function __construct(
        public int $player_id,
        public string $player_name,
        public ?int $shirt_number,
        /** Habilitado para jugar (TIZ-32): solo estos se pueden convocar. */
        public bool $is_eligible,
        /** Nombres de los requisitos obligatorios que le faltan. */
        public array $missing,
        public bool $called_up,
    ) {}
}
