<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Leagues\Models\League;
use App\Modules\Players\Models\Player;

/**
 * Given a TOS campaign and a league, attach every active player that
 * belongs to a club in that league to the matching line (goalkeeper /
 * defense / midfield / attack). Per-line DomainExceptions are swallowed
 * so a bad row for one position never blocks the rest.
 *
 * Returns a structured summary:
 *  [
 *    'attached'          => int,
 *    'no_clubs'          => bool,  // league has no clubs linked
 *    'no_active_players' => bool,  // league has clubs but no active positioned players
 *  ]
 */
final class AutoPopulateTeamOfSeasonFromLeagueAction
{
    public function __construct(
        private readonly AttachTeamOfSeasonCandidatesAction $attach,
    ) {}

    public function execute(Campaign $campaign, int $leagueId): array
    {
        $clubIds = League::find($leagueId)?->clubs()->pluck('clubs.id')->all() ?? [];
        if (empty($clubIds)) {
            return ['attached' => 0, 'no_clubs' => true, 'no_active_players' => false];
        }

        $byPosition = Player::active()
            ->whereIn('club_id', $clubIds)
            ->whereNotNull('position')
            ->get()
            ->groupBy(fn ($p) => $p->position?->value);

        if ($byPosition->flatten()->isEmpty()) {
            return ['attached' => 0, 'no_clubs' => false, 'no_active_players' => true];
        }

        $attached = 0;
        foreach ($byPosition as $slot => $group) {
            $category = $campaign->categories()->where('position_slot', $slot)->first();
            if (! $category) continue;
            try {
                $attached += $this->attach->execute($campaign, $category, $group->pluck('id')->all());
            } catch (\DomainException) {
                // Skip this line; keep going. The summary still reflects what was attached.
            }
        }

        return ['attached' => $attached, 'no_clubs' => false, 'no_active_players' => false];
    }
}
