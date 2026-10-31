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
 * @property int|null $competition_id
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
 * @property CarbonImmutable|null $callup_published_at
 * @property string|null $public_token
 * @property bool $updated_after_publish
 */
#[Fillable(['category_id', 'competition_id', 'opponent', 'match_date', 'meet_at', 'venue', 'maps_url', 'is_home', 'uniform', 'uniform_color', 'what_to_bring', 'our_score', 'opponent_score', 'status', 'notes', 'callup_published_at', 'public_token', 'updated_after_publish'])]
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
            'callup_published_at' => 'immutable_datetime',
            'updated_after_publish' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Competition, $this> */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /** @return HasMany<MatchCallup, $this> */
    public function callups(): HasMany
    {
        return $this->hasMany(MatchCallup::class, 'match_id');
    }

    /** @return HasMany<PlayerAttendance, $this> */
    public function attendance(): HasMany
    {
        return $this->hasMany(PlayerAttendance::class, 'match_id');
    }
}
