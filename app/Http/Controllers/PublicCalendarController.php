<?php

namespace App\Http\Controllers;

use App\Models\Club\Category;
use App\Models\Club\GameMatch;
use App\Models\Club\Training;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

/**
 * Calendario suscribible de una categoría (FRO-32), sin login: partidos y entrenamientos de los
 * últimos 2 meses y los próximos 6. Solo fecha, hora, rival y lugar: ningún dato personal.
 */
class PublicCalendarController extends Controller
{
    public function __construct(private TenantDatabase $tenantDatabase) {}

    public function show(string $token): Response
    {
        $slug = str_contains($token, '.') ? strstr($token, '.', true) : null;
        $tenant = $slug ? Tenant::query()->where('slug', $slug)->where('status', 'active')->first() : null;
        abort_unless($tenant, 404);
        $this->tenantDatabase->connect($tenant);

        try {
            $category = Category::query()->where('calendar_token', $token)->first();
            abort_unless($category, 404);

            $from = now()->subMonths(2);
            $to = now()->addMonths(6);
            $club = $tenant->short_name ?: $tenant->name;
            $tz = config('app.club_timezone');

            $events = [];
            foreach (GameMatch::query()->where('category_id', $category->id)->whereBetween('match_date', [$from, $to])->get() as $m) {
                $events[] = $this->event(
                    uid: "match-{$m->id}@{$tenant->slug}.sportiva",
                    start: $m->meet_at ?? $m->match_date,
                    end: $m->match_date->copy()->addHours(2),
                    summary: "{$club} · {$category->name} ".($m->is_home ? 'vs ' : 'en ').$m->opponent,
                    location: $m->venue,
                    description: trim(($m->meet_at ? 'Presentación '.$m->meet_at->copy()->setTimezone($tz)->format('H:i').'. Partido '.$m->match_date->copy()->setTimezone($tz)->format('H:i').'. ' : '').($m->what_to_bring ? 'Llevar: '.$m->what_to_bring : '')),
                    cancelled: in_array($m->status, ['cancelled', 'postponed'], true),
                    updated: $m->updated_at,
                );
            }
            foreach (Training::query()->where('category_id', $category->id)->whereBetween('starts_at', [$from, $to])->get() as $t) {
                $events[] = $this->event(
                    uid: "training-{$t->id}@{$tenant->slug}.sportiva",
                    start: $t->starts_at,
                    end: $t->ends_at ?? $t->starts_at->copy()->addMinutes(90),
                    summary: "{$club} · Entrenamiento {$category->name}",
                    location: $t->location,
                    description: '',
                    cancelled: false,
                    updated: $t->updated_at,
                );
            }

            $ics = implode("\r\n", [
                'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Sportiva//Calendario//ES', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
                'X-WR-CALNAME:'.$this->escape("{$club} · {$category->name}"),
                'X-WR-TIMEZONE:'.$tz,
                // Pide a los clientes que se actualicen cada pocas horas.
                'REFRESH-INTERVAL;VALUE=DURATION:PT3H', 'X-PUBLISHED-TTL:PT3H',
                ...$events,
                'END:VCALENDAR', '',
            ]);

            return response($ics, 200, [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'inline; filename="calendario.ics"',
                'Cache-Control' => 'public, max-age=900',
            ]);
        } finally {
            $this->tenantDatabase->disconnect();
        }
    }

    private function event(string $uid, CarbonInterface $start, CarbonInterface $end, string $summary, ?string $location, string $description, bool $cancelled, ?CarbonInterface $updated): string
    {
        return implode("\r\n", array_filter([
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.($updated ?? now())->copy()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$end->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape(($cancelled ? 'SUSPENDIDO · ' : '').$summary),
            $location ? 'LOCATION:'.$this->escape($location) : null,
            $description ? 'DESCRIPTION:'.$this->escape($description) : null,
            $cancelled ? 'STATUS:CANCELLED' : 'STATUS:CONFIRMED',
            'END:VEVENT',
        ]));
    }

    private function escape(?string $text): string
    {
        return str_replace(['\\', ',', ';', "\n"], ['\\\\', '\,', '\;', '\n'], (string) $text);
    }
}
