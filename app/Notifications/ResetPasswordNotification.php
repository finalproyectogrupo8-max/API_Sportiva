<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
        $email = method_exists($notifiable, 'getEmailForPasswordReset')
            ? $notifiable->getEmailForPasswordReset()
            : $notifiable->email;

        $url = $frontendUrl.'/restablecer-contrasena?'.http_build_query([
            'token' => $this->token,
            'email' => $email,
        ]);

        $broker = config('auth.defaults.passwords', 'users');
        $expire = config("auth.passwords.{$broker}.expire", 60);

        return (new MailMessage)
            ->subject('Restablecé tu contraseña de Sportiva')
            ->line('Recibiste este correo porque se solicitó restablecer la contraseña de tu cuenta de Sportiva.')
            ->action('Restablecer contraseña', $url)
            ->line("Este enlace para restablecer tu contraseña caducará en {$expire} minutos.")
            ->line('Si no fuiste vos, ignorá este mensaje.');
    }
}
