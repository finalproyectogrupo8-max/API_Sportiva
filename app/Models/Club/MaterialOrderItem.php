<?php

namespace App\Models\Club;

use Database\Factories\Club\MaterialOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de pedido de materiales por cantidad.
 *
 * @property int $id
 * @property int $order_id
 * @property int $material_id
 * @property string $quantity
 * @property string $delivered_quantity
 * @property string|null $notes
 */
#[Fillable([
    'order_id',
    'material_id',
    'quantity',
    'delivered_quantity',
    'notes',
])]
class MaterialOrderItem extends Model
{
    /** @use HasFactory<MaterialOrderItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'delivered_quantity' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
