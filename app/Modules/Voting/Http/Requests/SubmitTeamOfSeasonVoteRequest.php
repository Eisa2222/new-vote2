<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Requests;

use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;
use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dynamic sizes per campaign — reads the formation from the campaign's
 * categories so any valid formation (4-3-3, 3-4-3, 5-3-2, ...) is accepted.
 */
final class SubmitTeamOfSeasonVoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $token = $this->route('token');
        $campaign = Campaign::where('public_token', $token)
            ->with('categories')->firstOrFail();
        $f = TeamOfSeasonFormation::fromCampaign($campaign);

        return [
            'attack'       => ['required', 'array', 'size:'.$f['attack']],
            'attack.*'     => ['integer', 'exists:voting_category_candidates,id'],
            'midfield'     => ['required', 'array', 'size:'.$f['midfield']],
            'midfield.*'   => ['integer', 'exists:voting_category_candidates,id'],
            'defense'      => ['required', 'array', 'size:'.$f['defense']],
            'defense.*'    => ['integer', 'exists:voting_category_candidates,id'],
            'goalkeeper'   => ['required', 'array', 'size:'.$f['goalkeeper']],
            'goalkeeper.*' => ['integer', 'exists:voting_category_candidates,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'attack.size'     => __('Attack line requires exactly :size players.'),
            'midfield.size'   => __('Midfield line requires exactly :size players.'),
            'defense.size'    => __('Defense line requires exactly :size players.'),
            'goalkeeper.size' => __('Goalkeeper line requires exactly :size players.'),
        ];
    }
}
