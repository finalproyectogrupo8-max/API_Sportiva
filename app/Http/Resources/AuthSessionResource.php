<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthSessionResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     token?: string,
     *     expires_at?: string,
     *     user?: array{id: int, name: string, email: string},
     *     tenants?: array<int, array{slug: string, name: string}>,
     *     tenant?: array{slug: string, name: string},
     *     profile?: mixed,
     *     permissions?: array<int, string>
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
