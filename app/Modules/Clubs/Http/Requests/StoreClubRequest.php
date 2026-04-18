<?php

declare(strict_types=1);

namespace App\Modules\Clubs\Http\Requests;

use App\Modules\Clubs\Models\Club;
use App\Modules\Shared\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Club::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name_ar'    => ['required', 'string', 'max:120', 'unique:clubs,name_ar'],
            'name_en'    => ['required', 'string', 'max:120', 'unique:clubs,name_en'],
            'short_name' => ['nullable', 'string', 'max:20'],
            // SVG removed: an attacker-uploaded .svg can ship inline JS or
            // external <script> refs and would execute when served from
            // /storage. Stick to raster formats the browser cannot script.
            'logo'       => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'status'     => ['nullable', new Enum(ActiveStatus::class)],
            'sport_ids'  => ['array'],
            'sport_ids.*'=> ['integer', Rule::exists('sports', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required' => __('Club Arabic name is required.'),
            'name_en.required' => __('Club English name is required.'),
            'name_ar.unique'   => __('A club with this Arabic name already exists.'),
            'name_en.unique'   => __('A club with this English name already exists.'),
        ];
    }
}
