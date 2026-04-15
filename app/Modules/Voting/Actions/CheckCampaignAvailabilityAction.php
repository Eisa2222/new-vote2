<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Services\CampaignAvailabilityService;

/** Thin Action wrapper so controllers can call the check without touching the service directly. */
final class CheckCampaignAvailabilityAction
{
    public function __construct(private readonly CampaignAvailabilityService $svc) {}

    public function execute(Campaign $campaign): array
    {
        $reason = $this->svc->reasonFor($campaign);
        return [
            'available' => $reason === CampaignAvailabilityService::OK,
            'reason'    => $reason,
            'message'   => $this->svc->messageFor($reason),
        ];
    }
}
