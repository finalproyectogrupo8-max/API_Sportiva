<?php

namespace App\Http\Requests\Club;

use App\Models\Club\ClubUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Cambiar los roles, los deportes o el estado de alguien del equipo (TIZ-34). */
class UpdateClubUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => ['sometimes', 'array', 'min:1', 'max:10'],
            'roles.*' => ['string', 'distinct', 'max:60'],
            'sport_ids' => ['sometimes', 'array'],
            'sport_ids.*' => ['integer', 'distinct'],
            /** `disabled` le quita el acceso al club; `active` se lo devuelve. */
            'status' => ['sometimes', Rule::in([ClubUser::STATUS_ACTIVE, ClubUser::STATUS_DISABLED])],
        ];
    }
}
