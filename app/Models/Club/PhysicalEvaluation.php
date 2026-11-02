<?php

namespace App\Models\Club;

use App\Models\Club\Concerns\ScopedByCategory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Evaluación física de un jugador (TIZ-20): quién lo evaluó, cuándo y en qué categoría estaba.
 * Cada prueba es una medición (`physical_measurements`).
 *
 * @property int $id
 * @property int $player_id
 * @property int|null $category_id
 * @property int $evaluator_coach_id
 * @property CarbonImmutable $evaluated_at
 * @property string|null $summary
 * @property string|null $recommendations
 */
#[Fillable(['player_id', 'category_id', 'evaluator_coach_id', 'evaluated_at', 'summary', 'recommendations'])]
class PhysicalEvaluation extends Model
{
    use ScopedByCategory, SoftDeletes;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['evaluated_at' => 'immutable_datetime'];
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

    /** @return BelongsTo<Coach, $this> */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'evaluator_coach_id');
    }

    /** @return HasMany<PhysicalMeasurement, $this> */
    public function measurements(): HasMany
    {
        return $this->hasMany(PhysicalMeasurement::class, 'evaluation_id')->orderBy('id');
    }
}
