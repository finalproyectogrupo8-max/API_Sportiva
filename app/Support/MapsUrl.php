<?php

namespace App\Support;

/** Link de Google Maps a un lugar, sin API key: por coordenadas si las hay, si no por dirección. */
final class MapsUrl
{
    public static function for(?string $address, float|string|null $latitude = null, float|string|null $longitude = null): ?string
    {
        if ($latitude !== null && $longitude !== null) {
            return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(((float) $latitude).','.((float) $longitude));
        }

        $address = trim((string) $address);

        return $address === '' ? null : 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address);
    }
}
