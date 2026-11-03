<?php

namespace App\Notifications;

use App\Support\UserInviteUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly ?string $clubName = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->clubName
            ? "Te invitaron a Sportiva — {$this->clubName}"
            : 'Te invitaron a Sportiva';

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.invite', [
                'clubName' => $this->clubName,
                'acceptUrl' => UserInviteUrl::make($notifiable, $this->token),
            ]);
    }
}
