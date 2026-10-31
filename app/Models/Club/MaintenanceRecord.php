<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\MaintenanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de trabajo de mantenimiento preventivo o correctivo realizado.
 *
 * @property int $id
 * @property int|null $repair_request_id
 * @property int $facility_id
 * @property int|null $performed_by
 * @property CarbonImmutable $performed_at
 * @property string $description
 * @property string|null $cost
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'repair_request_id',
    'facility_id',
    'performed_by',
    'performed_at',
    'description',
    'cost',
])]
class MaintenanceRecord extends Model
{
    /** @use HasFactory<MaintenanceRecordFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'performed_at' => 'immutable_datetime',
            'cost' => 'decimal:2',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(RepairRequest::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'performed_by');
    }
}
