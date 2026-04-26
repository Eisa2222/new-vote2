<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Requests\Club;

use App\Modules\Voting\Enums\AwardType;
use App\Modules\Voting\Models\CampaignClub;
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

        // Which awards does this campaign show?
        //
        // Matches the logic in ClubVotingController::ballot() — if the
        // admin tagged voting_categories with award_type, only those
        // awards render on the ballot; otherwise all three render.
        //
        // Previously the request always required all three (Best Saudi +
        // Best Foreign + full TOS lineup). On a campaign that only showed
        // Best Saudi the ballot submitted a payload missing 'best_foreign_player_id'
        // and 'lineup', so Laravel bounced the request back to the ballot
        // with three required-field errors — from the voter's POV,
        // tapping "Complete voting" just refreshed the page and nothing
        // happened.
        [$showSaudi, $showForeign, $showTos] = $this->configuredAwards();

        $rules = [];

        if ($showSaudi) {
            $rules['best_saudi_player_id'] = ['required', 'integer', $exists];
        }
        if ($showForeign) {
            $rules['best_foreign_player_id'] = ['required', 'integer', $exists];
        }

        if ($showTos) {
            $rules['lineup']              = ['required', 'array'];
            $rules['lineup.goalkeeper']   = ['required', 'array', 'size:'.$slots['goalkeeper']];
            $rules['lineup.goalkeeper.*'] = ['required', 'integer', 'distinct', $exists];
            $rules['lineup.defense']      = ['required', 'array', 'size:'.$slots['defense']];
            $rules['lineup.defense.*']    = ['required', 'integer', 'distinct', $exists];
            $rules['lineup.midfield']     = ['required', 'array', 'size:'.$slots['midfield']];
            $rules['lineup.midfield.*']   = ['required', 'integer', 'distinct', $exists];
            $rules['lineup.attack']       = ['required', 'array', 'size:'.$slots['attack']];
            $rules['lineup.attack.*']     = ['required', 'integer', 'distinct', $exists];
        }

        return $rules;
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

    /**
     * Resolve the campaign for this token and return which awards are
     * active. Falls back to "all three" if no category carries an
     * award_type (which is also the ballot's fallback).
     *
     * @return array{0:bool,1:bool,2:bool}
     */
    private function configuredAwards(): array
    {
        $token = $this->route('token');
        if (! $token) {
            return [true, true, true];
        }

        $row = CampaignClub::with('campaign')
            ->where('voting_link_token', $token)
            ->first();

        if (! $row || ! $row->campaign) {
            return [true, true, true];
        }

        // Delegates to Campaign::configuredAwards() — the single
        // source of truth for "which awards does this campaign run?"
        $a = $row->campaign->configuredAwards();
        return [$a['saudi'], $a['foreign'], $a['tos']];
    }
}
