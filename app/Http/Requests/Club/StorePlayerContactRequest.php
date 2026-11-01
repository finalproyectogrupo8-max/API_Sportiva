<?php

namespace App\Http\Requests\Club;

use App\Models\Club\PlayerContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partial = $this->isMethod('PATCH');
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'name' => [...$required, 'string', 'max:150'],
            'relationship' => [...$required, 'string', Rule::in(PlayerContact::RELATIONSHIPS)],
            // Hace falta al menos un medio para avisarle.
            'phone' => ['nullable', 'string', 'max:40', ...($partial ? [] : ['required_without:email'])],
            'email' => ['nullable', 'email', 'max:255', ...($partial ? [] : ['required_without:phone'])],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without' => 'Cargá un teléfono o un mail.',
            'email.required_without' => 'Cargá un teléfono o un mail.',
        ];
    }
}
