<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\ApparelItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prenda del catálogo de indumentaria del club (camiseta, short, campera, etc.).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable(['name', 'description', 'is_active'])]
class ApparelItem extends Model
{
    /** @use HasFactory<ApparelItemFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(ApparelOrderItem::class, 'apparel_item_id');
    }
}
