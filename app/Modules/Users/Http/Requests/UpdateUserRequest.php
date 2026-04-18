<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($target?->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'status'   => ['nullable', 'in:active,inactive'],
            'roles'    => ['array'],
            'roles.*'  => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
