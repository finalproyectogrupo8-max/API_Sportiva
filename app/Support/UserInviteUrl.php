<?php

namespace App\Support;

use Illuminate\Contracts\Auth\CanResetPassword;

class UserInviteUrl
{
    public static function make(CanResetPassword $user, string $token): string
    {
        $frontUrl = (string) (config('app.front_url') ?: config('app.frontend_url') ?: 'http://localhost:3000');
        $baseUrl = rtrim($frontUrl, '/').'/aceptar-invitacion';

        $query = http_build_query([
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], '', '&', PHP_QUERY_RFC3986);

        return $baseUrl.'?'.$query;
    }
}
