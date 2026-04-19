<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSmsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

    public function rules(): array
    {
        $driver = $this->input('sms_driver');

        return [
            'sms_driver' => ['required', Rule::in(['log', 'twilio', 'unifonic'])],

            // Twilio — required only when that driver is chosen.
            'sms_twilio_sid'   => ['required_if:sms_driver,twilio', 'nullable', 'string', 'max:120'],
            'sms_twilio_token' => ['nullable', 'string', 'max:240'],  // blank = keep current
            'sms_twilio_from'  => ['required_if:sms_driver,twilio', 'nullable', 'string', 'max:40'],

            // Unifonic.
            'sms_unifonic_appsid' => ['nullable', 'string', 'max:240'], // blank = keep
            'sms_unifonic_sender' => ['required_if:sms_driver,unifonic', 'nullable', 'string', 'max:40'],

            // Optional test send.
            'test_to'      => ['nullable', 'string', 'max:30'],
            'test_message' => ['nullable', 'string', 'max:320'],
        ];
    }
}
