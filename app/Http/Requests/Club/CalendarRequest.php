<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class CalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            // Hasta dos meses por consulta: una vista de mes con sus bordes.
            'to' => ['required', 'date', 'after_or_equal:from', 'before_or_equal:'.$this->date('from')?->addDays(62)->toDateString()],
            'category_id' => ['sometimes', 'integer'],
        ];
    }

    public function messages(): array
    {
        return ['to.before_or_equal' => 'El rango puede ser de hasta 62 días.'];
    }
}
