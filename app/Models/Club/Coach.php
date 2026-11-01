<?php

namespace App\Models\Club;

use App\Club\Access\ClubAccess;
use Carbon\CarbonImmutable;
use Database\Factories\Club\CoachFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Profe del club. Puede estar asociado a un usuario del club (club_user_id).
 *
 * @property int $id
 * @property int|null $club_user_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string|null $email
 * @property string $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['club_user_id', 'first_name', 'last_name', 'phone', 'email', 'status'])]
class Coach extends Model
{
    /** @use HasFactory<CoachFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    /** @return HasMany<CategoryCoach, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(CategoryCoach::class);
    }

    /** Asignaciones vigentes (sin fecha de fin o con fin desde hoy). @return HasMany<CategoryCoach, $this> */
    public function currentAssignments(): HasMany
    {
        return $this->assignments()->current();
    }

    /**
     * Con coaches.view en alcance sport u own: su propia ficha y los profes de sus deportes/categorías
     * (asignación vigente). Con alcance all no filtra.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ClubAccess $access): void
    {
        $categoryIds = $access->scopedCategoryIds('coaches.view');

        if ($categoryIds === null) {
            return;
        }

        $clubUserId = $access->clubUser?->id;

        $query->where(fn (Builder $query) => $query
            ->when($clubUserId, fn (Builder $query) => $query->where('club_user_id', $clubUserId))
            ->orWhereIn('id', fn ($sub) => $sub->select('coach_id')
                ->from('category_coaches')
                ->whereIn('category_id', $categoryIds ?: [0])
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', today()->toDateString()))));
    }
}
