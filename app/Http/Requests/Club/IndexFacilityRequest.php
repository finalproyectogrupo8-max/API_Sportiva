<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class IndexFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', 'in:court,gym,locker_room,other'],
            'status' => ['sometimes', 'string', 'in:active,out_of_service'],
            'sort' => ['sometimes', 'string', 'in:name,-name,created_at,-created_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
