<?php

namespace App\Club\Alerts\Channels;

use App\Models\Club\Notification;

/**
 * Canal por defecto en esta versión: persiste las notificaciones en la tabla `notifications` del club.
 */
class DatabaseNotificationChannel implements NotificationChannel
{
    /**
     * @param  list<int>  $recipientClubUserIds
     * @param  array<string, mixed>  $data
     */
    public function send(
        array $recipientClubUserIds,
        string $type,
        string $title,
        string $body,
        array $data = [],
    ): void {
        $recipientClubUserIds = array_values(array_unique($recipientClubUserIds));

        if ($recipientClubUserIds === []) {
            return;
        }

        $now = now();
        $payload = array_map(fn (int $clubUserId) => [
            'club_user_id' => $clubUserId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data !== [] ? json_encode($data) : null,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $recipientClubUserIds);

        Notification::query()->insert($payload);
    }
}
