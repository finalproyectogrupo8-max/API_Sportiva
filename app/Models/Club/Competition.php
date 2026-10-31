<?php

namespace App\Models\Club;

use App\Club\Access\ClubAccess;
use Carbon\CarbonImmutable;
use Database\Factories\Club\CompetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Liga o torneo de un deporte en una temporada, con los requisitos para habilitar jugadores (TIZ-31).
 *
 * @property int $id
 * @property int $sport_id
 * @property int $season_id
 * @property string $name
 * @property string|null $organizer
 * @property CarbonImmutable|null $registration_deadline
 */
#[Fillable(['sport_id', 'season_id', 'name', 'organizer', 'registration_deadline'])]
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['registration_deadline' => 'immutable_date'];
    }

    /** @return BelongsTo<Sport, $this> */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_competitions');
    }

    /** @return HasMany<CompetitionRequirement, $this> */
    public function requirements(): HasMany
    {
        return $this->hasMany(CompetitionRequirement::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * Con alcance "sus categorías": solo las competencias que juega alguna de sus categorías.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ClubAccess $access): void
    {
        if ($access->isOwnScope('competitions.view')) {
            $query->whereHas('categories', fn (Builder $query) => $query->whereIn('categories.id', $access->ownCategoryIds() ?: [0]));
        }
    }
}
