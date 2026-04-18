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

        // Guard: a campaign with zero categories can't be voted on — reject
        // submission early with a friendly DomainException (which the
        // controller renders as a flash error) instead of letting the
        // committee approve it and then hit an empty-voting experience.
        if ($campaign->categories()->count() === 0) {
            throw new \DomainException(
                __('Add at least one category and candidate before submitting for approval.'),
            );
        }

        $campaign->update([
            'status' => CampaignStatus::PendingApproval->value,
        ]);

        $this->log->execute('campaigns.submitted_for_approval', $campaign, [
            'from' => $from->value,
        ]);

        return $campaign->fresh();
    }
}
