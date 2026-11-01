<?php

namespace App\Club\Alerts;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use Illuminate\Support\Facades\DB;

/**
 * Resuelve los destinatarios de las alertas según rol y alcance (TIZ-27):
 * - El profe recibe lo de sus categorías (category_coaches).
 * - El coordinador recibe lo de su alcance (all o sport, TIZ-35).
 */
class AlertRecipients
{
    /**
     * @return list<int> IDs de club_users destinatarios
     */
    public function forCategory(Category $category): array
    {
        // 1. Profes con asignación vigente a la categoría
        $coachUserIds = DB::connection('tenant')
            ->table('category_coaches')
            ->join('coaches', 'coaches.id', '=', 'category_coaches.coach_id')
            ->join('club_users', 'club_users.id', '=', 'coaches.club_user_id')
            ->where('category_coaches.category_id', $category->id)
            ->whereNull('coaches.deleted_at')
            ->where('club_users.status', ClubUser::STATUS_ACTIVE)
            ->where(fn ($query) => $query
                ->whereNull('category_coaches.end_date')
                ->orWhere('category_coaches.end_date', '>=', today()->toDateString()))
            ->pluck('club_users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 2. Coordinadores generales (alcance all: admin y coordinator)
        $generalCoordinators = ClubUser::query()
            ->where('status', ClubUser::STATUS_ACTIVE)
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['admin', 'coordinator']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 3. Coordinadores del deporte de la categoría (alcance sport: sport_coordinator)
        $sportCoordinators = ClubUser::query()
            ->where('status', ClubUser::STATUS_ACTIVE)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'sport_coordinator'))
            ->whereHas('sports', fn ($q) => $q->where('sports.id', $category->sport_id))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique([...$coachUserIds, ...$generalCoordinators, ...$sportCoordinators]));
    }

    /**
     * @return list<int>
     */
    public function generalCoordinators(): array
    {
        return ClubUser::query()
            ->where('status', ClubUser::STATUS_ACTIVE)
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['admin', 'coordinator']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
