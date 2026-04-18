<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Queries;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Bundles everything the admin campaigns index screen needs.
 * Keeps the controller a one-liner and the view free of DB calls.
 */
final class CampaignIndexData
{
    /** Status values we surface in the header counter grid. */
    private const COUNTED_STATUSES = [
        CampaignStatus::Draft,
        CampaignStatus::PendingApproval,
        CampaignStatus::Published,
        CampaignStatus::Active,
        CampaignStatus::Closed,
    ];

    /**
     * @return array{
     *     campaigns: LengthAwarePaginator,
     *     counts: array<string,int>,
     *     pending: Collection
     * }
     */
    public function fetch(int $perPage): array
    {
        return [
            'campaigns' => Campaign::withCount('votes')
                ->orderByDesc('id')
                ->paginate($perPage),

            'counts'    => $this->countsByStatus(),

            'pending'   => Campaign::where('status', CampaignStatus::PendingApproval->value)
                ->orderByDesc('id')
                ->get(),
        ];
    }

    /**
     * One grouped query instead of five (old code ran COUNT per status).
     *
     * @return array<string,int>
     */
    private function countsByStatus(): array
    {
        $rows = Campaign::query()
            ->whereIn('status', array_map(fn (CampaignStatus $status) => $status->value, self::COUNTED_STATUSES))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        // Normalise to every key so the view can look up without isset().
        $counts = [];
        foreach (self::COUNTED_STATUSES as $status) {
            $counts[$status->value] = (int) ($rows[$status->value] ?? 0);
        }
        return $counts;
    }
}
