<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un jugador en una categoría, desde y hasta cuándo. El historial no se borra: al pasar de
 * categoría se cierra la asignación (end_date, status ended) y se abre otra.
 *
 * @property int $id
 * @property int $player_id
 * @property int $category_id
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property int|null $shirt_number
 * @property string $status
 */
#[Fillable(['player_id', 'category_id', 'start_date', 'end_date', 'shirt_number', 'status'])]
class PlayerCategoryMembership extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['start_date' => 'immutable_date', 'end_date' => 'immutable_date', 'shirt_number' => 'integer'];
    }

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
