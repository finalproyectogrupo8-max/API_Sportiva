<?php

namespace App\Http\Requests\Club;

use App\Models\Club\CategoryCoach;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Asignar un profe a una categoría. */
class StoreCategoryCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coach_id' => ['required', 'integer', Rule::exists('tenant.coaches', 'id')->whereNull('deleted_at')],
            'assignment_type' => ['required', 'string', Rule::in(CategoryCoach::TYPES)],
            'start_date' => ['sometimes', 'date'],
        ];
    }
}
