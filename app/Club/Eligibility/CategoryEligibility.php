<?php

namespace App\Club\Eligibility;

/** Resumen de habilitación de una categoría. */
final readonly class CategoryEligibility
{
    public function __construct(
        public int $category_id,
        public string $category_name,
        public int $players,
        public int $eligible,
        /** Cierre de inscripción más próximo entre sus competencias. */
        public ?string $registration_deadline,
        public ?int $days_to_deadline,
        /** Requisitos aprobados que vencen en los próximos 30 días. */
        public int $expiring_soon,
    ) {}
}
