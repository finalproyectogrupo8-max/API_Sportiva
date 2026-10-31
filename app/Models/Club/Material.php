<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Material del inventario del club (pelotas, conos, pecheras, etc.).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $unit
 * @property string $minimum_stock
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable(['name', 'description', 'unit', 'minimum_stock', 'is_active'])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'minimum_stock' => 'decimal:2',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'material_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'material_id');
    }
}
