<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Club\ClubUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubUserController extends Controller
{
    /**
     * Personas activas del club, solo id y nombre: para elegir a quién asignar una reparación
     * y mostrar quién reportó o pidió algo. No expone mails ni roles.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->currentClubUser(), 403);

        $users = ClubUser::query()
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name']);

        return response()->json([
            'data' => $users->map(fn (ClubUser $user) => [
                'id' => $user->id,
                'display_name' => $user->display_name,
            ])->values(),
        ]);
    }
}
