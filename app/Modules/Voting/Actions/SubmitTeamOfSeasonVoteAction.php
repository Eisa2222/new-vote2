<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Voting\Models\Vote;
use Illuminate\Http\Request;

/**
 * Thin wrapper that turns the slot-based TOS payload into the standard
 * {category_id, candidate_ids} selections the generic SubmitVoteAction eats.
 */
final class SubmitTeamOfSeasonVoteAction
{
    public function __construct(
        private readonly ValidateTeamOfSeasonSelectionAction $validator,
        private readonly SubmitVoteAction $submit,
    ) {}

    /**
     * @param  array<string, int[]>  $payload  keyed by slot
     */
    public function execute(Campaign $campaign, Request $request, array $payload): Vote
    {
        $selections = $this->validator->execute($campaign, $payload);
        return $this->submit->execute($campaign, $request, $selections);
    }
}
