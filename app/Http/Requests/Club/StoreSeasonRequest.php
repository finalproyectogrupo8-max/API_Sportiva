<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            // Sin deporte, la temporada es de todo el club (solo con alcance "todo el club").
            'sport_id' => ['nullable', 'integer', Rule::exists('tenant.sports', 'id')],
            'name' => ['required', 'string', 'max:80'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', 'in:planned,active,closed'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->has('start_date') && ! $validator->errors()->has('end_date')
                && $this->date('end_date')?->lt($this->date('start_date'))) {
                $validator->errors()->add('end_date', 'La fecha de fin debe ser igual o posterior a la fecha de inicio.');
            }

            // Sin el permiso responde 403 el controlador; acá solo se valida el deporte.
            $access = $this->user()->clubAccess();
            $sportId = $this->filled('sport_id') ? $this->integer('sport_id') : null;
            if ($access->has('seasons.create') && ! $validator->errors()->has('sport_id') && ! $access->allowsSport('seasons.create', $sportId)) {
                $validator->errors()->add('sport_id', 'Elegí uno de los deportes que coordinás.');
            }
        }];
    }
}
