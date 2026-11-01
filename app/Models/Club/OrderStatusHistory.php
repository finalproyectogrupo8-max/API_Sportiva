<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\OrderStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro inmutable del historial de cambios de estado de un pedido.
 *
 * @property int $id
 * @property int $order_id
 * @property int $changed_by
 * @property string|null $old_status
 * @property string $new_status
 * @property string|null $comment
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'order_id',
    'changed_by',
    'old_status',
    'new_status',
    'comment',
    'created_at',
])]
class OrderStatusHistory extends Model
{
    /** @use HasFactory<OrderStatusHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'order_status_history';

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'changed_by');
    }
}
