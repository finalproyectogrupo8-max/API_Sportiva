<?php

namespace App\Http\Controllers;

use App\Models\Club\GameMatch;
use App\Models\Club\MatchCallup;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Páginas públicas de la convocatoria (TIZ-33), sin login: lo que ven el jugador y su familia al
 * tocar el link. Muestran lo mínimo: nunca DNI, contactos ni documentos, y de los convocados solo
 * nombre e inicial del apellido. Los tokens son "<club>.<aleatorio>": el club dice en qué base buscar.
 */
class PublicCallupController extends Controller
{
    public function __construct(private TenantDatabase $tenantDatabase) {}

    public function show(string $token): JsonResponse
    {
        $tenant = $this->connectClub($token);

        try {
            return response()->json(['data' => $this->page($tenant, $this->publishedMatch($token))]);
        } finally {
            $this->tenantDatabase->disconnect();
        }
    }

    public function showPersonal(string $token): JsonResponse
    {
        $tenant = $this->connectClub($token);

        try {
            $callup = $this->callup($token);

            return response()->json(['data' => ['page' => $this->page($tenant, $callup->match), 'me' => $this->me($callup)]]);
        } finally {
            $this->tenantDatabase->disconnect();
        }
    }

    /** "Voy" o "No puedo", con motivo opcional. Se puede cambiar la respuesta. */
    public function respond(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'response' => ['required', 'in:going,not_going'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['response.in' => 'Elegí "Voy" o "No puedo".']);

        $tenant = $this->connectClub($token);

        try {
            $callup = $this->callup($token);
            $callup->update(['response' => $data['response'], 'response_note' => $data['note'] ?? null, 'responded_at' => now()]);

            return response()->json(['data' => ['page' => $this->page($tenant, $callup->match), 'me' => $this->me($callup)]]);
        } finally {
            $this->tenantDatabase->disconnect();
        }
    }

    /** El partido como evento de calendario (.ics) para "Agregar al calendario". Acepta el token público o el personal. */
    public function calendar(string $token): Response
    {
        $tenant = $this->connectClub($token);

        try {
            // Sirve el link del grupo y también el personal de cada jugador.
            $match = GameMatch::query()->where('public_token', $token)->whereNotNull('callup_published_at')->first()
                ?? $this->callup($token)->match;
            $start = $match->meet_at ?? $match->match_date;
            $escape = fn (?string $text) => str_replace([',', ';', "\n"], ['\,', '\;', '\n'], (string) $text);
            $title = $match->category->name.' '.($match->is_home ? 'vs ' : 'en ').$match->opponent;

            $ics = implode("\r\n", [
                'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Sportiva//Convocatoria//ES', 'CALSCALE:GREGORIAN',
                'BEGIN:VEVENT',
                'UID:match-'.$match->id.'@'.$tenant->slug.'.sportiva',
                'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
                'DTSTART:'.$start->utc()->format('Ymd\THis\Z'),
                'DTEND:'.$match->match_date->addHours(2)->utc()->format('Ymd\THis\Z'),
                'SUMMARY:'.$escape(($tenant->short_name ?: $tenant->name).' · '.$title),
                'LOCATION:'.$escape($match->venue),
                'DESCRIPTION:'.$escape(($match->meet_at ? 'Presentación '.$match->meet_at->copy()->setTimezone(config('app.club_timezone'))->format('H:i').'. ' : '').($match->what_to_bring ? 'Llevar: '.$match->what_to_bring : '')),
                'END:VEVENT', 'END:VCALENDAR', '',
            ]);

            return response($ics, 200, [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="partido.ics"',
            ]);
        } finally {
            $this->tenantDatabase->disconnect();
        }
    }

    /** Apunta la conexión del club que dice el token. Quien llama desconecta. */
    private function connectClub(string $token): Tenant
    {
        $slug = str_contains($token, '.') ? strstr($token, '.', true) : null;
        $tenant = $slug ? Tenant::query()->where('slug', $slug)->where('status', 'active')->first() : null;
        abort_unless($tenant, 404);
        $this->tenantDatabase->connect($tenant);

        return $tenant;
    }

    private function publishedMatch(string $token): GameMatch
    {
        return GameMatch::query()->where('public_token', $token)->whereNotNull('callup_published_at')->with(['category', 'competition'])->firstOrFail();
    }

    private function callup(string $token): MatchCallup
    {
        $callup = MatchCallup::query()->where('token', $token)->with(['player', 'match.category', 'match.competition'])->firstOrFail();
        abort_unless($callup->match->callup_published_at !== null, 404);

        return $callup;
    }

    /**
     * @return array{club: array{name: string, short_name: string|null, primary_color: string|null, logo_url: string|null}, match: array{category: string, competition: string|null, opponent: string, is_home: bool, match_date: string, meet_at: string|null, venue: string|null, maps_url: string|null, uniform: string|null, uniform_color: string|null, what_to_bring: string|null, status: string, updated: bool}, players: list<array{name: string, shirt_number: int|null}>}
     */
    private function page(Tenant $tenant, GameMatch $match): array
    {
        $players = $match->callups()->with('player')->get()
            ->sortBy(fn (MatchCallup $c) => [$c->shirt_number ?? 999, $c->player->last_name])
            ->map(fn (MatchCallup $c) => [
                'name' => $c->player->first_name.' '.mb_substr($c->player->last_name, 0, 1).'.',
                'shirt_number' => $c->shirt_number,
            ])->values()->all();

        return [
            'club' => [
                'name' => $tenant->name,
                'short_name' => $tenant->short_name,
                'primary_color' => $tenant->primary_color,
                'logo_url' => $tenant->logo_path ? url("/api/public/clubs/{$tenant->slug}/logo") : null,
            ],
            'match' => [
                'category' => $match->category->name,
                'competition' => $match->competition?->name,
                'opponent' => $match->opponent,
                'is_home' => $match->is_home,
                'match_date' => $match->match_date->toIso8601String(),
                'meet_at' => $match->meet_at?->toIso8601String(),
                'venue' => $match->venue,
                'maps_url' => $match->maps_url,
                'uniform' => $match->uniform,
                'uniform_color' => $match->uniform_color,
                'what_to_bring' => $match->what_to_bring,
                'status' => $match->status,
                'updated' => $match->updated_after_publish,
            ],
            'players' => $players,
        ];
    }

    /** @return array{name: string, response: string, note: string|null} */
    private function me(MatchCallup $callup): array
    {
        return [
            'name' => $callup->player->first_name.' '.mb_substr($callup->player->last_name, 0, 1).'.',
            'response' => $callup->response,
            'note' => $callup->response_note,
        ];
    }
}
