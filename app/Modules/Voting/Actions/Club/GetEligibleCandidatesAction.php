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
 * Candidate list for a given award. Logic flow:
 *
 *   1. If the admin has configured voting_categories with an
 *      `award_type` pointing to this award, USE those categories'
 *      candidates (shortlist mode). This is the behaviour the spec
 *      asks for: when the admin picks 5 nominees for Best Saudi,
 *      the voter must see only those 5 — never the whole DB.
 *
 *   2. Otherwise fall back to "all active players matching the
 *      nationality / position" — the convenient default for a
 *      campaign that didn't pre-select nominees.
 *
 * Business rules layered on top (run in either mode):
 *   • Rule 5 allow_self_vote=false     → exclude the voter herself
 *   • Rule 6 allow_teammate_vote=false → exclude voter's clubmates
 *   • League scope (Campaign.league_id)→ limit to that league
 */
final class GetEligibleCandidatesAction
{
    public function execute(
        Campaign $campaign,
        Player $voter,
        AwardType $award,
        ?PlayerPosition $position = null,
    ): Collection {
        $shortlist = $this->shortlistFromCategories($campaign, $award);

        if ($shortlist !== null) {
            // Honour allow_self / allow_teammate + position on the
            // admin-curated shortlist so the view never has to.
            $shortlist = $shortlist
                ->when(! $campaign->allow_self_vote,     fn ($c) => $c->where('id', '<>', $voter->id))
                ->when(! $campaign->allow_teammate_vote, fn ($c) => $c->where('club_id', '<>', $voter->club_id))
                ->when($award === AwardType::TeamOfTheSeason && $position !== null,
                    fn ($c) => $c->where('position', $position->value),
                );

            return $shortlist
                ->load('club')
                ->sortBy(fn ($p) => $p->name_en)
                ->values();
        }

        // ── Default "all-by-nationality" path ──
        $q = Player::query()->active();

        if ($campaign->league_id) {
            $q->where(function ($w) use ($campaign) {
                $w->where('league_id', $campaign->league_id)
                  ->orWhereIn('club_id', $this->clubIdsInLeague($campaign->league_id));
            });
        }

        if ($award === AwardType::BestSaudi) {
            $q->where('nationality', NationalityType::Saudi->value);
        } elseif ($award === AwardType::BestForeign) {
            $q->where('nationality', NationalityType::Foreign->value);
        }

        if ($award === AwardType::TeamOfTheSeason && $position !== null) {
            $q->where('position', $position->value);
        }

        if (! $campaign->allow_self_vote) {
            $q->where('id', '<>', $voter->id);
        }
        if (! $campaign->allow_teammate_vote) {
            $q->where('club_id', '<>', $voter->club_id);
        }

        return $q->with('club')->orderBy('name_en')->get();
    }

    /**
     * Collect players explicitly attached to the campaign's categories
     * whose award_type matches this award. Returns an Eloquent
     * Collection of Player models (unique by id) or null if no such
     * categories exist.
     *
     * Important: must return an Eloquent\Collection (not a plain
     * Support\Collection) because execute() calls `->load('club')`
     * on the result — a method that only exists on Eloquent
     * collections. Building it via `collect()->push(...)` produced a
     * Support\Collection and blew up at runtime with:
     *
     *   "Return value must be of type ?Eloquent\Collection,
     *    Support\Collection returned"
     */
    private function shortlistFromCategories(Campaign $campaign, AwardType $award): ?Collection
    {
        $categories = $campaign->categories()
            ->where('award_type', $award->value)
            ->where('is_active', true)
            ->with('candidates.player')
            ->get();

        if ($categories->isEmpty()) {
            return null;
        }

        $players = [];
        foreach ($categories as $cat) {
            foreach ($cat->candidates as $cand) {
                if ($cand->player) $players[$cand->player->id] = $cand->player;
            }
        }

        // Eloquent\Collection — keeps `->load(...)` and other Eloquent
        // helpers available downstream.
        return new Collection(array_values($players));
    }

    private function clubIdsInLeague(int $leagueId): array
    {
        $league = \App\Modules\Leagues\Models\League::find($leagueId);
        if (! $league) return [];
        return method_exists($league, 'clubs')
            ? $league->clubs()->pluck('clubs.id')->all()
            : [];
    }
}
