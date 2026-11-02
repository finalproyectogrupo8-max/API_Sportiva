<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'required', 'string', 'max:150'],
            'body' => ['sometimes', 'required', 'string', 'max:10000'],
            ...AnnouncementAudienceRules::rules(required: false),
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => AnnouncementAudienceRules::requireTarget($validator)];
    }
}
