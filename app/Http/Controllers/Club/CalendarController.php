<?php

namespace App\Http\Controllers\Club;

use App\Club\Calendar\CalendarEvent;
use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\CalendarRequest;
use App\Http\Resources\Club\CalendarEventResource;
use App\Models\Club\GameMatch;
use App\Models\Club\Training;
use Carbon\CarbonInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Partidos y entrenamientos juntos para la vista de calendario (TIZ-16). Cada parte respeta su permiso. */
class CalendarController extends Controller
{
    use ChecksClubScope;

    public function __invoke(CalendarRequest $request): AnonymousResourceCollection
    {
        $access = $this->access();
        abort_unless($access->has('matches.view') || $access->has('trainings.view'), 403);

        $from = $request->date('from')->startOfDay();
        $to = $request->date('to')->addDay()->startOfDay();
        $categoryId = $request->validated('category_id');

        $events = [];

        if ($access->has('matches.view')) {
            $matches = GameMatch::query()
                ->visibleTo($access, 'matches.view')
                ->with('category')
                ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                ->where('match_date', '>=', $from)->where('match_date', '<', $to)
                ->get();
            foreach ($matches as $match) {
                $events[] = $this->event('match', $match->id, $match->category_id, $match->category->name,
                    ($match->is_home ? 'vs ' : 'en ').$match->opponent, $match->match_date, null, $match->venue, $match->status);
            }
        }

        if ($access->has('trainings.view')) {
            $trainings = Training::query()
                ->visibleTo($access, 'trainings.view')
                ->with('category')
                ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                ->where('starts_at', '>=', $from)->where('starts_at', '<', $to)
                ->get();
            foreach ($trainings as $training) {
                $events[] = $this->event('training', $training->id, $training->category_id, $training->category->name,
                    'Entrenamiento', $training->starts_at, $training->ends_at, $training->location, null);
            }
        }

        usort($events, fn (CalendarEvent $a, CalendarEvent $b) => strcmp($a->starts_at, $b->starts_at));

        return CalendarEventResource::collection($events);
    }

    private function event(string $type, int $id, int $categoryId, string $categoryName, string $title, CarbonInterface $startsAt, ?CarbonInterface $endsAt, ?string $location, ?string $status): CalendarEvent
    {
        return new CalendarEvent($type, $id, $categoryId, $categoryName, $title, $startsAt->toIso8601String(), $endsAt?->toIso8601String(), $location, $status);
    }
}
