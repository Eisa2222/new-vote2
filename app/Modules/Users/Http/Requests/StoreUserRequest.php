<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:180', Rule::unique('users', 'email')],
            // Password policy: at least 10 characters with mixed-case letters,
            // numbers, and symbols. Stricter than Laravel's default `min:8`
            // and enforced consistently on create + update.
            'password' => ['required', 'string',
                Password::min(10)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'status'   => ['nullable', 'in:active,inactive'],
            'roles'    => ['array'],
            'roles.*'  => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
