<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions\Club;

use App\Modules\Players\Models\Player;
use App\Modules\Voting\Models\CampaignClub;
use Illuminate\Database\Eloquent\Collection;

/**
 * The dropdown shown on the club-entry page.
 *
 * Returns the list of players who are allowed to sign in to vote via
 * this particular CampaignClub link:
 *   • active
 *   • belong to the club named in the link
 *   • belong to one of the leagues targeted by the campaign
 *     (when the campaign targets specific leagues; otherwise all
 *     active club members are eligible)
 */
final class ResolveClubPlayerSelectionAction
{
    public function execute(CampaignClub $row): Collection
    {
        $campaign = $row->campaign;

        $q = Player::query()
            ->active()
            ->where('club_id', $row->club_id);

        // If the campaign is scoped to a specific league, the voter
        // (the player picking their own name) must belong to it. We
        // only filter when the campaign explicitly targets a league
        // — otherwise we fall through to the full club roster.
        if ($campaign && $campaign->league_id) {
            $q->where(function ($w) use ($campaign) {
                $w->where('league_id', $campaign->league_id)
                  ->orWhereNull('league_id'); // tolerate legacy rows without a league
            });
        }

        return $q->orderBy('name_en')->get();
    }
}
