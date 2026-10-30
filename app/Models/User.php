<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Club\Access\ClubAccess;
use App\Models\Club\ClubUser;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /**
     * Roles y permisos del usuario en el club del request (el del token, que EnsureTenantContext
     * deja en el atributo "tenant"). Se calcula una vez por request. Fuera de un club, no tiene permisos.
     */
    public function clubAccess(): ClubAccess
    {
        $request = request();

        if (! $request->attributes->get('tenant')) {
            return ClubAccess::none();
        }

        $cached = $request->attributes->get('club_access');
        if ($cached instanceof ClubAccess && $request->attributes->get('club_access_user') === $this->id) {
            return $cached;
        }

        $access = ClubAccess::forGlobalUser($this->id);
        $request->attributes->set('club_access', $access);
        $request->attributes->set('club_access_user', $this->id);

        return $access;
    }

    public function hasClubPermission(string $permission): bool
    {
        return $this->clubAccess()->has($permission);
    }

    /** all (todo el club), own (sus categorías) o null si no tiene el permiso. */
    public function clubPermissionScope(string $permission): ?string
    {
        return $this->clubAccess()->scope($permission);
    }

    /** El perfil local en el club actual: para requested_by, performed_by, recorded_by... */
    public function currentClubUser(): ?ClubUser
    {
        return $this->clubAccess()->clubUser;
    }
}
