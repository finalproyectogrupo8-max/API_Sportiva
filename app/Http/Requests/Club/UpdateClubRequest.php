<?php

namespace App\Http\Requests\Club;

use App\Http\Requests\Concerns\TenantProfileRules;
use Illuminate\Foundation\Http\FormRequest;

/** Perfil del club editado por su administrador (permiso club.update). */
class UpdateClubRequest extends FormRequest
{
    use TenantProfileRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->tenantProfileRules(),
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            // Los maneja la plataforma.
            'slug' => ['prohibited'],
            'status' => ['prohibited'],
            'database_name' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return $this->tenantProfileMessages();
    }
}
