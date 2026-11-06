<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Category;
use App\Models\Club\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', Rule::exists('tenant.sports', 'id')],
            'season_id' => ['required', 'integer', Rule::exists('tenant.seasons', 'id')],
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('tenant.categories', 'name')
                    ->where('sport_id', $this->input('sport_id'))
                    ->where('season_id', $this->input('season_id')),
            ],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'max_age' => ['nullable', 'integer', 'min:0', 'max:32767'],
            // TIZ-40: `female`, `male` o `mixed`; opcional.
            'branch' => ['nullable', 'string', Rule::in(Category::BRANCHES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validateSportAndSeason(
                $validator,
                'categories.create',
                $this->filled('sport_id') ? $this->integer('sport_id') : null,
                $this->filled('season_id') ? $this->integer('season_id') : null,
            );

            if ($this->filled('min_age') && $this->filled('max_age') && $this->integer('max_age') < $this->integer('min_age')) {
                $validator->errors()->add('max_age', 'La edad máxima debe ser igual o mayor que la edad mínima.');
            }
        }];
    }

    /**
     * El deporte tiene que ser uno de los suyos (con alcance "sus deportes") y la temporada, de ese
     * deporte o de todo el club.
     */
    private function validateSportAndSeason(Validator $validator, string $permission, ?int $sportId, ?int $seasonId): void
    {
        $access = $this->user()->clubAccess();
        if ($sportId !== null && $access->has($permission) && ! $validator->errors()->has('sport_id') && ! $access->allowsSport($permission, $sportId)) {
            $validator->errors()->add('sport_id', 'Elegí uno de los deportes que coordinás.');
        }

        $season = $seasonId ? Season::find($seasonId) : null;
        if ($sportId !== null && $season && ! $validator->errors()->has('season_id') && ! $season->acceptsSport($sportId)) {
            $validator->errors()->add('season_id', 'La temporada es de otro deporte.');
        }
    }
}
