<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\InventoryBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saldo de stock actual por material y ubicación.
 *
 * @property int $id
 * @property int $material_id
 * @property int $location_id
 * @property string $quantity
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['material_id', 'location_id', 'quantity', 'updated_at'])]
class InventoryBalance extends Model
{
    /** @use HasFactory<InventoryBalanceFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    public const CREATED_AT = null;

    public const UPDATED_AT = 'updated_at';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'location_id');
    }
}
