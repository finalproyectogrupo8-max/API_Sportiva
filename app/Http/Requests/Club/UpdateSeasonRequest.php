<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Fuera de su alcance, 404 antes de validar: los errores no revelan que el registro existe. */
    protected function prepareForValidation(): void
    {
        $season = $this->route('season');
        // Sin el permiso de cambiarlo, que responda el 403 del controlador.
        abort_if($this->user()->clubAccess()->has('seasons.update') && $season instanceof Season && ! Season::query()->visibleTo($this->user()->clubAccess())->whereKey($season->getKey())->exists(), 404);
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['sometimes', 'nullable', 'integer', Rule::exists('tenant.sports', 'id')],
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', 'string', 'in:planned,active,closed'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Season $season */
            $season = $this->route('season');

            $access = $this->user()->clubAccess();
            if ($access->has('seasons.update') && $this->has('sport_id') && ! $validator->errors()->has('sport_id')) {
                $sportId = $this->filled('sport_id') ? $this->integer('sport_id') : null;
                if (! $access->allowsSport('seasons.update', $sportId)) {
                    $validator->errors()->add('sport_id', 'Elegí uno de los deportes que coordinás.');
                } elseif ($sportId !== null && $this->usedByOtherSport($season, $sportId)) {
                    $validator->errors()->add('sport_id', 'La temporada ya tiene categorías o competencias de otro deporte.');
                }
            }

            if ($validator->errors()->has('start_date') || $validator->errors()->has('end_date')) {
                return;
            }

            $startDate = $this->date('start_date') ?? $season->start_date;
            $endDate = $this->date('end_date') ?? $season->end_date;

            if ($endDate->lt($startDate)) {
                $validator->errors()->add('end_date', 'La fecha de fin debe ser igual o posterior a la fecha de inicio.');
            }
        }];
    }

    private function usedByOtherSport(Season $season, int $sportId): bool
    {
        return $season->categories()->where('sport_id', '!=', $sportId)->exists()
            || $season->competitions()->where('sport_id', '!=', $sportId)->exists();
    }
}
