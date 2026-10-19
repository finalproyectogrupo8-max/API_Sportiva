<?php

namespace App\Http\Requests\Admin;

use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTenantRequest extends FormRequest
{
    public const SORTABLE = ['name', 'slug', 'status', 'created_at'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sorts = array_merge(self::SORTABLE, array_map(fn (string $field) => '-'.$field, self::SORTABLE));

        return [
            'status' => ['sometimes', Rule::enum(TenantStatus::class)],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', Rule::in($sorts)],
        ];
    }
}
