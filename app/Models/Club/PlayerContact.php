<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tutor o contacto del jugador (TIZ-15). El principal recibe los avisos de la convocatoria.
 *
 * @property int $id
 * @property int $player_id
 * @property string $name
 * @property string $relationship
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_primary
 */
#[Fillable(['player_id', 'name', 'relationship', 'phone', 'email', 'is_primary'])]
class PlayerContact extends Model
{
    public const RELATIONSHIPS = ['mother', 'father', 'guardian', 'self', 'other'];

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
