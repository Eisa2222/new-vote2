<?php

declare(strict_types=1);

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Enums\NationalityType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Actions\Club\GenerateCampaignClubLinksAction;
use App\Modules\Voting\Actions\Club\SubmitClubVoteAction;
use App\Modules\Voting\Actions\Club\ValidateVoteRestrictionsAction;
use App\Modules\Voting\Models\CampaignClub;
use App\Modules\Voting\Models\Vote;

/*
 * Feature tests for the new club-scoped voting flow.
 * Covers every business rule in the spec:
 *   1  entry via club token
 *   2  player dropdown scoped to the club roster
 *   3  duplicate-vote guard
 *   4  per-club max_voters cap
 *   5  allow_self_vote
 *   6  allow_teammate_vote
 *   7  nationality filter (Saudi / Foreign)
 *   8  campaign must be active
 *   9  optional profile capture
 */

beforeEach(function () {
    seedRolesAndPermissions();
    $this->sport = makeFootball();
});

function makeActiveCampaign(array $attrs = []): Campaign
{
    return Campaign::create(array_merge([
        'title_ar' => 'ح', 'title_en' => 'C',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Active->value,
        'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
        'allow_self_vote'     => true,
        'allow_teammate_vote' => true,
    ], $attrs));
}

function makeClubRoster(int $saudiCount = 11, int $foreignCount = 11, array $clubAttrs = []): array
{
    $club = Club::create(array_merge(['name_ar' => 'ن', 'name_en' => 'Club'.rand(1000,9999)], $clubAttrs));
    $football = makeFootball();
    $players = [];
    $positions = [PlayerPosition::Goalkeeper, PlayerPosition::Defense, PlayerPosition::Midfield, PlayerPosition::Attack];
    for ($i = 0; $i < $saudiCount; $i++) {
        $players[] = Player::create([
            'club_id' => $club->id, 'sport_id' => $football->id,
            'name_ar' => 'س'.$i, 'name_en' => 'S'.$i,
            'position' => $positions[$i % 4]->value,
            'nationality' => NationalityType::Saudi->value,
            'status' => 'active',
        ]);
    }
    for ($i = 0; $i < $foreignCount; $i++) {
        $players[] = Player::create([
            'club_id' => $club->id, 'sport_id' => $football->id,
            'name_ar' => 'أ'.$i, 'name_en' => 'F'.$i,
            'position' => $positions[$i % 4]->value,
            'nationality' => NationalityType::Foreign->value,
            'status' => 'active',
        ]);
    }
    return [$club, $players];
}

function buildValidPayload(Player $saudi, Player $foreign, array $pool): array
{
    // Split pool by position to craft a valid 1-4-3-3.
    $byPos = [
        'goalkeeper' => [], 'defense' => [], 'midfield' => [], 'attack' => [],
    ];
    foreach ($pool as $p) {
        $byPos[$p->position->value][] = $p->id;
    }
    return [
        'best_saudi_player_id'   => $saudi->id,
        'best_foreign_player_id' => $foreign->id,
        'lineup' => [
            'goalkeeper' => array_slice($byPos['goalkeeper'], 0, 1),
            'defense'    => array_slice($byPos['defense'],    0, 4),
            'midfield'   => array_slice($byPos['midfield'],   0, 3),
            'attack'     => array_slice($byPos['attack'],     0, 3),
        ],
    ];
}

// ─────────────────────────────────────────────────────────────

it('GenerateCampaignClubLinksAction creates one row per club', function () {
    $campaign = makeActiveCampaign();
    $c1 = Club::create(['name_ar' => 'ن1', 'name_en' => 'C1']);
    $c2 = Club::create(['name_ar' => 'ن2', 'name_en' => 'C2']);

    app(GenerateCampaignClubLinksAction::class)->execute($campaign, [$c1->id, $c2->id], 100);

    expect(CampaignClub::where('campaign_id', $campaign->id)->count())->toBe(2);
    $row = CampaignClub::first();
    expect($row->voting_link_token)->not->toBeEmpty();
    expect($row->max_voters)->toBe(100);
});

it('Entry page 200s and shows the club roster', function () {
    $campaign = makeActiveCampaign();
    [$club, $players] = makeClubRoster(3, 0);
    $row = CampaignClub::create([
        'campaign_id' => $campaign->id,
        'club_id'     => $club->id,
    ]);

    test()->get(route('voting.club.show', $row->voting_link_token))
        ->assertOk()
        ->assertSee($players[0]->name_en)
        ->assertSee($players[1]->name_en);
});

