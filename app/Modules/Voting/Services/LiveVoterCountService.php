<?php

declare(strict_types=1);

namespace App\Modules\Voting\Services;

use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Support\Facades\Cache;

/**
 * Cheap, short-lived cache in front of the votes count query so the
 * dashboard and admin polling endpoints don't hammer the DB.
 */
final class LiveVoterCountService
{
    private const TTL_SECONDS = 5;

    public function count(Campaign $campaign): int
    {
        return Cache::remember(
            "campaign:{$campaign->id}:voters_count",
            self::TTL_SECONDS,
            fn () => (int) $campaign->votes()->count(),
        );
    }

    public function stats(Campaign $campaign): array
    {
        $count = $this->count($campaign);
        $max   = $campaign->max_voters;

        return [
            'campaign_id' => $campaign->id,
            'votes_count' => $count,
            'max_voters'  => $max,
            'percentage'  => $max ? min(100, round(($count / $max) * 100, 2)) : null,
            'status'      => $campaign->status?->value,
            'near_limit'  => $max ? ($count >= $max * 0.8) : false,
        ];
    }

    public function forget(Campaign $campaign): void
    {
        Cache::forget("campaign:{$campaign->id}:voters_count");
    }
}
