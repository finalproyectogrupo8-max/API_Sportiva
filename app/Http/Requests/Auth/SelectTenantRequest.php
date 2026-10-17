<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SelectTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
        ];
    }
}
