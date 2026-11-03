<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Models\Club\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Link de calendario de una categoría (FRO-32): se agrega a Google Calendar o al celular y muestra
 * los partidos y entrenamientos. Regenerarlo invalida el link anterior.
 */
class CategoryCalendarController extends Controller
{
    use ChecksClubScope;

    /** Devuelve el link (lo crea la primera vez). */
    public function show(Request $request, Category $category): JsonResponse
    {
        $this->authorizeClub('matches.view');
        $this->ensureCategoryInScope($category, 'matches.view');

        if (! $category->calendar_token) {
            $this->regenerate($request, $category);
        }

        return response()->json(['data' => $this->link($category)]);
    }

    /** Genera un link nuevo: el anterior deja de funcionar. */
    public function store(Request $request, Category $category): JsonResponse
    {
        $this->authorizeClub('matches.update');
        $this->ensureCategoryInScope($category, 'matches.update');
        $this->regenerate($request, $category);

        return response()->json(['data' => $this->link($category)], 201);
    }

    private function regenerate(Request $request, Category $category): void
    {
        // El slug del club adelante: así el link público sabe en qué base buscar (igual que la convocatoria).
        $category->forceFill(['calendar_token' => $request->attributes->get('tenant')->slug.'.'.Str::random(40)])->save();
    }

    /** @return array{url: string, webcal_url: string, google_url: string} */
    private function link(Category $category): array
    {
        $url = url("/api/public/calendar/{$category->calendar_token}.ics");
        $webcal = preg_replace('#^https?://#', 'webcal://', $url);

        return [
            'url' => $url,
            /** Para el calendario del celular (iPhone, Outlook). */
            'webcal_url' => $webcal,
            /** Agregar en Google Calendar por URL. */
            'google_url' => 'https://calendar.google.com/calendar/r?cid='.urlencode($webcal),
        ];
    }
}
