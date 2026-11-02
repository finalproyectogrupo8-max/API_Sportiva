<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comunicado por mail del club (TIZ-24). Se edita y se borra solo en `draft`.
 *
 * @property int $id
 * @property string $subject
 * @property string $body Markdown.
 * @property array<string, mixed> $audience
 * @property string $status `draft`, `sending` o `sent`.
 * @property int $created_by
 * @property int|null $sent_by
 * @property CarbonImmutable|null $sent_at
 * @property int $recipients_count
 * @property int $sent_count
 * @property int $failed_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['subject', 'body', 'audience', 'status', 'created_by', 'sent_by', 'sent_at', 'recipients_count', 'sent_count', 'failed_count'])]
class Announcement extends Model
{
    use SoftDeletes;

    public const DRAFT = 'draft';

    public const SENDING = 'sending';

    public const SENT = 'sent';

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'sent_at' => 'immutable_datetime',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<AnnouncementRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    /** @return BelongsTo<ClubUser, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'created_by');
    }

    /** @return BelongsTo<ClubUser, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'sent_by');
    }
}
