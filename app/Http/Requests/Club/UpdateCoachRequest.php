<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Coach;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Coach $coach */
        $coach = $this->route('coach');

        return [
            'club_user_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('tenant.club_users', 'id'),
                Rule::unique('tenant.coaches', 'club_user_id')->ignore($coach),
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive'],
        ];
    }
}
