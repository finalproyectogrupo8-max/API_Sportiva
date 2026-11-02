<?php

namespace App\Club\Squads;

/**
 * Rama de la categoría y género del jugador (TIZ-40). Solo choca la contradicción explícita: un
 * jugador `male` en una categoría `female` o al revés. Sin dato, `mixed` u `other` no bloquean.
 */
final class BranchRule
{
    private const LABELS = ['female' => 'femenina', 'male' => 'masculina'];

    public static function conflicts(?string $branch, ?string $gender): bool
    {
        return ($branch === 'female' && $gender === 'male') || ($branch === 'male' && $gender === 'female');
    }

    /** "femenina" o "masculina", para los mensajes de error. */
    public static function label(string $branch): string
    {
        return self::LABELS[$branch] ?? $branch;
    }
}
