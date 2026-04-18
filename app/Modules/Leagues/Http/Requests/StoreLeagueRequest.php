<?php

declare(strict_types=1);

namespace App\Modules\Leagues\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreLeagueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

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
            'sport_id' => ['required', 'integer', Rule::exists('sports', 'id')],
            'name_ar'  => ['required', 'string', 'max:150'],
            'name_en'  => ['required', 'string', 'max:150'],
            'slug'     => ['required', 'string', 'max:80', Rule::unique('leagues', 'slug')],
            'status'   => ['required', 'in:active,inactive'],
        ];
    }
}
