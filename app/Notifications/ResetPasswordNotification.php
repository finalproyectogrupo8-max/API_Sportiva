<?php

namespace App\Notifications;

use App\Support\PasswordResetUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CanResetPassword $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Restablecé tu contraseña de Sportiva')
            ->markdown('mail.password-reset', [
                'resetUrl' => PasswordResetUrl::make($notifiable, $this->token),
            ]);
    }
}
