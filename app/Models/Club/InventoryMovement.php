<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento inmutable del libro de inventario.
 *
 * @property int $id
 * @property int $material_id
 * @property int $location_id
 * @property string $movement_type
 * @property string $quantity
 * @property string|null $transfer_group_id
 * @property int|null $order_id
 * @property int $performed_by
 * @property string|null $reason
 * @property string|null $reference
 * @property CarbonImmutable $created_at
 */
#[Fillable([
    'material_id',
    'location_id',
    'movement_type',
    'quantity',
    'transfer_group_id',
    'order_id',
    'performed_by',
    'reason',
    'reference',
    'created_at',
])]
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'created_at' => 'immutable_datetime',
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

    public function performer(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'performed_by');
    }
}
