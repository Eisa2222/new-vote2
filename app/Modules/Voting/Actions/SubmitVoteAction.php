<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Actions\CloseVotingCampaignAction;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\VotingCategory;
use App\Modules\Voting\Domain\VoterIdentityStrategy;
use App\Modules\Voting\Events\VoteSubmitted;
use App\Modules\Voting\Exceptions\VotingException;
use App\Modules\Voting\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SubmitVoteAction
{
    public function __construct(
        private readonly VoterIdentityStrategy $identity,
        private readonly CloseVotingCampaignAction $close,
    ) {}

    /**
     * @param  array<int, array{category_id:int, candidate_ids:int[]}>  $selections
     */
    public function execute(Campaign $campaign, Request $request, array $selections): Vote
    {
        if (! $campaign->isAcceptingVotes()) {
            throw new VotingException(__('This campaign is not currently accepting votes.'));
        }

        $voterId = $this->identity->identify($request, $campaign->id);

        return DB::transaction(function () use ($campaign, $request, $selections, $voterId) {
            // Lock the campaign row so concurrent submissions serialize on the
            // max_voters check + close transition — prevents racing past the cap.
            $locked = Campaign::whereKey($campaign->id)->lockForUpdate()->first();

            if (! $locked->isAcceptingVotes()) {
                throw new VotingException(__('This campaign is not currently accepting votes.'));
            }
            if ($locked->reachedMaxVoters()) {
                throw new VotingException(__('This campaign has reached the maximum number of voters.'));
            }

            // Race-safe duplicate check is already backed by a UNIQUE index on
            // (campaign_id, voter_identifier). The inner query guards the UX.
            if (Vote::where('campaign_id', $campaign->id)
                ->where('voter_identifier', $voterId)->exists()) {
                throw new VotingException(__('You have already voted in this campaign.'));
            }

            $this->validateSelections($campaign, $selections);

            $vote = Vote::create([
                'campaign_id'      => $campaign->id,
                'voter_identifier' => $voterId,
                'ip_address'       => $request->ip(),
                'user_agent'       => substr((string) $request->userAgent(), 0, 512),
                'submitted_at'     => now(),
            ]);

            foreach ($selections as $s) {
                foreach ($s['candidate_ids'] as $cid) {
                    $vote->items()->create([
                        'voting_category_id' => $s['category_id'],
                        'candidate_id'       => $cid,
                    ]);
                }
            }

            event(new VoteSubmitted($vote));

            // Auto-close if this submission hit the cap (still inside the tx/lock).
            if ($locked->fresh()->reachedMaxVoters()) {
                $this->close->execute($locked->fresh(), 'max_voters_reached');
            }

            return $vote->load('items');
        });
    }

    private function validateSelections(Campaign $campaign, array $selections): void
    {
        $categories = $campaign->categories()->with('candidates:id,voting_category_id')->get()->keyBy('id');
        $seen = [];

        foreach ($selections as $s) {
            /** @var VotingCategory|null $cat */
            $cat = $categories[$s['category_id']] ?? null;
            if (! $cat || $cat->campaign_id !== $campaign->id) {
                throw new VotingException(__('Invalid category in submission.'));
            }
            if (isset($seen[$cat->id])) {
                throw new VotingException(__('Duplicate category in submission.'));
            }
            $seen[$cat->id] = true;

            $picks = array_unique($s['candidate_ids']);
            if (count($picks) !== (int) $cat->required_picks) {
                throw new VotingException(__('Category :name requires :n picks.', [
                    'name' => $cat->title_en, 'n' => $cat->required_picks,
                ]));
            }

            $valid = $cat->candidates->pluck('id')->all();
            if (array_diff($picks, $valid) !== []) {
                throw new VotingException(__('One or more candidates are not valid for their category.'));
            }
        }

        // All required categories must be answered
        $missing = $categories->keys()->diff(array_keys($seen));
        if ($missing->isNotEmpty()) {
            throw new VotingException(__('All categories must be answered.'));
        }

        // Team of the Season distribution safety-net (position_slot aggregates already enforce this,
        // but we double-check in case admin created a custom TOTS campaign).
        if ($campaign->type === CampaignType::TeamOfTheSeason) {
            (new \App\Modules\Campaigns\Domain\TeamOfTheSeasonDistributionRule())->validate(
                $categories->map(fn ($c) => [
                    'position_slot'  => $c->position_slot,
                    'required_picks' => $c->required_picks,
                ])->values()->all()
            );
        }
    }
}
