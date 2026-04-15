<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Services;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;

/**
 * Pure functions around the campaign state machine. Kept stateless so the
 * same rules apply to both the scheduled job and interactive admin actions.
 */
final class CampaignLifecycleService
{
    public function shouldActivate(Campaign $c): bool
    {
        return $c->status === CampaignStatus::Published
            && $c->start_at->isPast()
            && $c->end_at->isFuture();
    }

    public function shouldClose(Campaign $c): bool
    {
        return in_array($c->status, [CampaignStatus::Active, CampaignStatus::Published], true)
            && $c->end_at->isPast();
    }
}
