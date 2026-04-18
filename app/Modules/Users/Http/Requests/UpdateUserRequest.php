<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

    public function rules(): array
    {
        /** @var User|null $target */
        $target = $this->route('user');

        return [
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($target?->id)],
            // Password is optional on update, but when set it must pass the
            // same stricter policy as on create.
            'password' => ['nullable', 'string',
                Password::min(10)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'status'   => ['nullable', 'in:active,inactive'],
            'roles'    => ['array'],
            'roles.*'  => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
