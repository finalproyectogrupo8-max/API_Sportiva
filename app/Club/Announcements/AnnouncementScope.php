<?php

namespace App\Club\Announcements;

use App\Club\Access\ClubAccess;
use App\Models\Club\PlayerCategoryMembership;

/**
 * Alcance de la audiencia de un comunicado (TIZ-24). Con `announcements.send` limitado (profe: sus
 * categorías; coordinador de deporte: sus deportes) solo se eligen categorías y jugadores del
 * alcance, y nunca staff por rol ni usuarios del club. Fuera de alcance: 403.
 */
class AnnouncementScope
{
    /** @param  array<string, mixed>  $audience */
    public static function check(ClubAccess $access, array $audience): void
    {
        $allowed = $access->scopedCategoryIds('announcements.send');
        if ($allowed === null) {
            return;
        }

        abort_if(($audience['role_slugs'] ?? []) !== [] || ($audience['club_user_ids'] ?? []) !== [], 403,
            'Solo quien tiene alcance sobre todo el club puede escribirle al staff.');

        $categoryIds = array_column($audience['categories'] ?? [], 'category_id');
        abort_if(array_diff($categoryIds, $allowed) !== [], 403, 'Incluiste una categoría fuera de tu alcance.');

        $playerIds = $audience['players']['player_ids'] ?? [];
        if ($playerIds !== []) {
            $inScope = PlayerCategoryMembership::query()
                ->whereIn('player_id', $playerIds)
                ->whereIn('category_id', $allowed)
                ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
                ->distinct()->pluck('player_id')->all();
            abort_if(array_diff($playerIds, $inScope) !== [], 403, 'Incluiste un jugador fuera de tu alcance.');
        }
    }
}
