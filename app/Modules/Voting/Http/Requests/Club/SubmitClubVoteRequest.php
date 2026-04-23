<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Requests\Club;

use App\Modules\Voting\Support\Formation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SubmitClubVoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $exists = Rule::exists('players', 'id')->whereNull('deleted_at');
        $slots  = Formation::slots();

        return [
            'best_saudi_player_id'   => ['required', 'integer', $exists],
            'best_foreign_player_id' => ['required', 'integer', $exists],

            'lineup'               => ['required', 'array'],
            'lineup.goalkeeper'    => ['required', 'array', 'size:'.$slots['goalkeeper']],
            'lineup.goalkeeper.*'  => ['required', 'integer', 'distinct', $exists],
            'lineup.defense'       => ['required', 'array', 'size:'.$slots['defense']],
            'lineup.defense.*'     => ['required', 'integer', 'distinct', $exists],
            'lineup.midfield'      => ['required', 'array', 'size:'.$slots['midfield']],
            'lineup.midfield.*'    => ['required', 'integer', 'distinct', $exists],
            'lineup.attack'        => ['required', 'array', 'size:'.$slots['attack']],
            'lineup.attack.*'      => ['required', 'integer', 'distinct', $exists],
        ];
    }

    public function messages(): array
    {
        return [
            'best_saudi_player_id.required'   => __('Please pick a Best Saudi Player.'),
            'best_foreign_player_id.required' => __('Please pick a Best Foreign Player.'),
            'lineup.goalkeeper.size'          => __('Goalkeeper requires exactly :size player.', ['size' => Formation::GOALKEEPER]),
            'lineup.defense.size'             => __('Defense requires exactly :size players.',  ['size' => Formation::DEFENSE]),
            'lineup.midfield.size'            => __('Midfield requires exactly :size players.', ['size' => Formation::MIDFIELD]),
            'lineup.attack.size'              => __('Attack requires exactly :size players.',   ['size' => Formation::ATTACK]),
        ];
    }
}
