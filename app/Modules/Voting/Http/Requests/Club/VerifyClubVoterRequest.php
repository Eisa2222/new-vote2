<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyClubVoterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // The single hidden field from the dropdown. The controller
        // double-checks it belongs to the token's club and is active
        // — this request only guards the shape.
        return [
            'player_id' => ['required', 'integer', Rule::exists('players', 'id')->whereNull('deleted_at')],
        ];
    }
}
