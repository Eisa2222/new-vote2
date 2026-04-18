<?php

declare(strict_types=1);

namespace App\Modules\Shared\Queries;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Models\Player;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Models\CampaignResult;
use App\Modules\Sports\Models\Sport;
use App\Modules\Voting\Models\Vote;
use Illuminate\Support\Collection;

/**
 * Assembles the data the admin dashboard needs. Replaces a bunch of
 * inline Eloquent calls that used to sit at the top of the Blade
 * view — keeps the view focused on presentation and makes the
 * queries testable in isolation.
 */
final class DashboardData
{
    private const RECENT_CAMPAIGNS_LIMIT = 5;
    private const ENDING_SOON_WINDOW_DAYS = 2;

    /**
     * @return array{
     *     counts: array{clubs:int,sports:int,players:int,active_campaigns:int},
     *     recent_campaigns: Collection,
     *     ending_soon: int,
     *     pending_approval: int,
     *     total_votes: int
     * }
     */
    public function fetch(): array
    {
        return [
            'counts' => [
                'clubs'            => Club::count(),
                'sports'           => Sport::count(),
                'players'          => Player::count(),
                'active_campaigns' => Campaign::where('status', CampaignStatus::Active->value)->count(),
            ],
            'recent_campaigns' => Campaign::orderByDesc('id')->take(self::RECENT_CAMPAIGNS_LIMIT)->get(),
            'ending_soon'      => Campaign::where('status', CampaignStatus::Active->value)
                ->whereBetween('end_at', [now(), now()->addDays(self::ENDING_SOON_WINDOW_DAYS)])
                ->count(),
            'pending_approval' => CampaignResult::where('status', ResultStatus::Calculated->value)->count(),
            'total_votes'      => Vote::count(),
        ];
    }
}
