<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexSupportTicketRequest;
use App\Http\Requests\Admin\UpdateSupportTicketRequest;
use App\Http\Resources\Admin\SupportTicketResource;
use App\Models\SupportTicket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SupportTicketController extends Controller
{
    /**
     * Lista paginada de tickets de soporte para administradores de plataforma.
     */
    public function index(IndexSupportTicketRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SupportTicket::class);

        $sort = $request->validated('sort', '-created_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $tickets = SupportTicket::query()
            ->when($request->validated('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy($column, $direction)
            ->orderBy('id', 'desc')
            ->paginate(min(max((int) $request->validated('per_page', 20), 1), 100));

        return SupportTicketResource::collection($tickets);
    }

    /**
     * Detalle de un ticket de soporte.
     */
    public function show(SupportTicket $ticket): SupportTicketResource
    {
        Gate::authorize('view', $ticket);

        return SupportTicketResource::make($ticket);
    }

    /**
     * Actualizar estado y notas administrativas del ticket.
     */
    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket): SupportTicketResource
    {
        Gate::authorize('update', $ticket);

        DB::transaction(function () use ($request, $ticket) {
            $validated = $request->validated();

            if (array_key_exists('admin_notes', $validated)) {
                $ticket->admin_notes = $validated['admin_notes'];
            }

            if (array_key_exists('status', $validated)) {
                $newStatus = $validated['status'];
                $ticket->status = $newStatus;

                if ($newStatus === TicketStatus::Resolved->value || $newStatus === TicketStatus::Resolved) {
                    $ticket->resolved_at = now();
                    $ticket->handled_by = $request->user()->id;
                } else {
                    $ticket->resolved_at = null;
                }
            }

            $ticket->save();
        });

        return SupportTicketResource::make($ticket->fresh());
    }
}

