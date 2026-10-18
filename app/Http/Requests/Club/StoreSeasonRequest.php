<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', 'in:planned,active,closed'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('start_date') || $validator->errors()->has('end_date')) {
                return;
            }

            if ($this->date('end_date')?->lt($this->date('start_date'))) {
                $validator->errors()->add('end_date', 'La fecha de fin debe ser igual o posterior a la fecha de inicio.');
            }
        }];
    }
}
