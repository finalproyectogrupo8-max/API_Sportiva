<?php

namespace App\Models\Club;

use Database\Factories\Club\ApparelOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de pedido de indumentaria por jugador.
 *
 * @property int $id
 * @property int $order_id
 * @property int $player_id
 * @property int $apparel_item_id
 * @property string $size
 * @property int|null $shirt_number
 * @property string|null $alias
 * @property int $quantity
 * @property int $delivered_quantity
 * @property string|null $notes
 */
#[Fillable([
    'order_id',
    'player_id',
    'apparel_item_id',
    'size',
    'shirt_number',
    'alias',
    'quantity',
    'delivered_quantity',
    'notes',
])]
class ApparelOrderItem extends Model
{
    /** @use HasFactory<ApparelOrderItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'delivered_quantity' => 'integer',
            'shirt_number' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function apparelItem(): BelongsTo
    {
        return $this->belongsTo(ApparelItem::class, 'apparel_item_id');
    }
}
