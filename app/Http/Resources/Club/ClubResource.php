<?php

namespace App\Http\Resources\Club;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil del club con datos calculados de su base. `stats` lo arma el controlador.
 *
 * @mixin Tenant
 */
class ClubResource extends JsonResource
{
    /** @param  array{active_players: int, sports: list<string>}  $stats */
    public function __construct(Tenant $resource, private array $stats)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'city' => $this->city,
            'founded_year' => $this->founded_year,
            /** Hexadecimal (#rrggbb). Acento del club: escudo y chips. */
            'primary_color' => $this->primary_color,
            /** URL del escudo en la API (`GET /api/public/clubs/{slug}/logo`), o null si no tiene. */
            'logo_url' => $this->logo_path ? url("/api/public/clubs/{$this->slug}/logo").'?v='.substr(md5($this->logo_path), 0, 8) : null,
            'stats' => [
                /** Jugadores con estado activo. Se calcula, no se guarda. */
                'active_players' => $this->stats['active_players'],
                /**
                 * Nombres de los deportes activos del club.
                 *
                 * @var list<string>
                 */
                'sports' => $this->stats['sports'],
            ],
        ];
    }
}
