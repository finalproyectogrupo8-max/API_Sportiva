<?php

namespace App\Http\Controllers\Club;

use App\Club\Documents\DocumentStore;
use App\Http\Controllers\Controller;
use App\Http\Resources\Club\ClubResource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Escudo del club (TIZ-29). La ruta va en la base central (tenants.logo_path) porque se muestra antes
 * de entrar al club. No es un dato sensible: se sirve sin token.
 */
class ClubLogoController extends Controller
{
    public function __construct(private DocumentStore $store) {}

    public function store(Request $request, AuditLogger $auditLogger): ClubResource
    {
        Gate::allowIf(fn (User $user) => $user->hasClubPermission('club.update'));
        $request->validate(
            ['logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:1024']],
            ['logo.mimes' => 'El escudo tiene que ser PNG, JPG o WEBP.', 'logo.max' => 'El escudo puede pesar hasta 1 MB.'],
        );

        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');
        $old = $tenant->logo_path;
        $file = $request->file('logo');

        $tenant->logo_path = $file->storeAs("clubs/{$tenant->slug}/logo", Str::uuid().'.'.strtolower($file->extension() ?: 'png'), ['disk' => $this->store->disk()]);
        $auditLogger->recordUpdated($tenant);
        $tenant->save();

        if ($old) {
            Storage::disk($this->store->disk())->delete($old);
        }

        return app(ClubController::class)->show($request);
    }

    public function destroy(Request $request, AuditLogger $auditLogger): Response
    {
        Gate::allowIf(fn (User $user) => $user->hasClubPermission('club.update'));

        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');
        if ($tenant->logo_path) {
            Storage::disk($this->store->disk())->delete($tenant->logo_path);
            $tenant->logo_path = null;
            $auditLogger->recordUpdated($tenant);
            $tenant->save();
        }

        return response()->noContent();
    }

    /** Público: el escudo de un club activo. */
    public function show(string $slug): StreamedResponse
    {
        $tenant = Tenant::query()->where('slug', $slug)->where('status', 'active')->first();
        abort_unless($tenant?->logo_path && Storage::disk($this->store->disk())->exists($tenant->logo_path), 404);

        return Storage::disk($this->store->disk())->response($tenant->logo_path, null, ['Cache-Control' => 'public, max-age=3600']);
    }
}
