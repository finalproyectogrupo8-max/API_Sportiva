<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una dirección de un comunicado enviado (TIZ-24). Contiene datos personales: nunca va a los logs
 * ni al payload de los jobs.
 *
 * @property int $id
 * @property int $announcement_id
 * @property string $email
 * @property string $name
 * @property list<array{type: string, id: int}> $sources
 * @property string $status `pending`, `sent` o `failed`.
 * @property string|null $error
 * @property CarbonImmutable|null $sent_at
 */
#[Fillable(['announcement_id', 'email', 'name', 'sources', 'status', 'error', 'sent_at'])]
class AnnouncementRecipient extends Model
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['sources' => 'array', 'sent_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Announcement, $this> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
