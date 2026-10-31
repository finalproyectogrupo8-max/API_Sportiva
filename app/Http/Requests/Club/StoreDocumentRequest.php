<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Subir un documento de un jugador (multipart). */
class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $photo = $this->input('kind') === 'photo';

        return [
            'kind' => ['required', 'string', Rule::in(Document::PLAYER_KINDS)],
            // PDF o foto del celular (HEIC), hasta 10 MB. La foto de la ficha, solo imagen y hasta 5 MB.
            'file' => $photo
                ? ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic', 'max:5120']
                : ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,heic', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'El archivo tiene que ser PDF, JPG, PNG, WEBP o HEIC.',
            'file.max' => 'El archivo es demasiado grande.',
        ];
    }
}
