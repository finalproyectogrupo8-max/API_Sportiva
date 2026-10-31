<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\EndAssignmentRequest;
use App\Http\Requests\Club\StoreMembershipRequest;
use App\Http\Requests\Club\UpdateMembershipRequest;
use App\Http\Resources\Club\MembershipResource;
use App\Models\Club\Category;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Plantel de una categoría (TIZ-15). El historial no se borra: un pase de categoría cierra la
 * asignación anterior y abre otra.
 */
class CategoryMembershipController extends Controller
{
    use ChecksClubScope;

    /** `?status=active` (por defecto), `ended` o `all`. */
    /** Historial de categorías del jugador: vigentes y cerradas, de la más reciente a la más vieja. */
    public function forPlayer(Player $player): AnonymousResourceCollection
    {
        $this->authorizeClub('players.view');
        $this->ensurePlayerVisible($player);

        $memberships = $player->memberships()
            ->with('category')
            ->orderByRaw("status = 'active' desc")
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return MembershipResource::collection($memberships);
    }

    public function index(Request $request, Category $category): AnonymousResourceCollection
    {
        $this->authorizeClub('players.view');
        $this->ensureCategoryInScope($category, 'players.view');

        $status = $request->validate(['status' => ['sometimes', 'in:active,ended,all']])['status'] ?? 'active';

        $memberships = PlayerCategoryMembership::query()
            ->where('category_id', $category->id)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->whereHas('player')
            ->with('player')
            ->orderByRaw('shirt_number is null')
            ->orderBy('shirt_number')
            ->orderBy('id')
            ->get();

        return MembershipResource::collection($memberships);
    }

    public function store(StoreMembershipRequest $request, Category $category): JsonResponse
    {
        $this->authorizeClub('categories.manage_players');
        $this->ensureCategoryInScope($category, 'categories.manage_players');

        $data = $request->validated();
        $start = Carbon::parse($data['start_date'] ?? today())->toDateString();

        $membership = DB::connection('tenant')->transaction(function () use ($data, $category, $start) {
            $active = PlayerCategoryMembership::query()
                ->where('player_id', $data['player_id'])
                ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
                ->with('category')
                ->lockForUpdate()
                ->get();

            if ($active->contains('category_id', $category->id)) {
                throw ValidationException::withMessages(['player_id' => 'El jugador ya está en esta categoría.']);
            }

            // Un jugador juega una sola categoría por deporte a la vez.
            $sameSport = $active->first(fn (PlayerCategoryMembership $m) => $m->category->sport_id === $category->sport_id);
            if ($sameSport && ! ($data['close_previous'] ?? false)) {
                throw ValidationException::withMessages([
                    'player_id' => "El jugador ya juega en {$sameSport->category->name} de este deporte. Cerrá esa asignación o mandá close_previous.",
                ]);
            }

            $this->ensureShirtNumberIsFree($category->id, $data['shirt_number'] ?? null);

            $sameSport?->update(['status' => PlayerCategoryMembership::STATUS_ENDED, 'end_date' => $start]);

            return PlayerCategoryMembership::create([
                'player_id' => $data['player_id'],
                'category_id' => $category->id,
                'start_date' => $start,
                'shirt_number' => $data['shirt_number'] ?? null,
                'status' => PlayerCategoryMembership::STATUS_ACTIVE,
            ]);
        });

        return MembershipResource::make($membership->load('player', 'category'))->response()->setStatusCode(201);
    }

    public function update(UpdateMembershipRequest $request, PlayerCategoryMembership $membership): MembershipResource
    {
        $this->authorizeClub('categories.manage_players');
        $this->ensureCategoryInScope($membership->category, 'categories.manage_players');

        $number = $request->validated('shirt_number');
        if ($membership->status === PlayerCategoryMembership::STATUS_ACTIVE) {
            $this->ensureShirtNumberIsFree($membership->category_id, $number, $membership->id);
        }
        $membership->update(['shirt_number' => $number]);

        return MembershipResource::make($membership->load('player', 'category'));
    }

    public function end(EndAssignmentRequest $request, PlayerCategoryMembership $membership): MembershipResource
    {
        $this->authorizeClub('categories.manage_players');
        $this->ensureCategoryInScope($membership->category, 'categories.manage_players');

        if ($membership->status !== PlayerCategoryMembership::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['membership' => 'La asignación ya está cerrada.']);
        }

        $end = Carbon::parse($request->validated('end_date') ?? today());
        if ($end->lt($membership->start_date)) {
            throw ValidationException::withMessages(['end_date' => 'La fecha de cierre no puede ser anterior al inicio.']);
        }

        $membership->update(['status' => PlayerCategoryMembership::STATUS_ENDED, 'end_date' => $end->toDateString()]);

        return MembershipResource::make($membership->load('player', 'category'));
    }

    /** El número de camiseta no se repite entre los jugadores activos de la categoría. */
    private function ensureShirtNumberIsFree(int $categoryId, ?int $number, ?int $ignoreId = null): void
    {
        if ($number === null) {
            return;
        }

        $taken = PlayerCategoryMembership::query()
            ->where('category_id', $categoryId)
            ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
            ->where('shirt_number', $number)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['shirt_number' => "El número {$number} ya lo usa otro jugador de la categoría."]);
        }
    }
}
