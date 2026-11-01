<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estado de un requisito de habilitación para un jugador (TIZ-32).
 *
 * @property int $id
 * @property int $player_id
 * @property int $competition_requirement_id
 * @property string $status
 * @property int|null $document_id
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $expires_at
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $rejection_reason
 */
#[Fillable(['player_id', 'competition_requirement_id', 'status', 'document_id', 'submitted_at', 'expires_at', 'reviewed_by', 'reviewed_at', 'rejection_reason'])]
class PlayerRequirement extends Model
{
    public const PENDING = 'pending';

    public const SUBMITTED = 'submitted';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const EXPIRED = 'expired';

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'submitted_at' => 'immutable_datetime',
            'expires_at' => 'immutable_date',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    /** El estado real: un aprobado con el vencimiento pasado cuenta como vencido aunque el comando diario no haya corrido. */
    public function effectiveStatus(): string
    {
        if ($this->status === self::APPROVED && $this->expires_at && $this->expires_at->lt(today())) {
            return self::EXPIRED;
        }

        return $this->status;
    }

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return BelongsTo<CompetitionRequirement, $this> */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(CompetitionRequirement::class, 'competition_requirement_id');
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
