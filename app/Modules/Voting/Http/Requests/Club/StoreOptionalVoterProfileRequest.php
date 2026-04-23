<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOptionalVoterProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // Every field is optional — the whole form is skippable.
        // Shape validation only; the Action handles normalisation.
        return [
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:180'],
            'national_id'   => ['nullable', 'string', 'max:20'],
        ];
    }
}
