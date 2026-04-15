<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Actions\CloseVotingCampaignAction;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\VotingCategory;
use App\Modules\Campaigns\Services\CampaignAvailabilityService;
use App\Modules\Voting\Domain\VoterIdentityStrategy;
use App\Modules\Voting\Events\VoteSubmitted;
use App\Modules\Voting\Exceptions\VotingException;
use App\Modules\Voting\Models\Vote;
use App\Modules\Voting\Services\LiveVoterCountService;
use App\Modules\Voting\Services\VoteEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SubmitVoteAction
{
    public function __construct(
        private readonly VoterIdentityStrategy $identity,
        private readonly CloseVotingCampaignAction $close,
        private readonly CampaignAvailabilityService $availability,
        private readonly VoteEligibilityService $eligibility,
        private readonly LiveVoterCountService $counter,
    ) {}

    /**
     * @param  array<int, array{category_id:int, candidate_ids:int[]}>  $selections
     */
    public function execute(Campaign $campaign, Request $request, array $selections): Vote
    {
        $reason = $this->availability->reasonFor($campaign);
        if ($reason !== CampaignAvailabilityService::OK) {
            throw new VotingException($this->availability->messageFor($reason));
        }

        $voterId = $this->identity->identify($request, $campaign->id);

        return DB::transaction(function () use ($campaign, $request, $selections, $voterId) {
            // Lock the campaign row: serialize max_voters + auto-close transition.
            $locked = Campaign::whereKey($campaign->id)->lockForUpdate()->first();

            $reason = $this->availability->reasonFor($locked);
            if ($reason !== CampaignAvailabilityService::OK) {
                throw new VotingException($this->availability->messageFor($reason));
            }

            if ($this->eligibility->hasAlreadyVoted($locked, $voterId)) {
                throw new VotingException(__('You have already voted in this campaign.'));
            }

            $this->validateSelections($locked, $selections);

            $vote = Vote::create([
                'campaign_id'      => $locked->id,
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

            // Bust the live voter-count cache so admin polling sees the update immediately.
            $this->counter->forget($locked);
            event(new VoteSubmitted($vote));

            if ($locked->fresh()->reachedMaxVoters()) {
                $this->close->execute($locked->fresh(), 'max_voters_reached');
            }

            return $vote->load('items');
        });
    }

    private function validateSelections(Campaign $campaign, array $selections): void
    {
        $categories = $campaign->categories()
            ->where('is_active', true)
            ->with(['candidates' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->keyBy('id');

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

            $picks = array_values(array_unique($s['candidate_ids']));
            $min = $cat->effectiveMin();
            $max = $cat->effectiveMax();

            if (count($picks) < $min || count($picks) > $max) {
                throw new VotingException(__('Category :name requires between :min and :max picks.', [
                    'name' => $cat->title_en, 'min' => $min, 'max' => $max,
                ]));
            }

            $valid = $cat->candidates->pluck('id')->all();
            if (array_diff($picks, $valid) !== []) {
                throw new VotingException(__('One or more candidates are not valid for their category.'));
            }
        }

        if ($categories->keys()->diff(array_keys($seen))->isNotEmpty()) {
            throw new VotingException(__('All categories must be answered.'));
        }

        if ($campaign->type === CampaignType::TeamOfTheSeason) {
            (new \App\Modules\Campaigns\Domain\TeamOfTheSeasonDistributionRule())->validate(
                $categories->map(fn ($c) => [
                    'position_slot'  => $c->position_slot,
                    'required_picks' => $c->effectiveMax(),
                ])->values()->all()
            );
        }
    }
}
