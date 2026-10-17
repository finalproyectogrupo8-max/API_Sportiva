<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'database_name',
        'status',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }
}
