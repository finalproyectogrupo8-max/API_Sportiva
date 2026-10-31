<?php

namespace App\Http\Requests\Concerns;

/** Reglas del perfil del club, compartidas por el panel de plataforma y el admin del club. */
trait TenantProfileRules
{
    /** @return array<string, list<string>> */
    protected function tenantProfileRules(): array
    {
        $maxYear = (int) now()->format('Y');

        return [
            'short_name' => ['sometimes', 'nullable', 'string', 'max:6'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'founded_year' => ['sometimes', 'nullable', 'integer', 'min:1800', "max:{$maxYear}"],
            'primary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /** @return array<string, string> */
    protected function tenantProfileMessages(): array
    {
        return [
            'short_name.max' => 'La sigla puede tener hasta 6 caracteres.',
            'founded_year.min' => 'El año de fundación no es válido.',
            'founded_year.max' => 'El año de fundación no puede ser futuro.',
            'primary_color.regex' => 'El color tiene que ser un hexadecimal como #1d4ed8.',
        ];
    }
}
