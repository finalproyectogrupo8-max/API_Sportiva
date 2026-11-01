<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un jugador convocado a un partido (TIZ-33).
 *
 * @property int $id
 * @property int $match_id
 * @property int $player_id
 * @property int|null $shirt_number
 * @property string $response
 * @property string|null $response_note
 * @property CarbonImmutable|null $responded_at
 * @property string $token
 */
#[Fillable(['match_id', 'player_id', 'shirt_number', 'response', 'response_note', 'responded_at', 'token'])]
class MatchCallup extends Model
{
    public const PENDING = 'pending';

    public const GOING = 'going';

    public const NOT_GOING = 'not_going';

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['shirt_number' => 'integer', 'responded_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return BelongsTo<GameMatch, $this> */
    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
