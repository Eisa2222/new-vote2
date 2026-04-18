<?php

declare(strict_types=1);

namespace App\Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'status'  => ['required', 'in:active,inactive'],
        ];
    }
}
