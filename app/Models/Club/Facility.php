<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\FacilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Instalación o cancha del club (cancha, gimnasio, vestuario, etc.).
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string|null $location
 * @property string $status
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable(['name', 'type', 'location', 'status', 'notes'])]
class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    public function components(): HasMany
    {
        return $this->hasMany(FacilityComponent::class);
    }

    public function repairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
