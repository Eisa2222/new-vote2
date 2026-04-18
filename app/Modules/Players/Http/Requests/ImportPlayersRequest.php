<?php

declare(strict_types=1);

namespace App\Modules\Players\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportPlayersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('players.create');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.config('voting.import.max_size_kb')],
        ];
    }
}
