<?php

namespace App\Models\Club;

use App\Club\Access\ClubAccess;
use Carbon\CarbonImmutable;
use Database\Factories\Club\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ficha del jugador. Tiene datos personales, muchas veces de menores (D15).
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property CarbonImmutable|null $birth_date
 * @property string|null $document_number
 * @property string|null $registration_number
 * @property string|null $preferred_position
 * @property string|null $dominant_side
 * @property string $status
 * @property string|null $notes
 */
#[Fillable(['first_name', 'last_name', 'birth_date', 'document_number', 'registration_number', 'preferred_position', 'dominant_side', 'status', 'notes'])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = ['active', 'inactive', 'transferred'];

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['birth_date' => 'immutable_date'];
    }

    /** @return HasMany<PlayerCategoryMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(PlayerCategoryMembership::class);
    }

    /** @return HasMany<PlayerCategoryMembership, $this> */
    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', PlayerCategoryMembership::STATUS_ACTIVE);
    }

    /** @return MorphMany<Document, $this> */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /** @return MorphOne<Document, $this> */
    public function photo(): MorphOne
    {
        return $this->morphOne(Document::class, 'documentable')->where('kind', 'photo')->latestOfMany();
    }

    /** @return HasMany<PlayerContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(PlayerContact::class);
    }

    /**
     * Con alcance "sus categorías" en el permiso dado: solo los jugadores con una asignación activa
     * en alguna de las categorías del usuario. Con alcance all no filtra.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ClubAccess $access, string $permission = 'players.view'): void
    {
        if (! $access->isOwnScope($permission)) {
            return;
        }

        $query->whereHas('activeMemberships', fn (Builder $query) => $query
            ->whereIn('category_id', $access->ownCategoryIds() ?: [0]));
    }
}
