<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:150'],
            /** Markdown. El HTML se muestra como texto en el mail. */
            'body' => ['required', 'string', 'max:10000'],
            ...AnnouncementAudienceRules::rules(),
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => AnnouncementAudienceRules::requireTarget($validator)];
    }
}
