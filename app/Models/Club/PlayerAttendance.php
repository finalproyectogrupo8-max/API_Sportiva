<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asistencia de un jugador a un partido o a un entrenamiento (uno de los dos).
 *
 * @property int $id
 * @property int $player_id
 * @property int|null $training_id
 * @property int|null $match_id
 * @property string $status
 * @property string|null $notes
 * @property int $recorded_by
 */
#[Fillable(['player_id', 'training_id', 'match_id', 'status', 'notes', 'recorded_by'])]
class PlayerAttendance extends Model
{
    public const STATUSES = ['present', 'absent', 'excused', 'late'];

    protected $table = 'player_attendance';

    protected $connection = 'tenant';

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
