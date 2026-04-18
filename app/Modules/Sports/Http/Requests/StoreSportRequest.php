<?php

declare(strict_types=1);

namespace App\Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreSportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

    /**
     * Derive the slug from name_en before validation so the unique rule
     * evaluates against the computed slug instead of a blank field.
     */
    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug', ''));
        if ($slug === '') {
            $slug = Str::slug((string) $this->input('name_en', ''));
        }
        $this->merge(['slug' => $slug]);
    }

    public function rules(): array
    {
        return [
            'slug'    => ['required', 'string', 'max:60', Rule::unique('sports', 'slug')],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'status'  => ['required', 'in:active,inactive'],
        ];
    }
}
