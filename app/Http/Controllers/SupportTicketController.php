<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTicketRequest;
use App\Http\Resources\SupportTicketCreatedResource;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;

class SupportTicketController extends Controller
{
    /**
     * Envío público de tickets de contacto.
     */
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        // Honeypot: si el campo oculto website viene con contenido, responder 201 igual pero no guardar
        if ($request->filled('website')) {
            return SupportTicketCreatedResource::make(['id' => 0])
                ->response()
                ->setStatusCode(201);
        }

        $ticket = SupportTicket::create($request->safe()->only([
            'name',
            'email',
            'club_name',
            'subject',
            'message',
        ]));

        return SupportTicketCreatedResource::make($ticket)
            ->response()
            ->setStatusCode(201);
    }
}
