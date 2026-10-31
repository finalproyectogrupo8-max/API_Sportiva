<?php

namespace App\Club\Callups;

use App\Models\Club\GameMatch;
use App\Models\Club\MatchCallup;
use App\Models\Tenant;
use Illuminate\Support\Collection;

/**
 * Links y textos de la convocatoria (TIZ-33). Los links apuntan a páginas del front
 * (`app.frontend_url`). Los usa WhatsApp hoy y el mail cuando exista TIZ-21, así dicen lo mismo.
 */
class CallupMessages
{
    public function publicUrl(GameMatch $match): ?string
    {
        return $match->public_token ? config('app.frontend_url').'/convocatoria/'.$match->public_token : null;
    }

    public function personalUrl(MatchCallup $callup): string
    {
        return config('app.frontend_url').'/convocatoria/responder/'.$callup->token;
    }

    /**
     * Mensaje para el grupo de WhatsApp de la categoría: los datos del partido, la lista de
     * convocados y el link para confirmar (así las respuestas llegan al sistema).
     *
     * @param  Collection<int, MatchCallup>  $callups  Ya ordenados por número.
     */
    public function groupMessage(GameMatch $match, Tenant $tenant, Collection $callups): string
    {
        $list = $callups->map(fn (MatchCallup $c) => ($c->shirt_number !== null ? $c->shirt_number.' · ' : '• ')
            .trim($c->player->first_name.' '.$c->player->last_name));

        return implode("\n", array_filter([
            "*Convocatoria {$match->category->name} · ".($tenant->short_name ?: $tenant->name).'*',
            $this->summary($match),
            '',
            "*Convocados ({$callups->count()})*",
            ...$list->all(),
            '',
            $this->publicUrl($match) ? '👉 Tocá tu nombre y confirmá si vas: '.$this->publicUrl($match) : null,
        ], fn ($line) => $line !== null));
    }

    /** Mensaje personal para el contacto principal del jugador, con su link para confirmar. */
    public function personalMessage(MatchCallup $callup, GameMatch $match): string
    {
        return implode("\n", [
            "¡Hola! {$callup->player->first_name} está convocado/a para el partido de {$match->category->name}.",
            $this->summary($match),
            'Confirmá si va: '.$this->personalUrl($callup),
        ]);
    }

    /** Link de WhatsApp al teléfono del contacto principal, o null si no tiene teléfono. */
    public function whatsappUrl(MatchCallup $callup, GameMatch $match): ?string
    {
        $phone = $callup->player->contacts->firstWhere('is_primary', true)?->phone;
        $digits = $phone ? preg_replace('/\D+/', '', $phone) : '';

        return $digits !== '' ? 'https://wa.me/'.$digits.'?text='.rawurlencode($this->personalMessage($callup, $match)) : null;
    }

    private function summary(GameMatch $match): string
    {
        // Las horas se guardan en UTC: se muestran en la zona del club.
        $tz = config('app.club_timezone');
        $date = $match->match_date->copy()->setTimezone($tz)->locale('es');
        $meet = $match->meet_at?->copy()->setTimezone($tz);

        return implode("\n", array_filter([
            ($match->is_home ? 'vs ' : 'en cancha de ').$match->opponent,
            ucfirst($date->isoFormat('dddd D [de] MMMM')).' · '
                .($meet ? 'Presentación '.$meet->format('H:i').' · ' : '')
                .'Partido '.$date->format('H:i'),
            $match->venue ? 'Lugar: '.$match->venue.($match->maps_url ? ' ('.$match->maps_url.')' : '') : null,
            $match->uniform ? 'Camiseta '.($match->uniform === 'home' ? 'titular' : 'alternativa').($match->uniform_color ? " ({$match->uniform_color})" : '') : null,
            $match->what_to_bring ? 'Llevar: '.$match->what_to_bring : null,
        ]));
    }
}
