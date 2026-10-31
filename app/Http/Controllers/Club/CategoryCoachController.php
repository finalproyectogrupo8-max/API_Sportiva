<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\EndAssignmentRequest;
use App\Http\Requests\Club\StoreCategoryCoachRequest;
use App\Http\Resources\Club\CategoryCoachResource;
use App\Models\Club\Category;
use App\Models\Club\CategoryCoach;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Profes a cargo de una categoría (TIZ-15). Estas asignaciones definen el alcance "sus categorías"
 * de los permisos del profe.
 */
class CategoryCoachController extends Controller
{
    use ChecksClubScope;

    /** `?status=current` (por defecto) o `all`. */
    public function index(Request $request, Category $category): AnonymousResourceCollection
    {
        $this->authorizeClub('coaches.view');
        $this->ensureCategoryInScope($category, 'coaches.view');

        $status = $request->validate(['status' => ['sometimes', 'in:current,all']])['status'] ?? 'current';

        $assignments = CategoryCoach::query()
            ->where('category_id', $category->id)
            ->when($status === 'current', fn ($query) => $query->current())
            ->whereHas('coach')
            ->with('coach')
            ->orderBy('assignment_type')
            ->orderBy('id')
            ->get();

        return CategoryCoachResource::collection($assignments);
    }

    public function store(StoreCategoryCoachRequest $request, Category $category): JsonResponse
    {
        $this->authorizeClub('categories.manage_coaches');

        $data = $request->validated();

        $duplicate = CategoryCoach::query()
            ->where('category_id', $category->id)
            ->where('coach_id', $data['coach_id'])
            ->where('assignment_type', $data['assignment_type'])
            ->current()
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['coach_id' => 'El profe ya tiene esa asignación en la categoría.']);
        }

        $assignment = CategoryCoach::create([
            ...$data,
            'category_id' => $category->id,
            'start_date' => Carbon::parse($data['start_date'] ?? today())->toDateString(),
        ]);

        return CategoryCoachResource::make($assignment->load('coach'))->response()->setStatusCode(201);
    }

    public function end(EndAssignmentRequest $request, CategoryCoach $categoryCoach): CategoryCoachResource
    {
        $this->authorizeClub('categories.manage_coaches');

        $end = Carbon::parse($request->validated('end_date') ?? today());
        if ($categoryCoach->start_date && $end->lt($categoryCoach->start_date)) {
            throw ValidationException::withMessages(['end_date' => 'La fecha de cierre no puede ser anterior al inicio.']);
        }

        $categoryCoach->update(['end_date' => $end->toDateString()]);

        return CategoryCoachResource::make($categoryCoach->load('coach'));
    }
}
