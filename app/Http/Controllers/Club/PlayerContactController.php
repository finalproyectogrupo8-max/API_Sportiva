<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StorePlayerContactRequest;
use App\Http\Resources\Club\PlayerContactResource;
use App\Models\Club\Player;
use App\Models\Club\PlayerContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/** Tutores y contactos del jugador (TIZ-15). Uno solo es el principal. */
class PlayerContactController extends Controller
{
    use ChecksClubScope;

    public function index(Player $player): AnonymousResourceCollection
    {
        $this->authorizeClub('player_contacts.view');
        $this->ensurePlayerVisible($player, 'player_contacts.view');

        return PlayerContactResource::collection(
            $player->contacts()->orderByDesc('is_primary')->orderBy('id')->get()
        );
    }

    public function store(StorePlayerContactRequest $request, Player $player): JsonResponse
    {
        $this->authorizeClub('player_contacts.update');
        $this->ensurePlayerVisible($player, 'player_contacts.update');

        $contact = DB::connection('tenant')->transaction(function () use ($request, $player) {
            // El primer contacto es el principal aunque no se pida.
            $primary = $request->boolean('is_primary') || ! $player->contacts()->exists();
            if ($primary) {
                $player->contacts()->update(['is_primary' => false]);
            }

            return $player->contacts()->create([...$request->validated(), 'is_primary' => $primary]);
        });

        return PlayerContactResource::make($contact)->response()->setStatusCode(201);
    }

    public function update(StorePlayerContactRequest $request, PlayerContact $contact): PlayerContactResource
    {
        $this->authorizeClub('player_contacts.update');
        $this->ensurePlayerVisible($contact->player, 'player_contacts.update');

        DB::connection('tenant')->transaction(function () use ($request, $contact) {
            if ($request->boolean('is_primary')) {
                PlayerContact::query()->where('player_id', $contact->player_id)->whereKeyNot($contact->id)->update(['is_primary' => false]);
            }
            $contact->update($request->validated());
        });

        return PlayerContactResource::make($contact->refresh());
    }

    /** Si se borra el principal, el más antiguo que queda pasa a serlo. */
    public function destroy(PlayerContact $contact): Response
    {
        $this->authorizeClub('player_contacts.update');
        $this->ensurePlayerVisible($contact->player, 'player_contacts.update');

        DB::connection('tenant')->transaction(function () use ($contact) {
            $contact->delete();

            if ($contact->is_primary) {
                PlayerContact::query()->where('player_id', $contact->player_id)->orderBy('id')->first()?->update(['is_primary' => true]);
            }
        });

        return response()->noContent();
    }
}
