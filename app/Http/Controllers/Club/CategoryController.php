<?php

namespace App\Http\Controllers\Club;

use App\Club\Squads\BranchRule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexCategoryRequest;
use App\Http\Requests\Club\StoreCategoryRequest;
use App\Http\Requests\Club\UpdateCategoryRequest;
use App\Http\Resources\Club\CategoryResource;
use App\Models\Club\Category;
use App\Models\Club\PlayerCategoryMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(IndexCategoryRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Category::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'name';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $categories = Category::query()
            ->visibleTo($request->user()->clubAccess())
            ->when($filters['sport_id'] ?? null, fn ($query, $sportId) => $query->where('sport_id', $sportId))
            ->when($filters['season_id'] ?? null, fn ($query, $seasonId) => $query->where('season_id', $seasonId))
            ->when($filters['branch'] ?? null, fn ($query, $branch) => $query->where('branch', $branch))
            ->when(array_key_exists('is_active', $filters), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', Category::class);

        $category = Category::create($request->validated());

        return CategoryResource::make($category->refresh())->response()->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        Gate::authorize('view', $category);

        // Fuera de "sus categorías" responde 404: no se revela que existe.
        abort_unless(Category::query()->visibleTo(request()->user()->clubAccess())->whereKey($category->getKey())->exists(), 404);

        return CategoryResource::make($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        Gate::authorize('update', $category);
        $this->ensureCanChange($category, 'categories.update');
        $data = $request->validated();

        // TIZ-40: no se puede pasar a una rama que contradiga el plantel activo.
        if (array_key_exists('branch', $data) && $data['branch'] !== $category->branch) {
            $this->ensureSquadFitsBranch($category, $data['branch']);
        }

        $category->update($data);

        return CategoryResource::make($category->refresh());
    }

    public function destroy(Category $category): Response
    {
        Gate::authorize('delete', $category);
        $this->ensureCanChange($category, 'categories.delete');

        $category->update(['is_active' => false]);

        return response()->noContent();
    }

    /** Fuera de su alcance responde 404; con alcance "sus deportes", solo cambia las de su deporte. */
    private function ensureCanChange(Category $category, string $permission): void
    {
        $access = request()->user()->clubAccess();
        abort_unless(Category::query()->visibleTo($access)->whereKey($category->getKey())->exists(), 404);
        abort_unless($access->allowsSport($permission, $category->sport_id), 403);
    }

    private function ensureSquadFitsBranch(Category $category, ?string $branch): void
    {
        $opposite = PlayerCategoryMembership::query()
            ->where('category_id', $category->id)
            ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
            ->with('player')
            ->get()
            ->first(fn (PlayerCategoryMembership $m) => BranchRule::conflicts($branch, $m->player?->gender));

        if ($opposite) {
            $name = trim($opposite->player->first_name.' '.$opposite->player->last_name);
            throw ValidationException::withMessages([
                'branch' => "{$category->name} no puede ser de rama ".BranchRule::label($branch).": {$name} está en el plantel.",
            ]);
        }
    }
}
