<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions\Club;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\NationalityType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Enums\AwardType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Returns the candidate list for a given award, filtered by the
 * voting rules that apply to the voter who is currently signed in.
 *
 *   award_type = best_saudi       → saudi players
 *   award_type = best_foreign     → foreign players
 *   award_type = team_of_the_season → all active players; caller
 *                                     filters by position when
 *                                     rendering each pitch slot.
 *
 * Extra restrictions applied here (from the spec):
 *   • Rule 5 allow_self_vote=false     → exclude the voter herself
 *   • Rule 6 allow_teammate_vote=false → exclude voter's clubmates
 *   • Campaign.league_id                → only candidates from that league
 *
 * Callers usually want `->groupBy('club_id')` output for the Team-of-
 * Season pitch popup, so this action just returns the filtered
 * Player collection — grouping is the view's job.
 */
final class GetEligibleCandidatesAction
{
    public function execute(
        Campaign $campaign,
        Player $voter,
        AwardType $award,
        ?PlayerPosition $position = null,
    ): Collection {
        $q = Player::query()->active();

        // League scope — when the campaign targets one league, only
        // its players can be voted for.
        if ($campaign->league_id) {
            $q->where(function ($w) use ($campaign) {
                $w->where('league_id', $campaign->league_id)
                  ->orWhereIn('club_id', $this->clubIdsInLeague($campaign->league_id));
            });
        }

        // Nationality filter per award.
        if ($award === AwardType::BestSaudi) {
            $q->where('nationality', NationalityType::Saudi->value);
        } elseif ($award === AwardType::BestForeign) {
            $q->where('nationality', NationalityType::Foreign->value);
        }

        // Position filter for TOS slots.
        if ($award === AwardType::TeamOfTheSeason && $position !== null) {
            $q->where('position', $position->value);
        }

        // Rule 5 — self-vote.
        if (! $campaign->allow_self_vote) {
            $q->where('id', '<>', $voter->id);
        }

        // Rule 6 — teammate-vote.
        if (! $campaign->allow_teammate_vote) {
            $q->where('club_id', '<>', $voter->club_id);
        }

        return $q->with('club')->orderBy('name_en')->get();
    }

    /** Club ids attached to the league via leagues↔clubs pivot. */
    private function clubIdsInLeague(int $leagueId): array
    {
        $league = \App\Modules\Leagues\Models\League::find($leagueId);
        if (! $league) return [];
        return method_exists($league, 'clubs')
            ? $league->clubs()->pluck('clubs.id')->all()
            : [];
    }
}
