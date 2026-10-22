<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'changes',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Audit logs are immutable.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Audit logs are immutable.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
