<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Users\Actions\LogActivityAction;

final class ActivateVotingCampaignAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(Campaign $campaign): Campaign
    {
        if (! $campaign->status->canTransitionTo(CampaignStatus::Active)) {
            throw new \DomainException("Cannot activate campaign from {$campaign->status->value}.");
        }

        $campaign->update(['status' => CampaignStatus::Active->value]);
        $this->log->execute('campaigns.activated', $campaign);

        return $campaign->fresh();
    }
}
