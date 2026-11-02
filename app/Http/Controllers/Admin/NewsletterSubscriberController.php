<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Suscriptores del newsletter de la landing (FRO-27), para el panel de plataforma. `?format=csv`
 * descarga la lista completa.
 */
class NewsletterSubscriberController extends Controller
{
    public function index(Request $request): JsonResponse|StreamedResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:190'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'format' => ['sometimes', 'in:json,csv'],
        ]);

        $query = Newsletter::query()
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('email', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (($filters['format'] ?? 'json') === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel abre bien los acentos.
                fputcsv($out, ['email', 'suscripto_el']);
                $query->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [$row->email, $row->created_at?->toDateTimeString()]);
                    }
                });
                fclose($out);
            }, 'suscriptores-newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $page = $query->paginate($filters['per_page'] ?? 50);

        return response()->json([
            'data' => collect($page->items())->map(fn (Newsletter $n) => [
                'id' => $n->id,
                'email' => $n->email,
                'subscribed_at' => $n->created_at?->toIso8601String(),
            ])->values(),
            'meta' => ['total' => $page->total(), 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
        ]);
    }
}
