<?php

namespace App\Club\Eligibility;

/** Un jugador con el estado de sus requisitos y si está habilitado. */
final readonly class PlayerEligibility
{
    /** @param  list<RequirementStatus>  $requirements */
    public function __construct(
        public int $player_id,
        public string $player_name,
        public ?int $shirt_number,
        /** Tiene aprobados y vigentes todos los requisitos obligatorios que le aplican. */
        public bool $is_eligible,
        /** Cuántos requisitos obligatorios le faltan. */
        public int $missing,
        public array $requirements,
    ) {}
}
