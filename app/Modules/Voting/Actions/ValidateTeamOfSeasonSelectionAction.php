<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Voting\Exceptions\VotingException;

/**
 * Validates a TOTS selection payload:
 *   [ 'attack' => [cid,...], 'midfield' => [...], 'defense' => [...], 'goalkeeper' => [...] ]
 *
 * Values are voting_category_candidates.id — NOT player ids — so a candidate
 * can only be picked where the admin wired it in.
 *
 * Returns a flat list of selections the SubmitVoteAction can consume.
 */
final class ValidateTeamOfSeasonSelectionAction
{
    /**
     * @param  array<string, int[]>  $payload  keyed by slot
     * @return array<int, array{category_id:int, candidate_ids:int[]}>
     */
    public function execute(Campaign $campaign, array $payload): array
    {
        $formation = TeamOfSeasonFormation::slots();

        // 1. Reject unknown keys
        $unknown = array_diff(array_keys($payload), array_keys($formation));
        if ($unknown) {
            throw new VotingException(__('Unexpected keys in selection: :keys', ['keys' => implode(',', $unknown)]));
        }

        // 2. Exact count per slot
        foreach ($formation as $slot => $expected) {
            $got = count(array_unique($payload[$slot] ?? []));
            if ($got !== $expected) {
                throw new VotingException(__(
                    'Line :slot requires exactly :n players (got :got).',
                    ['slot' => __(ucfirst($slot)), 'n' => $expected, 'got' => $got],
                ));
            }
        }

        // 3. No duplicate candidate ids across lines
        $all = array_merge(...array_values($payload));
        if (count($all) !== count(array_unique($all))) {
            throw new VotingException(__('A player cannot appear in more than one line.'));
        }

        // 4. Every candidate must actually belong to its declared slot in this campaign
        $categories = $campaign->categories()
            ->where('is_active', true)
            ->with(['candidates' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->keyBy('position_slot');

        $selections = [];
        foreach ($formation as $slot => $expected) {
            $cat = $categories[$slot] ?? null;
            if (! $cat) {
                throw new VotingException(__('Campaign is not configured for Team of the Season.'));
            }
            $valid = $cat->candidates->pluck('id')->all();
            $invalid = array_diff($payload[$slot], $valid);
            if ($invalid) {
                throw new VotingException(__(
                    'Invalid player(s) for line :slot.', ['slot' => __(ucfirst($slot))],
                ));
            }
            $selections[] = [
                'category_id'   => $cat->id,
                'candidate_ids' => array_values(array_unique($payload[$slot])),
            ];
        }

        return $selections;
    }
}