it('Entry page shows "ended" when campaign is closed', function () {
    $campaign = makeActiveCampaign(['status' => CampaignStatus::Closed->value]);
    [$club] = makeClubRoster(1, 0);
    $row = CampaignClub::create(['campaign_id' => $campaign->id, 'club_id' => $club->id]);

    test()->get(route('voting.club.show', $row->voting_link_token))
        ->assertOk()
        ->assertSee(__('This campaign has ended'));
});

it('Start rejects a player who does not belong to the club', function () {
    $campaign = makeActiveCampaign();
    [$club] = makeClubRoster(1, 0);
    $row = CampaignClub::create(['campaign_id' => $campaign->id, 'club_id' => $club->id]);

    $otherClub = Club::create(['name_ar' => 'آخر', 'name_en' => 'Other']);
    $football = makeFootball();
    $otherPlayer = Player::create([
        'club_id' => $otherClub->id, 'sport_id' => $football->id,
        'name_ar' => 'x', 'name_en' => 'x',
        'position' => 'attack', 'nationality' => 'saudi', 'status' => 'active',
    ]);

    test()->post(route('voting.club.start', $row->voting_link_token), ['player_id' => $otherPlayer->id])
        ->assertStatus(422);
});

it('Validate rejects duplicate vote', function () {
    $campaign = makeActiveCampaign();
    [$club, $players] = makeClubRoster(8, 8);
    $row = CampaignClub::create(['campaign_id' => $campaign->id, 'club_id' => $club->id]);
    $voter = $players[0];

    $saudiPick  = collect($players)->first(fn ($p) => $p->isSaudi()   && $p->id !== $voter->id);
    $foreignPick = collect($players)->first(fn ($p) => $p->isForeign() && $p->id !== $voter->id);
    $payload = buildValidPayload($saudiPick, $foreignPick, $players);

    $submit = app(SubmitClubVoteAction::class);
    $submit->execute($row, $voter, $payload);

    // Second attempt with the same voter → exception
    expect(fn () => $submit->execute($row, $voter, $payload))
        ->toThrow(\App\Modules\Voting\Exceptions\VotingException::class);
});

it('Enforces per-club max_voters', function () {
    $campaign = makeActiveCampaign();
    [$club, $players] = makeClubRoster(8, 8);
    $row = CampaignClub::create([
        'campaign_id' => $campaign->id, 'club_id' => $club->id,
        'max_voters'  => 1, 'current_voters_count' => 1, // already full
    ]);

    $voter = $players[0];
    $saudiPick   = collect($players)->first(fn ($p) => $p->isSaudi()   && $p->id !== $voter->id);
    $foreignPick = collect($players)->first(fn ($p) => $p->isForeign() && $p->id !== $voter->id);
    $payload = buildValidPayload($saudiPick, $foreignPick, $players);

    expect(fn () => app(SubmitClubVoteAction::class)->execute($row, $voter, $payload))
        ->toThrow(\App\Modules\Voting\Exceptions\VotingException::class, 'maximum number of voters');
});

it('Rejects self-vote when allow_self_vote is false', function () {
    $campaign = makeActiveCampaign(['allow_self_vote' => false]);
    [$club, $players] = makeClubRoster(8, 8);
    $voter = collect($players)->first(fn ($p) => $p->isSaudi());
    $foreign = collect($players)->first(fn ($p) => $p->isForeign());

    // voter picks themself as best_saudi → should fail
    $payload = buildValidPayload($voter, $foreign, $players);

    expect(fn () => app(ValidateVoteRestrictionsAction::class)->execute($campaign, $voter, $payload))
        ->toThrow(\App\Modules\Voting\Exceptions\VotingException::class, 'cannot vote for yourself');
});

it('Rejects teammate pick when allow_teammate_vote is false', function () {
    $campaign = makeActiveCampaign(['allow_teammate_vote' => false]);
    [$club, $players] = makeClubRoster(8, 8);
    $voter   = $players[0];
    $teamSaudi   = collect($players)->first(fn ($p) => $p->isSaudi() && $p->id !== $voter->id);
    $teamForeign = collect($players)->first(fn ($p) => $p->isForeign());

    $payload = buildValidPayload($teamSaudi, $teamForeign, $players);

    expect(fn () => app(ValidateVoteRestrictionsAction::class)->execute($campaign, $voter, $payload))
        ->toThrow(\App\Modules\Voting\Exceptions\VotingException::class, 'teammate');
});

