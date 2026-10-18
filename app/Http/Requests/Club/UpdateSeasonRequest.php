<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', 'string', 'in:planned,active,closed'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('start_date') || $validator->errors()->has('end_date')) {
                return;
            }

            /** @var Season $season */
            $season = $this->route('season');
            $startDate = $this->date('start_date') ?? $season->start_date;
            $endDate = $this->date('end_date') ?? $season->end_date;

            if ($endDate->lt($startDate)) {
                $validator->errors()->add('end_date', 'La fecha de fin debe ser igual o posterior a la fecha de inicio.');
            }
        }];
    }
}
