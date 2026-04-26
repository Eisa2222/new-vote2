<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Services;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;

/**
 * Single source of truth for "is this campaign currently voteable?"
 *
 * Returns a reason code so the UI and API can localize messages consistently.
 */
final class CampaignAvailabilityService
{
    public const OK                  = 'ok';
    public const NOT_PUBLISHED       = 'not_published';
    public const NOT_STARTED         = 'not_started';
    public const ENDED               = 'ended';
    public const CLOSED              = 'closed';
    public const MAX_VOTERS_REACHED  = 'max_voters_reached';

    public function reasonFor(Campaign $campaign): string
    {
        $status = $campaign->status;

        // Check max_voters FIRST — when a campaign auto-closes because
        // it filled up, its status becomes Closed, but the voter deserves
        // the real reason ("Voter limit reached") rather than a generic
        // "Campaign closed" message.
        //
        // P0-1 perf — prefer the cached `votes_count` set by
        // `withCount('votes')` on the calling query. This service is
        // hit on every public listing render (50× per page on
        // /campaigns) and the bare votes()->count() forced 50 extra
        // SQL aggregates per render. Falls back to the live query
        // only if the count wasn't pre-loaded.
        if ($campaign->max_voters !== null) {
            $votesCount = $campaign->votes_count
                ?? $campaign->votes()->count();
            if ($votesCount >= $campaign->max_voters) {
                return self::MAX_VOTERS_REACHED;
            }
        }

        if ($status === CampaignStatus::Draft || $status === CampaignStatus::Archived) {
            return self::NOT_PUBLISHED;
        }
        if ($status === CampaignStatus::Closed) {
            return self::CLOSED;
        }
        if ($campaign->start_at->isFuture()) {
            return self::NOT_STARTED;
        }
        if ($campaign->end_at->isPast()) {
            return self::ENDED;
        }

        return self::OK;
    }

    public function isAvailable(Campaign $c): bool
    {
        return $this->reasonFor($c) === self::OK;
    }

    public function messageFor(string $reason): string
    {
        return match ($reason) {
            self::NOT_PUBLISHED      => __('This campaign is not published yet.'),
            self::NOT_STARTED        => __('This campaign has not started yet.'),
            self::ENDED              => __('This campaign has ended.'),
            self::CLOSED             => __('This campaign is closed.'),
            self::MAX_VOTERS_REACHED => __('This campaign has reached the maximum number of voters.'),
            self::OK                 => __('Voting is open.'),
            default                  => __('This campaign is not available.'),
        };
    }
}
