<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexPlayerRequest;
use App\Http\Requests\Club\StorePlayerRequest;
use App\Http\Requests\Club\UpdatePlayerRequest;
use App\Http\Resources\Club\PlayerResource;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** ABM de jugadores (TIZ-15). Con alcance "sus categorías", el profe ve solo los de sus categorías. */
class PlayerController extends Controller
{
    use ChecksClubScope;

    public function index(IndexPlayerRequest $request): AnonymousResourceCollection
    {
        $this->authorizeClub('players.view');

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'last_name';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $players = Player::query()
            ->visibleTo($this->access())
            ->with('activeMemberships.category')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('document_number', 'like', "%{$search}%")
                ->orWhere('registration_number', 'like', "%{$search}%")))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->whereHas(
                'activeMemberships',
                fn (Builder $query) => $query->where('category_id', $categoryId),
            ))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return PlayerResource::collection($players);
    }

    public function store(StorePlayerRequest $request): JsonResponse
    {
        $this->authorizeClub('players.create');

        $player = Player::create(['status' => 'active', ...$request->validated()]);

        return PlayerResource::make($player->refresh())->response()->setStatusCode(201);
    }

    public function show(Player $player): PlayerResource
    {
        $this->authorizeClub('players.view');
        $this->ensurePlayerVisible($player);

        return PlayerResource::make($player->load('activeMemberships.category'));
    }

    public function update(UpdatePlayerRequest $request, Player $player): PlayerResource
    {
        $this->authorizeClub('players.update');
        $this->ensurePlayerVisible($player, 'players.update');

        $player->update($request->validated());

        return PlayerResource::make($player->refresh()->load('activeMemberships.category'));
    }

    /** Baja lógica. Las asignaciones activas se cierran: el jugador deja de figurar en el plantel. */
    public function destroy(Player $player): Response
    {
        $this->authorizeClub('players.delete');
        $this->ensurePlayerVisible($player, 'players.delete');

        $player->activeMemberships()->update([
            'status' => PlayerCategoryMembership::STATUS_ENDED,
            'end_date' => today()->toDateString(),
        ]);
        $player->delete();

        return response()->noContent();
    }
}
