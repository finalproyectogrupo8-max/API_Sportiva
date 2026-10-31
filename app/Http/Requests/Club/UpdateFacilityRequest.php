<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'type' => ['sometimes', 'required', 'string', 'in:court,gym,locker_room,other'],
            'location' => ['sometimes', 'nullable', 'string', 'max:200'],
            'status' => ['sometimes', 'required', 'string', 'in:active,out_of_service'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
