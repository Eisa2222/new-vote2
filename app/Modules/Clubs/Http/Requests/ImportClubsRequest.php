<?php

declare(strict_types=1);

namespace App\Modules\Clubs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportClubsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('clubs.create');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.config('voting.import.max_size_kb')],
        ];
    }
}
