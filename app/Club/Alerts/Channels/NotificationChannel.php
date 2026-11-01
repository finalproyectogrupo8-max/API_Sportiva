<?php

namespace App\Club\Alerts\Channels;

/**
 * Contrato para el envío de notificaciones de alertas.
 * Permite sumar canales futuros (email, push, WhatsApp) sin modificar las reglas de alertas.
 */
interface NotificationChannel
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
    ): void;
}
