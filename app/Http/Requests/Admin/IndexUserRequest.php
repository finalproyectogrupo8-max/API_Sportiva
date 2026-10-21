<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    public const SORTABLE = ['name', 'email', 'created_at'];

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('is_active'), ['true', 'false'], true)) {
            $this->merge(['is_active' => $this->input('is_active') === 'true']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sorts = array_merge(self::SORTABLE, array_map(fn (string $field) => '-'.$field, self::SORTABLE));

        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', Rule::in($sorts)],
        ];
    }
}
