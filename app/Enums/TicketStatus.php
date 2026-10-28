<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case Resolved = 'resolved';
    case Discarded = 'discarded';
}
