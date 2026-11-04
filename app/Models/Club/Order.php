<?php

namespace App\Models\Club;

use App\Club\Access\ClubAccess;
use Carbon\CarbonImmutable;
use Database\Factories\Club\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pedido de indumentaria o materiales del club.
 *
 * @property int $id
 * @property string $order_number
 * @property string $type
 * @property int $requested_by
 * @property int|null $category_id
 * @property string $status
 * @property string $priority
 * @property string|null $description
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'order_number',
    'type',
    'requested_by',
    'category_id',
    'status',
    'priority',
    'description',
    'approved_by',
    'approved_at',
    'delivered_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_APPAREL = 'apparel';

    public const TYPE_MATERIAL = 'material';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_PARTIALLY_DELIVERED = 'partially_delivered';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_REJECTED = 'rejected';

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'approved_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
        ];
    }

    /**
     * Genera el siguiente número de pedido con formato YYYY-0001 (Decisión D13)
     * serializado con lock dentro de la transacción de la base del club.
     */
    public static function generateOrderNumber(?int $year = null): string
    {
        $year ??= (int) date('Y');
        $prefix = "{$year}-";

        $maxSeq = static::query()
            ->where('order_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING(order_number, 6) AS UNSIGNED)) as max_seq')
            ->value('max_seq');

        $nextSeq = ((int) $maxSeq) + 1;

        return sprintf('%04d-%04d', $year, $nextSeq);
    }

    /**
     * Registra una transición en el historial de estados.
     */
    public function recordStatusTransition(int $changedByClubUserId, string $newStatus, ?string $comment = null): OrderStatusHistory
    {
        $oldStatus = $this->status;

        $this->status = $newStatus;
        $this->save();

        return $this->statusHistory()->create([
            'changed_by' => $changedByClubUserId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    /**
     * Pedidos que el usuario puede ver. Cada tipo usa su propio permiso: sin
     * `apparel_orders.view` no ve pedidos de indumentaria, sin `material_orders.view` no ve
     * los de materiales. Con alcance reducido (sport u own) ve los de sus categorías y los
     * que pidió él.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, ClubAccess $access): void
    {
        $currentClubUserId = $access->clubUser?->id ?? 0;
        $types = [self::TYPE_APPAREL => 'apparel_orders.view', self::TYPE_MATERIAL => 'material_orders.view'];

        $query->where(function (Builder $q) use ($access, $types, $currentClubUserId) {
            $q->whereRaw('1 = 0');

            foreach ($types as $type => $permission) {
                if (! $access->has($permission)) {
                    continue;
                }

                $categoryIds = $access->scope($permission) === Permission::SCOPE_ALL ? null : $access->scopedCategoryIds($permission);

                $q->orWhere(fn (Builder $ofType) => $ofType
                    ->where('type', $type)
                    ->when($categoryIds !== null, fn (Builder $scoped) => $scoped->where(fn (Builder $mine) => $mine
                        ->whereIn('category_id', $categoryIds ?: [0])
                        ->orWhere('requested_by', $currentClubUserId))));
            }
        });
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'approved_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function apparelOrderItems(): HasMany
    {
        return $this->hasMany(ApparelOrderItem::class, 'order_id');
    }

    public function materialOrderItems(): HasMany
    {
        return $this->hasMany(MaterialOrderItem::class, 'order_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id')->orderBy('id', 'asc');
    }
}
