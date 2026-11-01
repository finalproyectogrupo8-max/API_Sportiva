<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\StorageLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Depósito o ubicación física del club donde se almacena stock.
 *
 * @property int $id
 * @property string $name
 * @property string|null $location_code
 * @property string|null $description
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable(['name', 'location_code', 'description', 'is_active'])]
class StorageLocation extends Model
{
    /** @use HasFactory<StorageLocationFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'location_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'location_id');
    }
}
