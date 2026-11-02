<?php

namespace App\Http\Requests\Club;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Reglas de la audiencia de un comunicado (TIZ-24), compartidas por el alta, la edición y la vista previa. */
final class AnnouncementAudienceRules
{
    /** @return array<string, mixed> */
    public static function rules(string $prefix = 'audience', bool $required = true): array
    {
        return [
            $prefix => [$required ? 'required' : 'sometimes', 'array'],
            /** Categorías: sus jugadores, sus tutores y/o sus profes. */
            "{$prefix}.categories" => ['sometimes', 'array', 'max:50'],
            "{$prefix}.categories.*.category_id" => ['required', 'integer', 'distinct', Rule::exists('tenant.categories', 'id')],
            "{$prefix}.categories.*.players" => ['sometimes', 'boolean'],
            "{$prefix}.categories.*.tutors" => ['sometimes', 'boolean'],
            "{$prefix}.categories.*.coaches" => ['sometimes', 'boolean'],
            /** Jugadores sueltos: ellos y/o sus tutores. */
            "{$prefix}.players" => ['sometimes', 'array'],
            "{$prefix}.players.player_ids" => ['required_with:'.$prefix.'.players', 'array', 'max:200'],
            "{$prefix}.players.player_ids.*" => ['integer', 'distinct', Rule::exists('tenant.players', 'id')->whereNull('deleted_at')],
            "{$prefix}.players.players" => ['sometimes', 'boolean'],
            "{$prefix}.players.tutors" => ['sometimes', 'boolean'],
            /** Staff con alguno de estos roles (solo con alcance sobre todo el club). */
            "{$prefix}.role_slugs" => ['sometimes', 'array', 'max:20'],
            "{$prefix}.role_slugs.*" => ['string', 'distinct', Rule::exists('tenant.roles', 'slug')],
            /** Usuarios del club sueltos (los lista `GET /api/club-users`). */
            "{$prefix}.club_user_ids" => ['sometimes', 'array', 'max:200'],
            "{$prefix}.club_user_ids.*" => ['integer', 'distinct', Rule::exists('tenant.club_users', 'id')],
        ];
    }

    /** Tiene que haber al menos un destino. */
    public static function requireTarget(Validator $validator, string $prefix = 'audience'): void
    {
        $audience = $validator->getData()[$prefix] ?? null;
        if (! is_array($audience)) {
            return;
        }

        $categories = collect($audience['categories'] ?? [])->contains(fn ($g) => ($g['players'] ?? false) || ($g['tutors'] ?? false) || ($g['coaches'] ?? false));
        $players = ($audience['players']['player_ids'] ?? []) !== [];
        $staff = ($audience['role_slugs'] ?? []) !== [] || ($audience['club_user_ids'] ?? []) !== [];

        if (! $categories && ! $players && ! $staff) {
            $validator->errors()->add($prefix, 'Elegí al menos un destinatario.');
        }
    }
}
