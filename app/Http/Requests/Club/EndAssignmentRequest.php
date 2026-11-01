<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

/** Cerrar una asignación (jugador o profe en una categoría). Sin fecha, cierra hoy. */
class EndAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_date' => ['sometimes', 'date'],
        ];
    }
}
