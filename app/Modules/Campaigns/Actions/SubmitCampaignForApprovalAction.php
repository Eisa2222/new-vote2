<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Users\Actions\LogActivityAction;

/**
 * Admin → committee: "please review this campaign".
 * Transitions Draft (or Rejected) into PendingApproval.
 */
final class SubmitCampaignForApprovalAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(Campaign $campaign): Campaign
    {
        $from = $campaign->status;
        if (! $from->canTransitionTo(CampaignStatus::PendingApproval)) {
            throw new \DomainException(
                __('Only Draft or Rejected campaigns can be submitted for approval.'),
            );
        }

        // No category guard any more: under the club-scoped flow an
        // empty categories set is a valid shape — the ballot falls
        // back to the three default awards (Best Saudi / Best Foreign
        // / Team of the Season) sourced directly from the club rosters.
        // Admins who want curated shortlists still add categories on
        // the per-campaign Categories page, but it isn't required.

        $campaign->update([
            'status' => CampaignStatus::PendingApproval->value,
        ]);

        $this->log->execute('campaigns.submitted_for_approval', $campaign, [
            'from' => $from->value,
        ]);

        return $campaign->fresh();
    }
}
