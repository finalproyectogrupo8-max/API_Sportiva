<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\FacilityComponentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Componente o elemento de una instalación (aro, tablero, red, iluminación, etc.).
 *
 * @property int $id
 * @property int $facility_id
 * @property string $name
 * @property string $condition_status
 * @property CarbonImmutable|null $last_inspected_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['facility_id', 'name', 'condition_status', 'last_inspected_at', 'notes'])]
class FacilityComponent extends Model
{
    /** @use HasFactory<FacilityComponentFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'last_inspected_at' => 'immutable_datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function repairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class, 'component_id');
    }
}
