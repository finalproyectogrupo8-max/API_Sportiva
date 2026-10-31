<?php

namespace App\Club\Calendar;

/** Un partido o un entrenamiento en el calendario del club. */
final readonly class CalendarEvent
{
    public function __construct(
        /** `match` o `training`. */
        public string $type,
        public int $id,
        public int $category_id,
        public string $category_name,
        public string $title,
        public string $starts_at,
        public ?string $ends_at,
        public ?string $location,
        /** Solo partidos: `scheduled`, `played`, `postponed` o `cancelled`. */
        public ?string $status,
    ) {}
}
