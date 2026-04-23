<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions\Club;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Voting\Exceptions\VotingException;
use App\Modules\Voting\Models\CampaignClub;

/**
 * Gate check for anyone trying to enter a club-scoped voting link.
 *
 * Throws VotingException with a user-friendly reason if the link is
 * not usable right now. Controllers catch the exception and render
 * the appropriate "unavailable" view.
 *
 * Rules (from the spec, section "Rule 8: صلاحية الحملة"):
 *   1. CampaignClub row exists and is_active
 *   2. Campaign.status === Active
 *   3. now() ∈ [start_at, end_at]
 *   4. current_voters_count < max_voters (null = unlimited)
 */
final class ValidateClubVotingEntryAction
{
    public function execute(CampaignClub $row): void
    {
        if (! $row->is_active) {
            throw new VotingException(__('This voting link has been disabled.'));
        }

        $campaign = $row->campaign ?: $row->campaign()->first();
        if (! $campaign) {
            throw new VotingException(__('Campaign not found.'));
        }

        if ($campaign->status !== CampaignStatus::Active) {
            throw new VotingException(match ($campaign->status->value) {
                'draft', 'pending_approval', 'rejected', 'published'
                    => __('Voting has not started yet.'),
                'closed', 'archived'
                    => __('This campaign has ended.'),
                default
                    => __('Voting is not available.'),
            });
        }

        $now = now();
        if ($campaign->start_at && $now->lt($campaign->start_at)) {
            throw new VotingException(__('Voting opens on :date.', ['date' => $campaign->start_at->format('Y-m-d H:i')]));
        }
        if ($campaign->end_at && $now->gt($campaign->end_at)) {
            throw new VotingException(__('This campaign has ended.'));
        }

        if ($row->isFull()) {
            throw new VotingException(__('The maximum number of voters for this club has been reached.'));
        }
    }
}
