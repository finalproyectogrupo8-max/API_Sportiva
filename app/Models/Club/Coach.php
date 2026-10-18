<?php

namespace App\Models\Club;

use Database\Factories\Club\CoachFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['club_user_id', 'first_name', 'last_name', 'phone', 'email', 'status'])]
class Coach extends Model
{
    /** @use HasFactory<CoachFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
}
