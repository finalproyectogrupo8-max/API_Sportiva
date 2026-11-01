<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveRepairRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'performed_at' => ['nullable', 'date'],
            'performed_by' => [
                'nullable',
                'integer',
                Rule::exists('tenant.club_users', 'id'),
            ],
        ];
    }
}
