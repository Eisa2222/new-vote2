<?php

declare(strict_types=1);

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Voting\Models\Vote;

function makeActiveIndividualCampaign(?int $maxVoters = null): Campaign
{
    seedRolesAndPermissions();
    $voter = makePlayer([
        'position'      => PlayerPosition::Attack,
        'jersey_number' => 9,
        'national_id'   => '1000000001',
        'mobile_number' => '0501110001',
    ]);
    $other = makePlayer([
        'position'      => PlayerPosition::Attack,
        'club_id'       => makeClub()->id,
        'jersey_number' => 10,
        'national_id'   => '1000000002',
        'mobile_number' => '0501110002',
    ]);

    $c = Campaign::create([
        'title_ar'   => 'أفضل لاعب', 'title_en' => 'Player of the Year',
        'type'       => CampaignType::IndividualAward->value,
        'start_at'   => now()->subHour(),
        'end_at'     => now()->addDay(),
        'max_voters' => $maxVoters,
        'status'     => CampaignStatus::Active->value,
    ]);
    $cat = $c->categories()->create([
        'title_ar' => 'الأفضل', 'title_en' => 'Best',
        'position_slot' => 'any', 'required_picks' => 1,
        'selection_min' => 1, 'selection_max' => 1, 'is_active' => true,
    ]);
    $cat->candidates()->create(['player_id' => $voter->id, 'is_active' => true, 'display_order' => 0]);
    $cat->candidates()->create(['player_id' => $other->id, 'is_active' => true, 'display_order' => 1]);

    return $c->load('categories.candidates');
}

/** Verifies as the first candidate's national_id (any active player works). */
function verifyAs(Campaign $c, string $nationalId): void
{
    test()->post(route('voting.verify', $c->public_token), ['national_id' => $nationalId])
        ->assertRedirect(route('voting.form', $c->public_token));
}

it('accepts a valid public vote (after verification)', function () {
    $c = makeActiveIndividualCampaign();
    $cat = $c->categories->first();
    $cand = $cat->candidates->first();

    verifyAs($c, '1000000001');
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]],
    ])->assertRedirect(route('voting.thanks', $c->public_token));

    expect(Vote::count())->toBe(1);
    expect(Vote::first()->is_verified)->toBeTrue();
});

it('prevents the same verified player from voting twice', function () {
    $c = makeActiveIndividualCampaign();
    $cat = $c->categories->first();
    $cand = $cat->candidates->first();
    $payload = ['selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]]];

    verifyAs($c, '1000000001');
    $this->post(route('voting.submit', $c->public_token), $payload)->assertRedirect();

    // Second verify attempt should fail (player already voted).
    $this->post(route('voting.verify', $c->public_token), ['national_id' => '1000000001'])
        ->assertSessionHasErrors(['identity']);

    expect(Vote::count())->toBe(1);
});

it('auto-closes the campaign when max_voters reached', function () {
    $c = makeActiveIndividualCampaign(maxVoters: 1);
    $cat = $c->categories->first();
    $cand = $cat->candidates->first();

    verifyAs($c, '1000000001');
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]],
    ]);

    expect($c->fresh()->status->value)->toBe('closed');
});

it('blocks the vote page when campaign is not active', function () {
    $c = makeActiveIndividualCampaign();
    $c->update(['status' => CampaignStatus::Draft->value]);
    // Now renders the friendly "unavailable" view (HTTP 200) instead of 410.
    $this->get("/vote/{$c->public_token}")
        ->assertStatus(200)
        ->assertSee(__('Not open yet'));
});

it('blocks the vote page after end date', function () {
    $c = makeActiveIndividualCampaign();
    $c->update(['end_at' => now()->subMinute()]);
    $this->get("/vote/{$c->public_token}")
        ->assertStatus(200)
        ->assertSee(__('Voting has ended'));
});

it('shows a voter-limit-reached page once max_voters is hit', function () {
    $c = makeActiveIndividualCampaign(maxVoters: 1);
    $cat = $c->categories->first();
    $cand = $cat->candidates->first();
    verifyAs($c, '1000000001');
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]],
    ]);

    // A second visitor sees the friendly limit page instead of a 410.
    $this->get("/vote/{$c->public_token}")
        ->assertStatus(200)
        ->assertSee(__('Voter limit reached'));
});

it('rejects wrong number of picks even when verified', function () {
    $c = makeActiveIndividualCampaign();
    $cat = $c->categories->first();
    $ids = $cat->candidates->pluck('id')->all();

    verifyAs($c, '1000000001');
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => $ids]],
    ])->assertSessionHasErrors();

    expect(Vote::count())->toBe(0);
});

it('returns 404 for unknown campaign tokens', function () {
    $this->get('/vote/this-token-does-not-exist')->assertNotFound();
});

it('has a unique public_token per campaign', function () {
    $c1 = makeActiveIndividualCampaign();
    $c2 = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x',
        'type' => 'individual_award',
        'start_at' => now(), 'end_at' => now()->addDay(),
        'status' => 'draft',
    ]);
    expect($c1->public_token)->not->toBe($c2->public_token);
});
