<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions\Club;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\NationalityType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Enums\AwardType;
use App\Modules\Voting\Exceptions\VotingException;
use App\Modules\Voting\Support\Formation;

/**
 * Server-side re-check of every business rule that the form *already*
 * enforces on the client. Never trust the browser.
 *
 * Inputs:
 *   $payload structure:
 *     [
 *       'best_saudi_player_id'   => 17,
 *       'best_foreign_player_id' => 42,
 *       'lineup' => [
 *         'goalkeeper' => [91],
 *         'defense'    => [12, 13, 14, 15],
 *         'midfield'   => [21, 22, 23],
 *         'attack'     => [31, 32, 33],
 *       ],
 *     ]
 *
 * Throws VotingException with a human message on the first violation.
 */
final class ValidateVoteRestrictionsAction
{
    public function __construct(
        private readonly GetEligibleCandidatesAction $eligibles,
    ) {}

    public function execute(Campaign $campaign, Player $voter, array $payload): void
    {
        // Which awards does this campaign actually run? Mirrors the
        // logic in ClubVotingController::ballot() + SubmitClubVoteRequest
        // — if the admin tagged voting_categories with an award_type,
        // only those awards are live on the ballot, so we should only
        // validate the submitted picks for those awards. Validating a
        // non-configured award here always failed because the payload
        // doesn't carry it.
        [$showSaudi, $showForeign, $showTos] = $this->configuredAwards($campaign);

        // ── 1. Individual awards. PHP enums cannot be array keys, so
        // use a plain array of tuples instead of `enum => meta`.
        $awards = [];
        if ($showSaudi) {
            $awards[] = [AwardType::BestSaudi,   'best_saudi_player_id',   NationalityType::Saudi];
        }
        if ($showForeign) {
            $awards[] = [AwardType::BestForeign, 'best_foreign_player_id', NationalityType::Foreign];
        }
        foreach ($awards as [$award, $key, $expectedNationality]) {
            $id = $payload[$key] ?? null;
            if (! $id) {
                throw new VotingException(__(':award is required.', ['award' => $award->label()]));
            }
            $pick = Player::find($id);
            if (! $pick || $pick->status->value !== 'active') {
                throw new VotingException(__('One of your picks is not available.'));
            }
            if ($pick->nationality !== $expectedNationality) {
                throw new VotingException(__(':award must be a :type player.', [
                    'award' => $award->label(),
                    'type'  => $expectedNationality->label(),
                ]));
            }
            // Self / teammate rules run BEFORE the shortlist pool
            // check so the voter gets the precise "you cannot vote for
            // yourself" error (the eligible pool already excludes self
            // + teammates, so otherwise a self-pick would surface as
            // the generic "not among nominees" message).
            $this->enforceSelfAndTeammate($campaign, $voter, $pick, $award);
            // H-1 fix — enforce the admin-curated shortlist server-side.
            // Without this, a voter can tamper best_saudi_player_id in the
            // POST body and cast the vote for any active Saudi player
            // anywhere in the DB, silently inflating a non-nominee.
            $this->assertInEligiblePool($campaign, $voter, $pick, $award);
        }

        // ── 2. Team of the Season — only when TOS is a configured award
        if ($showTos) {
            $lineup = $payload['lineup'] ?? null;
            if (! is_array($lineup)) {
                throw new VotingException(__('Team of the Season lineup is required.'));
            }
            $this->validateLineup($campaign, $voter, $lineup);
        }
    }

    /**
     * @return array{0:bool,1:bool,2:bool}
     */
    private function configuredAwards(Campaign $campaign): array
    {
        $configured = $campaign->categories()
            ->whereNotNull('award_type')
            ->where('is_active', true)
            ->pluck('award_type')
            ->map(fn ($v) => $v instanceof AwardType ? $v->value : $v)
            ->unique()
            ->all();

        if (empty($configured)) {
            return [true, true, true];
        }

        return [
            in_array(AwardType::BestSaudi->value,       $configured, true),
            in_array(AwardType::BestForeign->value,     $configured, true),
            in_array(AwardType::TeamOfTheSeason->value, $configured, true),
        ];
    }

    /**
     * Re-run the eligible-candidate query the ballot rendered from and
     * assert the submitted id is in that set. This covers both shortlist
     * mode (admin picked N nominees per award) and default mode (all
     * active players matching nationality/position/league/self/teammate
     * rules).
     */
    private function assertInEligiblePool(
        Campaign $campaign,
        Player $voter,
        Player $pick,
        AwardType $award,
        ?PlayerPosition $position = null,
    ): void {
        $allowed = $this->eligibles->execute($campaign, $voter, $award, $position)
            ->pluck('id')->all();
        if (! in_array($pick->id, $allowed, true)) {
            throw new VotingException(__('One of your picks is not among the eligible nominees.'));
        }
    }

    private function enforceSelfAndTeammate(Campaign $campaign, Player $voter, Player $pick, AwardType $award): void
    {
        if (! $campaign->allow_self_vote && $pick->id === $voter->id) {
            throw new VotingException(__('You cannot vote for yourself.'));
        }
        if (! $campaign->allow_teammate_vote && $pick->club_id === $voter->club_id && $pick->id !== $voter->id) {
            throw new VotingException(__('You cannot vote for a teammate.'));
        }
    }

    private function validateLineup(Campaign $campaign, Player $voter, array $lineup): void
    {
        $expected = Formation::slots();
        $allIds   = [];

        foreach ($expected as $slot => $required) {
            $ids = $lineup[$slot] ?? [];
            if (! is_array($ids) || count($ids) !== $required) {
                throw new VotingException(__(':slot requires exactly :n player(s).', [
                    'slot' => __(ucfirst($slot)),
                    'n'    => $required,
                ]));
            }

            foreach ($ids as $pid) {
                $p = Player::find($pid);
                if (! $p || $p->status->value !== 'active') {
                    throw new VotingException(__('One of your picks is not available.'));
                }
                if ($p->position !== PlayerPosition::from($slot)) {
                    throw new VotingException(__(':name is not a :slot.', [
                        'name' => $p->localized('name'),
                        'slot' => __(ucfirst($slot)),
                    ]));
                }
                $this->enforceSelfAndTeammate($campaign, $voter, $p, AwardType::TeamOfTheSeason);
                // Same shortlist enforcement as for individual awards —
                // after the self/teammate gate so the precise message
                // wins when that's the actual violation.
                $this->assertInEligiblePool(
                    $campaign, $voter, $p,
                    AwardType::TeamOfTheSeason,
                    PlayerPosition::from($slot),
                );
                $allIds[] = $p->id;
            }
        }

        if (count($allIds) !== count(array_unique($allIds))) {
            throw new VotingException(__('A player cannot appear in more than one line.'));
        }
    }
}
