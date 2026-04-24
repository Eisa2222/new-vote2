<?php

declare(strict_types=1);

namespace App\Modules\Results\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\VotingCategory;
use App\Modules\Campaigns\Models\VotingCategoryCandidate;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Results\Domain\ResultTieBreakerRule;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Events\ResultsCalculated;
use App\Modules\Results\Models\CampaignResult;
use App\Modules\Voting\Enums\AwardType;
use App\Modules\Voting\Models\VoteItem;
use App\Modules\Voting\Support\Formation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Calculates / recalculates a campaign's results.
 *
 * Winner selection rules:
 *   - Sort by votes desc, then the deterministic tie-breaker
 *     (display_order, candidate_id).
 *   - Walk the sorted list: every candidate strictly above the cutoff
 *     is an unambiguous winner.
 *   - If a tie group STRADDLES the cutoff (e.g. 5 candidates tied on
 *     the same vote count but only 3 winner slots left), EVERY
 *     candidate in that tie group is marked `needs_committee_decision`
 *     and `is_winner` is left NULL until the committee picks manually.
 *
 *  Approval and announcement are blocked while any tie is unresolved.
 */
final class CalculateCampaignResultsAction
{
    public function __construct(private readonly ResultTieBreakerRule $tieBreaker = new ResultTieBreakerRule()) {}

