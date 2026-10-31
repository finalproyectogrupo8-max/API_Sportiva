<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            $this->merge(['status' => 'active']);
        }
        if (! $this->has('type')) {
            $this->merge(['type' => 'court']);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['sometimes', 'string', 'in:court,gym,locker_room,other'],
            'location' => ['nullable', 'string', 'max:200'],
            'status' => ['sometimes', 'string', 'in:active,out_of_service'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
