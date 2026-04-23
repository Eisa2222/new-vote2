<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions\Club;

use App\Modules\Voting\Models\CampaignClub;

/**
 * Atomically bump CampaignClub.current_voters_count. Uses an
 * increment() so it's safe under concurrent submits from the same
 * club link without a race.
 *
 * Called inside SubmitClubVoteAction's DB transaction so the
 * counter stays in lock-step with the votes table: if the vote
 * insert rolls back, the counter increment rolls back too.
 */
final class IncrementCampaignClubVoterCountAction
{
    public function execute(CampaignClub $row): void
    {
        $row->increment('current_voters_count');
    }
}
