<?php

namespace App\Support;

use Illuminate\Contracts\Auth\CanResetPassword;

class PasswordResetUrl
{
    public static function make(CanResetPassword $user, string $token): string
    {
        $baseUrl = rtrim((string) config('app.front_url'), '/').'/restablecer-contrasena';
        $query = http_build_query([
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], '', '&', PHP_QUERY_RFC3986);

        return $baseUrl.'?'.$query;
    }
}
