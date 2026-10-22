<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:12', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_platform_admin' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->route('user');
            $isDeactivating = in_array($this->input('is_active'), [false, 0, '0'], true);

            if (
                $isDeactivating
                && $user instanceof User
                && $user->is($this->user())
            ) {
                $validator->errors()->add('is_active', 'No podés desactivar tu propio usuario.');
            }
        });
    }
}
