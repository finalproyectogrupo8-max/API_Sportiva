<?php

namespace App\Models\Club;

use App\Support\MapsUrl;
use Carbon\CarbonImmutable;
use Database\Factories\Club\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sede del club: la cancha de la sede principal, un parque deportivo, etc. (TIZ-39).
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $is_active
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['name', 'address', 'latitude', 'longitude', 'is_active', 'notes'])]
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<GameMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function mapsUrl(): ?string
    {
        return MapsUrl::for($this->address, $this->latitude, $this->longitude);
    }

    /** Lo que se guarda como lugar del partido: "Sede principal · Av. Siempreviva 742". */
    public function label(): string
    {
        return $this->name.' · '.$this->address;
    }
}
