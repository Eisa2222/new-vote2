<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyVoterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'national_id' => ['nullable', 'required_without:mobile', 'string', 'min:8', 'max:15'],
            'mobile'      => ['nullable', 'required_without:national_id', 'string', 'min:9', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.required_without' => __('Please enter your national ID or mobile number.'),
            'mobile.required_without'      => __('Please enter your national ID or mobile number.'),
        ];
    }
}
