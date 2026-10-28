<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'club_name',
        'subject',
        'message',
        'status',
        'admin_notes',
        'handled_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

