<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreVenueRequest;
use App\Http\Resources\Club\VenueResource;
use App\Models\Club\Venue;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Sedes del club (TIZ-39): dónde juega de local. */
class VenueController extends Controller
{
    use ChecksClubScope;

    /** Filtro `is_active`. Sin paginar: un club tiene pocas sedes. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeClub('venues.view');
        $filters = $request->validate(['is_active' => ['sometimes', 'boolean']]);

        $venues = Venue::query()
            ->when(array_key_exists('is_active', $filters), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return VenueResource::collection($venues);
    }

    public function store(StoreVenueRequest $request): JsonResponse
    {
        $this->authorizeClub('venues.create');
        $venue = Venue::create($request->validated());

        return VenueResource::make($venue->refresh())->response()->setStatusCode(201);
    }

    public function show(Venue $venue): VenueResource
    {
        $this->authorizeClub('venues.view');

        return VenueResource::make($venue);
    }

    public function update(StoreVenueRequest $request, Venue $venue): VenueResource
    {
        $this->authorizeClub('venues.update');
        $venue->update($request->validated());

        return VenueResource::make($venue->refresh());
    }

    /** Con partidos por jugarse en la sede responde 409: primero hay que moverlos. */
    public function destroy(Venue $venue): Response|JsonResponse
    {
        $this->authorizeClub('venues.delete');

        if ($venue->matches()->where('status', 'scheduled')->where('match_date', '>=', now())->exists()) {
            return ApiError::conflict('resource_in_use');
        }

        $venue->delete();

        return response()->noContent();
    }
}
