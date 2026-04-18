<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'name')],
            'permissions'   => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('Role name must use only lowercase letters, digits, and underscores.'),
        ];
    }
}
