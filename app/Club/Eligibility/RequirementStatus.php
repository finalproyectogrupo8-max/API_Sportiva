<?php

namespace App\Club\Eligibility;

/** Un requisito de un jugador, como lo ve la grilla de habilitación. */
final readonly class RequirementStatus
{
    public function __construct(
        public int $requirement_id,
        public string $name,
        public int $competition_id,
        public string $competition_name,
        /** `pending`, `submitted`, `approved`, `rejected`, `expired` o `not_applicable` (por ejemplo, "solo menores" a un mayor). */
        public string $status,
        public bool $is_required,
        public bool $requires_file,
        public ?string $expires_at,
        /** Vence en los próximos 30 días. */
        public bool $expiring_soon,
        public ?int $document_id,
        public ?string $rejection_reason,
    ) {}
}
