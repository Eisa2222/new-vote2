<?php

declare(strict_types=1);

namespace App\Modules\Results\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Results\Domain\ResultTieBreakerRule;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Events\ResultsCalculated;
use App\Modules\Results\Models\CampaignResult;
use App\Modules\Voting\Models\VoteItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Calculates / recalculates a campaign's results.
 *
 * Writes:
 *   - campaign_results.total_votes, calculated_at, calculated_by, status
 *   - result_items.votes_count, vote_percentage, rank, is_winner, position, metadata
 *
 * Uses ResultTieBreakerRule for deterministic ordering on ties.
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

            // group by category, apply tie-breaker, then compute rank + winners
            $categories = $campaign->categories;
            $byCategory = $rows->groupBy('voting_category_id');

            foreach ($categories as $category) {
                $tallies = $this->tieBreaker->sort($byCategory[$category->id] ?? collect());
                $categoryTotal = $tallies->sum('votes_count') ?: 1;
                $winnersTaken = 0;

                foreach ($tallies as $i => $row) {
                    $isWinner = $winnersTaken < (int) $category->required_picks;
                    if ($isWinner) $winnersTaken++;

                    $result->items()->create([
                        'voting_category_id' => $category->id,
                        'candidate_id'       => $row->candidate_id,
                        'position'           => $category->position_slot ?? null,
                        'votes_count'        => $row->votes_count,
                        'vote_percentage'    => round(($row->votes_count / $categoryTotal) * 100, 2),
                        'rank'               => $i + 1,
                        'is_winner'          => $isWinner,
                        'is_announced'       => false,
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
}
