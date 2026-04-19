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

    public function messages(): array
    {
        return [
            'roles.required' => __('At least one role is required.'),
            'roles.min'      => __('At least one role is required.'),
        ];
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:180', Rule::unique('users', 'email')],
            // Password is OPTIONAL on create:
            //   • If supplied, it must pass the 10-char / mixed / symbols
            //     policy (same as self-service reset).
            //   • If omitted, the controller sends an invitation email
            //     with a one-time link so the user sets their own password.
            'password' => ['nullable', 'string',
                Password::min(10)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'status'   => ['nullable', 'in:active,inactive'],
            // Every new user must get at least one role — otherwise they
            // can sign in but see nothing, which is a confusing "it's
            // broken" support ticket. Enforcing at the request layer.
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
