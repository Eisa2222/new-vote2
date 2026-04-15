<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Voting\Models\Vote;

final class PreventDuplicateVoteByPlayerAction
{
    public function hasVoted(Campaign $campaign, int $playerId): bool
    {
        return Vote::where('campaign_id', $campaign->id)
            ->where('verified_player_id', $playerId)
            ->exists();
    }
}