it('Rejects nationality mismatch on Best Saudi', function () {
    $campaign = makeActiveCampaign();
    [$club, $players] = makeClubRoster(8, 8);
    $voter = $players[0];

    $wrongSaudi  = collect($players)->first(fn ($p) => $p->isForeign()); // foreign! should fail
    $foreignPick = collect($players)->first(fn ($p) => $p->isForeign());
    $payload = buildValidPayload($wrongSaudi, $foreignPick, $players);

    expect(fn () => app(ValidateVoteRestrictionsAction::class)->execute($campaign, $voter, $payload))
        ->toThrow(\App\Modules\Voting\Exceptions\VotingException::class, 'must be a');
});

it('Successful submit creates vote + 13 items + increments counter', function () {
    $campaign = makeActiveCampaign();
    [$club, $players] = makeClubRoster(8, 8);
    $row = CampaignClub::create(['campaign_id' => $campaign->id, 'club_id' => $club->id]);
    $voter = $players[0];

    $saudiPick   = collect($players)->first(fn ($p) => $p->isSaudi()   && $p->id !== $voter->id);
    $foreignPick = collect($players)->first(fn ($p) => $p->isForeign());
    $payload = buildValidPayload($saudiPick, $foreignPick, $players);

    app(SubmitClubVoteAction::class)->execute($row, $voter, $payload);

    expect(Vote::where('campaign_id', $campaign->id)->where('player_id', $voter->id)->count())->toBe(1);
    // 1 best_saudi + 1 best_foreign + 11 tos slots = 13 items
    $vote = Vote::where('player_id', $voter->id)->first();
    expect($vote->items()->count())->toBe(13);
    expect($row->fresh()->current_voters_count)->toBe(1);
});

it('SaveOptionalVoterProfile writes back to the player row', function () {
    [, $players] = makeClubRoster(1, 0);
    $player = $players[0];

    app(\App\Modules\Voting\Actions\Club\SaveOptionalVoterProfileAction::class)->execute($player, [
        'email'         => 'NEW@Example.COM',
        'mobile_number' => '0501234567',
    ]);

    $fresh = $player->fresh();
    expect($fresh->email)->toBe('new@example.com');
    expect($fresh->mobile_number)->toBe('0501234567');
});

/**
 * HTTP smoke test for the full club-flow submit path. Pins the
 * regression that was reported as "POST /submit returns 302 but I
 * never see the success page": the redirect target must be the
 * success route, not the entry/show route (which would mean the
 * voter session was dropped between ballot render and submit).
 */
it('HTTP submit redirects to success after a valid vote', function () {
    [$club, $pool] = makeClubRoster(11, 11);
    $voter = $pool[0];

    $campaign = makeActiveCampaign();
    app(GenerateCampaignClubLinksAction::class)
        ->execute($campaign, [$club->id]);

    $row = CampaignClub::where('campaign_id', $campaign->id)
        ->where('club_id', $club->id)->firstOrFail();
    $token = $row->voting_link_token;

    // Step 1: hit the entry page (creates a session, sets CSRF).
    $this->get(route('voting.club.show', $token))->assertOk();

    // Step 2: start — picks the voter from the roster, elevates the
    // session ("club_voter:{token}" key) and rotates the session id.
    $this->post(route('voting.club.start', $token), [
        'player_id' => $voter->id,
    ])->assertRedirect(route('voting.club.ballot', $token));

    // Step 3: ballot renders.
    $this->get(route('voting.club.ballot', $token))->assertOk();

    // Step 4: build a valid payload and submit. MUST land on success,
    // not bounce back to /show (which would mean session was lost).
    $saudi   = collect($pool)->first(fn ($p) => $p->nationality?->value === 'saudi');
    $foreign = collect($pool)->first(fn ($p) => $p->nationality?->value === 'foreign');
    $payload = buildValidPayload($saudi, $foreign, $pool);

    $this->post(route('voting.club.submit', $token), $payload)
        ->assertRedirect(route('voting.club.success', $token));

    // The submit recorded a vote for this player.
    expect(Vote::where('campaign_id', $campaign->id)
        ->where('player_id', $voter->id)->exists())->toBeTrue();
});
