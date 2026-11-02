<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PreviewAnnouncementAudienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return AnnouncementAudienceRules::rules();
    }

    public function after(): array
    {
        return [fn (Validator $validator) => AnnouncementAudienceRules::requireTarget($validator)];
    }
}
