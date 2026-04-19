<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Kept under `users.manage` to stay consistent with the other
        // settings panels. An explicit `settings.mail` permission can be
        // introduced later if mail management needs a separate gate.
        return (bool) $this->user()?->can('users.manage');
    }

    public function rules(): array
    {
        return [
            'mail_host'         => ['required', 'string', 'max:180'],
            'mail_port'         => ['required', 'integer', 'between:1,65535'],
            'mail_username'     => ['nullable', 'string', 'max:180'],
            // Password is only required on first setup. If left blank and
            // a value already exists in the DB, the controller keeps the
            // previous one — matches the "leave blank to keep" UX we
            // use for user passwords.
            'mail_password'     => ['nullable', 'string', 'max:180'],
            'mail_encryption'   => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'mail_from_address' => ['required', 'email', 'max:180'],
            'mail_from_name'    => ['required', 'string', 'max:120'],
            // Optional test email dispatched on save.
            'test_to'           => ['nullable', 'email', 'max:180'],
        ];
    }
}
