<?php

namespace App\Models\Club;

use App\Club\Access\ClubAccess;
use Carbon\CarbonImmutable;
use Database\Factories\Club\SeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Temporada. `status`: planned, active o closed. Con `sport_id` es de ese deporte; sin deporte es de
 * todo el club y la usan todos los deportes.
 *
 * @property int $id
 * @property int|null $sport_id
 * @property string $name
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property string $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['sport_id', 'name', 'start_date', 'end_date', 'status'])]
class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /** Si un registro de ese deporte puede ir en esta temporada: la del club sirve para todos. */
    public function acceptsSport(int $sportId): bool
    {
        return $this->sport_id === null || $this->sport_id === $sportId;
    }

    /**
     * Con alcance "sus deportes": las temporadas del club y las de sus deportes. Con alcance "todo el
     * club" no filtra.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ClubAccess $access, string $permission = 'seasons.view'): void
    {
        $sportIds = $access->scopedSportIds($permission);

        if ($sportIds !== null) {
            $query->where(fn (Builder $q) => $q->whereNull('sport_id')->orWhereIn('sport_id', $sportIds ?: [0]));
        }
    }
}
