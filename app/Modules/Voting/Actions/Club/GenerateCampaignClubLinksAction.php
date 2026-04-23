<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions\Club;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Voting\Models\CampaignClub;
use Illuminate\Support\Facades\DB;

/**
 * Ensures every targeted club has a CampaignClub row (and therefore a
 * unique voting link). Idempotent — can be called any time the admin
 * edits the campaign's club list; extra rows are created, missing
 * tokens are backfilled, existing rows are left alone.
 *
 * Params:
 *   $clubIds — ids of clubs to include
 *   $maxPerClub — optional cap applied when creating a row (existing
 *                 rows keep their own cap so admins can tune per-club)
 */
final class GenerateCampaignClubLinksAction
{
    /**
     * @param  array<int,int>  $clubIds
     */
    public function execute(Campaign $campaign, array $clubIds, ?int $maxPerClub = null): void
    {
        DB::transaction(function () use ($campaign, $clubIds, $maxPerClub) {
            // Normalise + drop unknowns so a tampered request cannot
            // attach a non-existent club id.
            $validIds = Club::whereIn('id', $clubIds)->pluck('id')->all();

            foreach ($validIds as $clubId) {
                CampaignClub::firstOrCreate(
                    ['campaign_id' => $campaign->id, 'club_id' => $clubId],
                    [
                        'max_voters'           => $maxPerClub,
                        'current_voters_count' => 0,
                        'is_active'            => true,
                    ],
                );
            }

            // Deactivate rows for clubs no longer in the list rather
            // than deleting them — preserves any historical votes +
            // audit trail that reference the row.
            CampaignClub::where('campaign_id', $campaign->id)
                ->whereNotIn('club_id', $validIds)
                ->update(['is_active' => false]);
        });
    }
}
