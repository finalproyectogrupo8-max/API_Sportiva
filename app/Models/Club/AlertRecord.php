<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de alerta generada para control de deduplicación y resolución (TIZ-27).
 *
 * @property int $id
 * @property string $alert_type
 * @property string $subject_type
 * @property int $subject_id
 * @property string $dedup_key
 * @property CarbonImmutable $notified_at
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['alert_type', 'subject_type', 'subject_id', 'dedup_key', 'notified_at', 'resolved_at'])]
class AlertRecord extends Model
{
    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'notified_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function markAsResolved(): void
    {
        if (! $this->resolved_at) {
            $this->update(['resolved_at' => now()]);
        }
    }
}
