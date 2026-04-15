<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Users\Actions\LogActivityAction;

final class ArchiveVotingCampaignAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(Campaign $campaign): Campaign
    {
        if (! $campaign->status->canTransitionTo(CampaignStatus::Archived)) {
            throw new \DomainException("Cannot archive campaign from {$campaign->status->value}.");
        }

        $campaign->update(['status' => CampaignStatus::Archived->value]);
        $this->log->execute('campaigns.archived', $campaign);

        return $campaign->fresh();
    }
}
