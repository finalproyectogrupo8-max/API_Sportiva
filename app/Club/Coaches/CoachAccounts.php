<?php

namespace App\Club\Coaches;

use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\User;

/**
 * Vincula a cada usuario con rol de profe o preparador físico con su ficha de profe. Las
 * categorías a cargo salen de la ficha: sin ficha vinculada, el profe entra pero no tiene
 * categorías y no puede cargar partidos, asistencia ni evaluaciones. Requiere la conexión
 * "tenant" apuntando al club.
 */
class CoachAccounts
{
    /** Roles que trabajan con categorías a cargo. */
    public const ROLES = ['coach', 'physical_trainer'];

    /**
     * Si el usuario tiene un rol de profe, se asegura de que tenga su ficha: la ya vinculada, una
     * sin vincular con su mail, o una nueva con su nombre y mail.
     */
    public function ensureFor(ClubUser $clubUser, User $user): ?Coach
    {
        if (! $clubUser->roles()->whereIn('slug', self::ROLES)->exists()) {
            return null;
        }

        $linked = Coach::query()->where('club_user_id', $clubUser->id)->first();
        if ($linked) {
            return $linked;
        }

        $byEmail = Coach::query()
            ->whereNull('club_user_id')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($user->email)])
            ->first();
        if ($byEmail) {
            $byEmail->update(['club_user_id' => $clubUser->id]);

            return $byEmail;
        }

        [$firstName, $lastName] = $this->splitName($clubUser->display_name ?: $user->name);

        return Coach::create([
            'club_user_id' => $clubUser->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
            'status' => 'active',
        ]);
    }

    /**
     * Al cargar una ficha con el mail de un usuario del club que todavía no tiene ficha, la vincula.
     */
    public function linkByEmail(Coach $coach): void
    {
        if ($coach->club_user_id !== null || ! $coach->email) {
            return;
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($coach->email)])->first();
        $clubUser = $user ? ClubUser::query()->where('global_user_id', $user->id)->first() : null;

        if ($clubUser && ! Coach::query()->where('club_user_id', $clubUser->id)->exists()) {
            $coach->update(['club_user_id' => $clubUser->id]);
        }
    }

    /**
     * Para los usuarios que ya tenían rol de profe sin ficha vinculada (antes de este arreglo).
     * Idempotente. Devuelve cuántas fichas vinculó o creó.
     */
    public function backfill(): int
    {
        $count = 0;

        $clubUsers = ClubUser::query()
            ->where('status', ClubUser::STATUS_ACTIVE)
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', self::ROLES))
            ->whereNotIn('id', Coach::query()->whereNotNull('club_user_id')->select('club_user_id'))
            ->get();

        foreach ($clubUsers as $clubUser) {
            $user = User::query()->find($clubUser->global_user_id);
            if ($user && $this->ensureFor($clubUser, $user)) {
                $count++;
            }
        }

        return $count;
    }

    /** @return array{string, string} nombre y apellido */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? $name, $parts[1] ?? ''];
    }
}
