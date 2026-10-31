<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\RepairRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Solicitud o pedido de reparación sobre una instalación o componente.
 *
 * @property int $id
 * @property int $facility_id
 * @property int|null $component_id
 * @property int $reported_by
 * @property int|null $assigned_to
 * @property string $title
 * @property string $description
 * @property string $priority
 * @property string $status
 * @property CarbonImmutable $reported_at
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'facility_id',
    'component_id',
    'reported_by',
    'assigned_to',
    'title',
    'description',
    'priority',
    'status',
    'reported_at',
    'resolved_at',
])]
class RepairRequest extends Model
{
    /** @use HasFactory<RepairRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'reported_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(FacilityComponent::class, 'component_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'assigned_to');
    }

    public function maintenanceRecord(): HasOne
    {
        return $this->hasOne(MaintenanceRecord::class);
    }
}
