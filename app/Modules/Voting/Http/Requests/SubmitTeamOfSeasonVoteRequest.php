<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitTeamOfSeasonVoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'attack'          => ['required', 'array', 'size:3'],
            'attack.*'        => ['required', 'integer', 'exists:voting_category_candidates,id'],
            'midfield'        => ['required', 'array', 'size:3'],
            'midfield.*'      => ['required', 'integer', 'exists:voting_category_candidates,id'],
            'defense'         => ['required', 'array', 'size:4'],
            'defense.*'       => ['required', 'integer', 'exists:voting_category_candidates,id'],
            'goalkeeper'      => ['required', 'array', 'size:1'],
            'goalkeeper.*'    => ['required', 'integer', 'exists:voting_category_candidates,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'attack.size'     => __('Attack line requires exactly 3 players.'),
            'midfield.size'   => __('Midfield line requires exactly 3 players.'),
            'defense.size'    => __('Defense line requires exactly 4 players.'),
            'goalkeeper.size' => __('Goalkeeper line requires exactly 1 player.'),
        ];
    }
}
