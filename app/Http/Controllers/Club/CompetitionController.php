<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\CopyCompetitionRequest;
use App\Http\Requests\Club\StoreCompetitionRequest;
use App\Http\Resources\Club\CompetitionResource;
use App\Models\Club\Category;
use App\Models\Club\Competition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Ligas y torneos del club con sus requisitos de habilitación (TIZ-31). */
class CompetitionController extends Controller
{
    use ChecksClubScope;

    /** Filtros: `season_id`, `sport_id`, `category_id` y `search`. Paginado. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeClub('competitions.view');
        $filters = $request->validate([
            'season_id' => ['sometimes', 'integer'],
            'sport_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $competitions = Competition::query()
            ->visibleTo($this->access())
            ->with(['categories', 'requirements'])
            ->when($filters['season_id'] ?? null, fn ($query, $id) => $query->where('season_id', $id))
            ->when($filters['sport_id'] ?? null, fn ($query, $id) => $query->where('sport_id', $id))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->whereHas('categories', fn ($q) => $q->whereKey($id)))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return CompetitionResource::collection($competitions);
    }

    public function store(StoreCompetitionRequest $request): JsonResponse
    {
        $this->authorizeClub('competitions.create');
        $data = $request->validated();

        $competition = DB::connection('tenant')->transaction(function () use ($data) {
            $competition = Competition::create($data);
            $this->syncCategories($competition, $data['category_ids'] ?? []);
            $this->syncRequirements($competition, $data['requirements'] ?? []);

            return $competition;
        });

        return CompetitionResource::make($competition->refresh()->load(['categories', 'requirements']))->response()->setStatusCode(201);
    }

    public function show(Competition $competition): CompetitionResource
    {
        $this->authorizeClub('competitions.view');
        $this->ensureVisible($competition);

        return CompetitionResource::make($competition->load(['categories', 'requirements']));
    }

    public function update(StoreCompetitionRequest $request, Competition $competition): CompetitionResource
    {
        $this->authorizeClub('competitions.update');
        $data = $request->validated();

        DB::connection('tenant')->transaction(function () use ($competition, $data) {
            $competition->update($data);
            if (array_key_exists('category_ids', $data) || isset($data['sport_id'])) {
                $this->syncCategories($competition, $data['category_ids'] ?? $competition->categories()->pluck('categories.id')->all());
            }
            if (array_key_exists('requirements', $data)) {
                $this->syncRequirements($competition, $data['requirements']);
            }
        });

        return CompetitionResource::make($competition->refresh()->load(['categories', 'requirements']));
    }

    public function destroy(Competition $competition): Response
    {
        $this->authorizeClub('competitions.delete');

        $competition->delete();

        return response()->noContent();
    }

    /** Para la temporada siguiente: copia los requisitos, no las categorías (son de otra temporada). */
    public function copy(CopyCompetitionRequest $request, Competition $competition): JsonResponse
    {
        $this->authorizeClub('competitions.create');
        $data = $request->validated();

        $copy = DB::connection('tenant')->transaction(function () use ($competition, $data) {
            $copy = Competition::create([
                'sport_id' => $competition->sport_id,
                'season_id' => $data['season_id'],
                'name' => $data['name'] ?? $competition->name,
                'organizer' => $competition->organizer,
                'registration_deadline' => $data['registration_deadline'] ?? null,
            ]);
            foreach ($competition->requirements as $requirement) {
                $copy->requirements()->create($requirement->only(['name', 'validity_months', 'requires_file', 'minors_only', 'is_required', 'sort']));
            }

            return $copy;
        });

        return CompetitionResource::make($copy->load(['categories', 'requirements']))->response()->setStatusCode(201);
    }

    private function ensureVisible(Competition $competition): void
    {
        abort_unless(Competition::query()->visibleTo($this->access())->whereKey($competition->getKey())->exists(), 404);
    }

    /**
     * Las categorías tienen que ser del deporte de la competencia.
     *
     * @param  list<int>  $categoryIds
     */
    private function syncCategories(Competition $competition, array $categoryIds): void
    {
        $wrong = Category::query()->whereIn('id', $categoryIds)->where('sport_id', '!=', $competition->sport_id)->pluck('name');
        if ($wrong->isNotEmpty()) {
            throw ValidationException::withMessages([
                'category_ids' => 'Estas categorías son de otro deporte: '.$wrong->implode(', ').'.',
            ]);
        }

        $competition->categories()->sync($categoryIds);
    }

    /**
     * Reemplaza la lista: con id edita, sin id crea, y borra los que no vienen. El orden es el de la lista.
     *
     * @param  list<array<string, mixed>>  $requirements
     */
    private function syncRequirements(Competition $competition, array $requirements): void
    {
        $keep = [];
        foreach (array_values($requirements) as $sort => $item) {
            $attributes = [
                'name' => $item['name'],
                'validity_months' => $item['validity_months'] ?? null,
                'requires_file' => $item['requires_file'] ?? false,
                'minors_only' => $item['minors_only'] ?? false,
                'is_required' => $item['is_required'] ?? true,
                'sort' => $sort,
            ];

            $existing = isset($item['id']) ? $competition->requirements()->whereKey($item['id'])->first() : null;
            if (isset($item['id']) && ! $existing) {
                throw ValidationException::withMessages(["requirements.{$sort}.id" => 'El requisito no es de esta competencia.']);
            }

            $keep[] = $existing ? tap($existing)->update($attributes)->id : $competition->requirements()->create($attributes)->id;
        }

        $competition->requirements()->whereKeyNot($keep)->delete();
    }
}
