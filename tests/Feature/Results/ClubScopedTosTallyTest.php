<?php

declare(strict_types=1);

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Enums\NationalityType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Results\Actions\CalculateCampaignResultsAction;
use App\Modules\Voting\Enums\AwardType;
use App\Modules\Voting\Models\Vote;

/**
 * Guardrail: after the committee calculates results for a club-scoped
 * TOS campaign, every winner's `position` column on the result item
 * must match the position that voters actually cast their votes for.
 *
 * This protects against a regression where the lazy-materialiser in
 * CalculateCampaignResultsAction could land the attack votes into the
 * midfield category (or vice versa) — which would make the
 * announcement pitch put players on the wrong line.
 */

beforeEach(function () {
    seedRolesAndPermissions();
});

it('places club-scoped TOS winners in their voted position on results', function () {
    $club = Club::create(['name_ar' => 'نادٍ', 'name_en' => 'Club', 'status' => 'active']);

    // Voter (same club so the ballot would accept them).
    $voter = makePlayer([
        'position' => PlayerPosition::Midfield,
        'nationality' => NationalityType::Saudi,
        'club_id' => $club->id,
        'jersey_number' => 1,
    ]);

    // One player per position slot, all from the same club for simplicity.
    $attacker  = makePlayer(['position' => PlayerPosition::Attack,     'club_id' => $club->id, 'jersey_number' => 70]);
    $midfielder = makePlayer(['position' => PlayerPosition::Midfield,  'club_id' => $club->id, 'jersey_number' => 80]);
    $defender  = makePlayer(['position' => PlayerPosition::Defense,    'club_id' => $club->id, 'jersey_number' => 40]);
    $keeper    = makePlayer(['position' => PlayerPosition::Goalkeeper, 'club_id' => $club->id, 'jersey_number' => 99]);

    $campaign = Campaign::create([
        'title_ar' => 'الحملة', 'title_en' => 'Campaign',
        'type'     => 'individual_award',
        'start_at' => now()->subDay(),
        'end_at'   => now()->subHour(),
        'status'   => 'closed',
    ]);

    // Synthesise a club-scoped vote directly (bypassing the HTTP flow)
    // so the test stays focused on the tally + materialiser contract.
    $vote = Vote::create([
        'campaign_id'      => $campaign->id,
        'player_id'        => $voter->id,
        'club_id'          => $club->id,
        'voter_identifier' => 'test-voter-'.$voter->id,
        'submitted_at'     => now(),
    ]);

    // Only a partial lineup to keep the fixture small — enough to prove
    // each pick ends up on its intended line. Note: the voter places
    // $attacker into the attack slot, $midfielder into midfield, etc.
    $picks = [
        ['position_key' => 'attack',     'candidate_player_id' => $attacker->id],
        ['position_key' => 'midfield',   'candidate_player_id' => $midfielder->id],
        ['position_key' => 'defense',    'candidate_player_id' => $defender->id],
        ['position_key' => 'goalkeeper', 'candidate_player_id' => $keeper->id],
    ];
    foreach ($picks as $p) {
        $vote->items()->create([
            'award_type'          => AwardType::TeamOfTheSeason->value,
            'category_key'        => 'tos_'.$p['position_key'],
            'position_key'        => $p['position_key'],
            'candidate_player_id' => $p['candidate_player_id'],
        ]);
    }

    // Calculate — this triggers materialiseClubScopedShape() which
    // creates voting_categories keyed by position_slot and backfills
    // voting_category_id on the existing vote_items.
    $result = (new CalculateCampaignResultsAction())->execute($campaign);

    // Every winner must be in the position slot the voter chose.
    $winners = $result->items()->where('is_winner', true)
        ->with(['candidate.player', 'category'])
        ->get();

    $expected = [
        $attacker->id   => 'attack',
        $midfielder->id => 'midfield',
        $defender->id   => 'defense',
        $keeper->id     => 'goalkeeper',
    ];

    foreach ($winners as $w) {
        $pid = $w->candidate->player->id;
        if (! isset($expected[$pid])) continue;

        expect($w->category->position_slot)
            ->toBe($expected[$pid],
                "Player #{$pid} was voted into {$expected[$pid]} but landed in {$w->category->position_slot}");

        // The position column on the result item itself should match
        // too (it's what the view's grouping falls back to).
        expect($w->position)->toBe($expected[$pid]);
    }
});
