<?php

declare(strict_types=1);

namespace App\Modules\Results\Domain;

use App\Modules\Campaigns\Enums\ResultsVisibility;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Models\CampaignResult;

/**
 * Single source of truth for "can this entity see the results?".
 *
 * Admin path is permission-gated upstream. This rule is specifically
 * about PUBLIC visibility.
 */
final class ResultVisibilityRule
{
    public function isPublic(Campaign $campaign, ?CampaignResult $result): bool
    {
        if (! $result) return false;
        if ($result->status !== ResultStatus::Announced) return false;
        if ($campaign->results_visibility !== ResultsVisibility::Announced) return false;
        return true;
    }

    /** Admin can always see, regardless of visibility. */
    public function isVisibleToAdmin(?CampaignResult $result): bool
    {
        return $result !== null;
    }
}
