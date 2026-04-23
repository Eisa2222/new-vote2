<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions\Club;

use App\Modules\Players\Models\Player;
use App\Modules\Voting\Enums\AwardType;
use App\Modules\Voting\Exceptions\VotingException;
use App\Modules\Voting\Models\CampaignClub;
use App\Modules\Voting\Models\Vote;
use App\Modules\Voting\Models\VoteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The heart of the club-scoped voting flow. Persists everything in a
 * single transaction:
 *
 *   1. creates the Vote row (player_id + club_id + campaign_club_id)
 *   2. creates one VoteItem per pick:
 *        • 1 for Best Saudi
 *        • 1 for Best Foreign
 *        • 11 for Team of the Season (one per pitch slot)
 *   3. increments CampaignClub.current_voters_count
 *
 * The unique (campaign_id, player_id) DB index makes duplicate votes
 * impossible — we still check beforehand for a friendlier error.
 */
final class SubmitClubVoteAction
{
    public function __construct(
        private readonly ValidateClubVotingEntryAction $gate,
        private readonly ValidateVoteRestrictionsAction $rules,
        private readonly IncrementCampaignClubVoterCountAction $bump,
    ) {}

    /**
     * @param  array{
     *   best_saudi_player_id:int,
     *   best_foreign_player_id:int,
     *   lineup:array<string,int[]>
     * }  $payload
     */
    public function execute(CampaignClub $row, Player $voter, array $payload, ?Request $request = null): Vote
    {
        // Re-check the link is still valid at submit time — between
        // the voter opening the form and hitting submit the campaign
        // could have closed or the club quota could have filled.
        $this->gate->execute($row);

        // Voter must belong to the club this link represents.
        if ($voter->club_id !== $row->club_id) {
            throw new VotingException(__('The selected player does not belong to this club.'));
        }

        // Duplicate guard. Also enforced by a unique DB index, but
        // catching here gives a clean message instead of a 23000.
        $already = Vote::where('campaign_id', $row->campaign_id)
            ->where('player_id', $voter->id)->exists();
        if ($already) {
            throw new VotingException(__('You have already voted in this campaign.'));
        }

        $this->rules->execute($row->campaign, $voter, $payload);

        return DB::transaction(function () use ($row, $voter, $payload, $request) {
            // M-1 fix — re-fetch the CampaignClub under row lock so the
            // max_voters check cannot be beaten by two simultaneous
            // submits (both could otherwise pass the outer gate when
            // count == max-1, landing the counter at max+1). The gate
            // re-check below happens inside the same transaction, so
            // any overrun by a concurrent write is caught here.
            $locked = CampaignClub::whereKey($row->id)->lockForUpdate()->first();
            if ($locked && $locked->isFull()) {
                throw new VotingException(__('This club has reached the maximum number of voters.'));
            }

            // L-2 fix — hash the voter_identifier so `player_id` cannot
            // be reconstructed from the votes table directly. The tuple
            // still collides exactly once (dedup still works) but it no
            // longer leaks on e.g. CSV exports.
            $voterIdent = hash_hmac(
                'sha256',
                'club:'.$voter->id.':'.$row->campaign_id,
                (string) config('app.key'),
            );

            $vote = Vote::create([
                'campaign_id'      => $row->campaign_id,
                'player_id'        => $voter->id,
                'club_id'          => $row->club_id,
                'campaign_club_id' => $row->id,
                'voter_identifier' => $voterIdent,
                'submitted_at'     => now(),
                'ip_address'       => $request?->ip(),
                'user_agent'       => substr((string) $request?->userAgent(), 0, 512),
            ]);

            $items = [];

            $items[] = [
                'award_type'          => AwardType::BestSaudi->value,
                'category_key'        => 'best_saudi',
                'candidate_player_id' => (int) $payload['best_saudi_player_id'],
            ];
            $items[] = [
                'award_type'          => AwardType::BestForeign->value,
                'category_key'        => 'best_foreign',
                'candidate_player_id' => (int) $payload['best_foreign_player_id'],
            ];

            foreach ($payload['lineup'] as $slot => $ids) {
                foreach ($ids as $pid) {
                    $items[] = [
                        'award_type'          => AwardType::TeamOfTheSeason->value,
                        'category_key'        => 'tos_'.$slot,
                        'position_key'        => $slot,
                        'candidate_player_id' => (int) $pid,
                    ];
                }
            }

            foreach ($items as $item) {
                VoteItem::create(array_merge($item, ['vote_id' => $vote->id]));
            }

            $this->bump->execute($row);

            return $vote;
        });
    }
}
