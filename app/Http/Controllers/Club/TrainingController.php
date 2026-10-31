<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexEventRequest;
use App\Http\Requests\Club\StoreTrainingRequest;
use App\Http\Resources\Club\TrainingResource;
use App\Models\Club\Category;
use App\Models\Club\Training;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Entrenamientos (TIZ-16). Mismas reglas de alcance que los partidos. */
class TrainingController extends Controller
{
    use ChecksClubScope;

    public function index(IndexEventRequest $request): AnonymousResourceCollection
    {
        $this->authorizeClub('trainings.view');
        $filters = $request->validated();

        $trainings = Training::query()
            ->visibleTo($this->access(), 'trainings.view')
            ->with('category')
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('starts_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('starts_at', '<', date('Y-m-d', strtotime($to.' +1 day'))))
            ->orderBy('starts_at', ($filters['sort'] ?? 'date') === '-date' ? 'desc' : 'asc')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return TrainingResource::collection($trainings);
    }

    public function store(StoreTrainingRequest $request): JsonResponse
    {
        $this->authorizeClub('trainings.create');
        $data = $request->validated();
        $this->ensureCategoryInScope(Category::findOrFail($data['category_id']), 'trainings.create');

        $training = Training::create($data);

        return TrainingResource::make($training->refresh()->load('category'))->response()->setStatusCode(201);
    }

    public function show(Training $training): TrainingResource
    {
        $this->authorizeClub('trainings.view');
        $this->ensureVisible($training, 'trainings.view');

        return TrainingResource::make($training->load('category'));
    }

    public function update(StoreTrainingRequest $request, Training $training): TrainingResource
    {
        $this->authorizeClub('trainings.update');
        $this->ensureVisible($training, 'trainings.update');
        $data = $request->validated();
        if (isset($data['category_id'])) {
            $this->ensureCategoryInScope(Category::findOrFail($data['category_id']), 'trainings.update');
        }

        $training->update($data);

        return TrainingResource::make($training->refresh()->load('category'));
    }

    public function destroy(Training $training): Response
    {
        $this->authorizeClub('trainings.delete');
        $this->ensureVisible($training, 'trainings.delete');

        $training->delete();

        return response()->noContent();
    }

    private function ensureVisible(Training $training, string $permission): void
    {
        abort_unless(Training::query()->visibleTo($this->access(), $permission)->whereKey($training->getKey())->exists(), 404);
    }
}
