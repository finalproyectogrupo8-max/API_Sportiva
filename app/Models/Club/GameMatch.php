<?php

namespace App\Models\Club;

use App\Models\Club\Concerns\ScopedByCategory;
use Carbon\CarbonImmutable;
use Database\Factories\Club\GameMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Partido de una categoría. Se llama GameMatch porque `match` es palabra reservada de PHP.
 *
 * @property int $id
 * @property int $category_id
 * @property string $opponent
 * @property CarbonImmutable $match_date
 * @property CarbonImmutable|null $meet_at
 * @property string|null $venue
 * @property string|null $maps_url
 * @property bool $is_home
 * @property string|null $uniform
 * @property string|null $uniform_color
 * @property string|null $what_to_bring
 * @property int|null $our_score
 * @property int|null $opponent_score
 * @property string $status
 * @property string|null $notes
 */
#[Fillable(['category_id', 'opponent', 'match_date', 'meet_at', 'venue', 'maps_url', 'is_home', 'uniform', 'uniform_color', 'what_to_bring', 'our_score', 'opponent_score', 'status', 'notes'])]
class GameMatch extends Model
{
    /** @use HasFactory<GameMatchFactory> */
    use HasFactory, ScopedByCategory, SoftDeletes;

    public const STATUSES = ['scheduled', 'played', 'postponed', 'cancelled'];

    protected $table = 'matches';

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'match_date' => 'immutable_datetime',
            'meet_at' => 'immutable_datetime',
            'is_home' => 'boolean',
            'our_score' => 'integer',
            'opponent_score' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<PlayerAttendance, $this> */
    public function attendance(): HasMany
    {
        return $this->hasMany(PlayerAttendance::class, 'match_id');
    }
}
