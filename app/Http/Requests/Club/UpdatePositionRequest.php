<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Position $position */
        $position = $this->route('position');
        $sportId = $this->input('sport_id', $position->sport_id);

        return [
            'sport_id' => ['sometimes', 'required', 'integer', 'exists:tenant.sports,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('tenant.positions', 'name')
                    ->where('sport_id', $sportId)
                    ->ignore($position),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
