<?php

declare(strict_types=1);

namespace App\Modules\Clubs\Http\Requests;

use App\Modules\Shared\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdateClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('club')) ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('club')?->id;

        return [
            'name_ar'    => ['sometimes', 'required', 'string', 'max:120', Rule::unique('clubs', 'name_ar')->ignore($id)],
            'name_en'    => ['sometimes', 'required', 'string', 'max:120', Rule::unique('clubs', 'name_en')->ignore($id)],
            'short_name' => ['nullable', 'string', 'max:20'],
            // No SVG — see StoreClubRequest for the reasoning (XSS risk).
            'logo'       => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'status'     => ['nullable', new Enum(ActiveStatus::class)],
            'sport_ids'  => ['array'],
            'sport_ids.*'=> ['integer', Rule::exists('sports', 'id')],
        ];
    }
}
