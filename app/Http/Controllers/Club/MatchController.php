<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexEventRequest;
use App\Http\Requests\Club\StoreMatchRequest;
use App\Http\Resources\Club\MatchResource;
use App\Models\Club\Category;
use App\Models\Club\GameMatch;
use App\Models\Club\Venue;
use App\Support\MapsUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Fixture del club (TIZ-16). Con alcance "sus categorías", el profe ve y carga solo los de sus categorías. */
class MatchController extends Controller
{
    use ChecksClubScope;

    public function index(IndexEventRequest $request): AnonymousResourceCollection
    {
        $this->authorizeClub('matches.view');
        $filters = $request->validated();

        $matches = GameMatch::query()
            ->visibleTo($this->access(), 'matches.view')
            ->with('category')
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('match_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('match_date', '<', date('Y-m-d', strtotime($to.' +1 day'))))
            ->orderBy('match_date', ($filters['sort'] ?? 'date') === '-date' ? 'desc' : 'asc')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return MatchResource::collection($matches);
    }

    public function store(StoreMatchRequest $request): JsonResponse
    {
        $this->authorizeClub('matches.create');
        $data = $request->validated();
        $this->ensureCategoryInScope(Category::findOrFail($data['category_id']), 'matches.create');

        $match = GameMatch::create(['status' => 'scheduled', ...$this->withPlace($data)]);

        return MatchResource::make($match->refresh()->load('category'))->response()->setStatusCode(201);
    }

    public function show(GameMatch $match): MatchResource
    {
        $this->authorizeClub('matches.view');
        $this->ensureVisible($match, 'matches.view');

        return MatchResource::make($match->load('category'));
    }

    /** También carga el resultado: con los dos puntajes, el partido pasa a `played` salvo que se mande otro estado. */
    public function update(StoreMatchRequest $request, GameMatch $match): MatchResource
    {
        $this->authorizeClub('matches.update');
        $this->ensureVisible($match, 'matches.update');
        $data = $request->validated();
        if (isset($data['category_id'])) {
            $this->ensureCategoryInScope(Category::findOrFail($data['category_id']), 'matches.update');
        }

        $match->fill($this->withPlace($data, $match));
        // Convocatoria publicada y cambió algo que ya se avisó: hay que volver a avisar (TIZ-33).
        if ($match->callup_published_at && $match->isDirty(['match_date', 'meet_at', 'venue', 'maps_url', 'uniform', 'uniform_color'])) {
            $match->updated_after_publish = true;
        }
        if (! isset($data['status']) && $match->our_score !== null && $match->opponent_score !== null) {
            $match->status = 'played';
        }
        $match->save();

        return MatchResource::make($match->refresh()->load('category'));
    }

    public function destroy(GameMatch $match): Response
    {
        $this->authorizeClub('matches.delete');
        $this->ensureVisible($match, 'matches.delete');

        $match->delete();

        return response()->noContent();
    }

    private function ensureVisible(GameMatch $match, string $permission): void
    {
        abort_unless(GameMatch::query()->visibleTo($this->access(), $permission)->whereKey($match->getKey())->exists(), 404);
    }

    /**
     * Lugar del partido (TIZ-39). Con sede del club: el lugar y el link salen de la sede. Sin sede
     * y sin link: el link se arma con la dirección escrita (partido de visitante).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withPlace(array $data, ?GameMatch $match = null): array
    {
        if (! empty($data['venue_id'])) {
            $venue = Venue::findOrFail($data['venue_id']);

            return [...$data, 'venue' => $venue->label(), 'maps_url' => $venue->mapsUrl()];
        }

        if (array_key_exists('venue', $data) && empty($data['maps_url'])) {
            $data['maps_url'] = MapsUrl::for($data['venue']);
        }

        return $data;
    }
}
