<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Voting\Models\Vote;

final class PreventDuplicateVoteByPlayerAction
{
    /**
     * Has this player already cast a vote in this campaign?
     *
     * Security P0-2 fix — the original implementation only checked
     * `verified_player_id` (legacy /vote/{token} flow). The new
     * club-scoped /vote/club/{token} flow stores its voter in
     * `votes.player_id`. A voter who came in via the club link
     * could therefore re-verify on the legacy URL of the same
     * campaign and cast a second vote because this guard ignored
     * the new column. Now both columns are covered.
     */
    public function hasVoted(Campaign $campaign, int $playerId): bool
    {
        return Vote::where('campaign_id', $campaign->id)
            ->where(function ($q) use ($playerId) {
                $q->where('verified_player_id', $playerId)
                  ->orWhere('player_id', $playerId);
            })
            ->exists();
    }
}
