<?php

declare(strict_types=1);

namespace App\Modules\Voting\Services;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Voting\Models\Vote;

/** Decides whether a given voter identifier can still vote in a given campaign. */
final class VoteEligibilityService
{
    public function hasAlreadyVoted(Campaign $campaign, string $voterIdentifier): bool
    {
        return Vote::where('campaign_id', $campaign->id)
            ->where('voter_identifier', $voterIdentifier)
            ->exists();
    }
}