    public function execute(Campaign $campaign): CampaignResult
    {
        return DB::transaction(function () use ($campaign) {
            $result = CampaignResult::firstOrCreate(
                ['campaign_id' => $campaign->id],
                ['status' => ResultStatus::PendingCalculation->value],
            );
            $result->items()->delete();

            $totalVotes = (int) $campaign->votes()->count();

            // Lazy-materialise voting_categories + voting_category_candidates
            // for the club-scoped flow so the downstream tally + view layer
            // can treat both the legacy (pre-attached categories) and the
            // new (on-the-fly from club rosters) flow uniformly.
            $this->materialiseClubScopedShape($campaign);

            $rows = VoteItem::query()
                ->join('voting_category_candidates as c', 'c.id', '=', 'vote_items.candidate_id')
                ->selectRaw('vote_items.voting_category_id as voting_category_id,
                             vote_items.candidate_id       as candidate_id,
                             c.display_order               as display_order,
                             COUNT(*)                       as votes_count')
                ->whereIn('vote_id', fn ($q) => $q->select('id')->from('votes')
                    ->where('campaign_id', $campaign->id))
                ->groupBy('vote_items.voting_category_id', 'vote_items.candidate_id', 'c.display_order')
                ->get();

            $categories = $campaign->categories()->get(); // fresh after materialise
            $byCategory = $rows->groupBy('voting_category_id');

            foreach ($categories as $category) {
                $tallies       = $this->tieBreaker->sort($byCategory[$category->id] ?? collect());
                $categoryTotal = $tallies->sum('votes_count') ?: 1;
                $requiredPicks = (int) $category->required_picks;

                // Group tallies into tie-groups (same votes_count).
                // Walk the groups filling winner slots; the first group that
                // overflows is flagged as needing a committee decision.
                $winnersAssigned   = 0;
                $ambiguousCandIds  = [];    // candidate_ids in the tied-at-cutoff group
                $groups            = $tallies->groupBy('votes_count')->values(); // already desc-sorted

                foreach ($groups as $group) {
                    $groupSize = $group->count();
                    $remaining = $requiredPicks - $winnersAssigned;

                    if ($remaining <= 0) {
                        // all remaining candidates are non-winners
                        break;
                    }
                    if ($groupSize <= $remaining) {
                        // whole group is won
                        $winnersAssigned += $groupSize;
                    } else {
                        // tie straddles the cutoff — flag every member
                        foreach ($group as $r) $ambiguousCandIds[] = $r->candidate_id;
                        break;
                    }
                }

                foreach ($tallies as $i => $row) {
                    $isAmbiguous = in_array($row->candidate_id, $ambiguousCandIds, true);
                    $rank        = $i + 1;
                    $isWinner    = $isAmbiguous
                        ? null                        // committee must decide
                        : ($rank <= $requiredPicks);  // otherwise deterministic

                    $result->items()->create([
                        'voting_category_id'       => $category->id,
                        'candidate_id'             => $row->candidate_id,
                        'position'                 => $category->position_slot ?? null,
                        'votes_count'              => $row->votes_count,
                        'vote_percentage'          => round(($row->votes_count / $categoryTotal) * 100, 2),
                        'rank'                     => $rank,
                        'is_winner'                => $isWinner,
                        'needs_committee_decision' => $isAmbiguous,
                        'is_announced'             => false,
                    ]);
                }
            }

            $result->update([
                'status'        => ResultStatus::Calculated->value,
                'calculated_at' => now(),
                'calculated_by' => Auth::id(),
                'total_votes'   => $totalVotes,
            ]);

            event(new ResultsCalculated($result));

            return $result->fresh('items');
        });
    }

    /**
     * The club-scoped voting flow writes vote_items with `award_type`,
     * `category_key`, and `candidate_player_id` — but NOT
     * voting_category_id / candidate_id. The tally query above depends
     * on the legacy (category, candidate) shape, so on first
     * calculation we synthesise matching voting_categories +
     * voting_category_candidates rows and backfill the legacy columns
     * on the existing vote_items. This is idempotent: re-running picks
     * up any new votes cast since the last calculation.
     *
     * Naming:
     *   • Best Saudi / Best Foreign → one category each, position_slot=any
     *   • Team of the Season        → four categories, one per position
     *                                  slot (attack / midfield / defense /
     *                                  goalkeeper) with required_picks
     *                                  matching the formation shape.
     */
    private function materialiseClubScopedShape(Campaign $campaign): void
    {
        // Is there anything to do? Only proceed when the campaign has
        // club-scoped vote_items still missing their legacy FKs.
        $pending = VoteItem::query()
            ->whereNull('voting_category_id')
            ->whereIn('vote_id', fn ($q) => $q->select('id')->from('votes')
                ->where('campaign_id', $campaign->id))
            ->exists();
        if (! $pending) {
            return;
        }

        $slots = Formation::slots(); // ['goalkeeper'=>1, 'defense'=>4, ...]

        // Map from (award_type, position_key|null) → VotingCategory id
        $categoryId = [];

        $ensureCategory = function (string $awardType, ?string $positionKey) use ($campaign, $slots, &$categoryId): int {
            $key = $awardType.'|'.($positionKey ?? 'any');
            if (isset($categoryId[$key])) return $categoryId[$key];

            [$titleAr, $titleEn, $slot, $requiredPicks] = match (true) {
                $awardType === AwardType::BestSaudi->value
                    => ['أفضل لاعب سعودي', 'Best Saudi Player', 'any', 1],
                $awardType === AwardType::BestForeign->value
                    => ['أفضل لاعب أجنبي', 'Best Foreign Player', 'any', 1],
                $awardType === AwardType::TeamOfTheSeason->value
                    => [
                        'تشكيلة الموسم — '.PlayerPosition::from($positionKey ?? 'attack')->label(),
                        'Team of the Season — '.ucfirst($positionKey ?? 'attack'),
                        $positionKey ?? 'any',
                        $slots[$positionKey ?? 'attack'] ?? 1,
                    ],
                default => [$awardType, $awardType, 'any', 1],
            };

            $cat = VotingCategory::firstOrCreate(
                [
                    'campaign_id'   => $campaign->id,
                    'award_type'    => $awardType,
                    'position_slot' => $slot,
                ],
                [
                    'title_ar'       => $titleAr,
                    'title_en'       => $titleEn,
                    'required_picks' => $requiredPicks,
                    'is_active'      => true,
                    'display_order'  => $this->awardDisplayOrder($awardType),
                ],
            );
            return $categoryId[$key] = $cat->id;
        };

        // Map (category_id, player_id) → candidate_id
        $candidateId = [];

        // Walk every club-scoped vote_item missing a legacy FK and
        // backfill it in place. This is bounded by the votes for this
        // campaign (max ~roster_count × num_awards) so doing it in PHP
        // is fine and keeps the logic readable.
        VoteItem::query()
            ->whereNull('voting_category_id')
            ->whereNotNull('award_type')
            ->whereNotNull('candidate_player_id')
            ->whereIn('vote_id', fn ($q) => $q->select('id')->from('votes')
                ->where('campaign_id', $campaign->id))
            ->orderBy('id')
            ->chunkById(500, function ($items) use ($campaign, $ensureCategory, &$candidateId) {
                foreach ($items as $vi) {
                    // award_type is cast to AwardType enum on VoteItem;
                    // the category-materialiser closure works in plain
                    // strings so it can key a map. Normalise here.
                    $awardValue = $vi->award_type instanceof AwardType
                        ? $vi->award_type->value
                        : (string) $vi->award_type;
                    $catId = $ensureCategory($awardValue, $vi->position_key);

                    $candKey = $catId.':'.$vi->candidate_player_id;
                    if (! isset($candidateId[$candKey])) {
                        $cand = VotingCategoryCandidate::firstOrCreate(
                            [
                                'voting_category_id' => $catId,
                                'player_id'          => $vi->candidate_player_id,
                            ],
                            ['display_order' => 0, 'is_active' => true],
                        );
                        $candidateId[$candKey] = $cand->id;
                    }

                    $vi->update([
                        'voting_category_id' => $catId,
                        'candidate_id'       => $candidateId[$candKey],
                    ]);
                }
            });
    }

    private function awardDisplayOrder(string $awardType): int
    {
        return match ($awardType) {
            AwardType::BestSaudi->value      => 10,
            AwardType::BestForeign->value    => 20,
            AwardType::TeamOfTheSeason->value => 30,
            default                          => 99,
        };
    }
}
