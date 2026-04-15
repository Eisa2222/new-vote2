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
    $p1 = makePlayer(['position' => PlayerPosition::Attack, 'jersey_number' => 9]);
    $p2 = makePlayer(['position' => PlayerPosition::Attack, 'club_id' => makeClub()->id, 'jersey_number' => 10]);

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
        'position_slot' => 'any', 'required_picks' => 1, 'display_order' => 0,
    ]);
    $cat->candidates()->create(['player_id' => $p1->id, 'display_order' => 0]);
    $cat->candidates()->create(['player_id' => $p2->id, 'display_order' => 1]);

    return $c->load('categories.candidates');
}

it('accepts a valid public vote', function () {
    $c = makeActiveIndividualCampaign();
    $cat = $c->categories->first();
    $cand = $cat->candidates->first();

    $this->post("/vote/{$c->public_token}", [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]],
    ])->assertRedirect(route('voting.thanks', $c->public_token));

    expect(Vote::count())->toBe(1);
});

it('prevents duplicate voting from same ip/user-agent', function () {
    $c = makeActiveIndividualCampaign();
    $cat = $c->categories->first();
    $cand = $cat->candidates->first();
    $payload = ['selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]]];

    $this->post("/vote/{$c->public_token}", $payload)->assertRedirect();
    $this->post("/vote/{$c->public_token}", $payload)->assertSessionHasErrors();

    expect(Vote::count())->toBe(1);
});

it('auto-closes the campaign when max_voters reached', function () {
    $c = makeActiveIndividualCampaign(maxVoters: 1);
    $cat = $c->categories->first();
    $cand = $cat->candidates->first();

    $this->post("/vote/{$c->public_token}", [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => [$cand->id]]],
    ]);

    expect($c->fresh()->status->value)->toBe('closed');
});

it('blocks the vote page when campaign is not active', function () {
    $c = makeActiveIndividualCampaign();
    $c->update(['status' => CampaignStatus::Draft->value]);

    $this->get("/vote/{$c->public_token}")->assertStatus(410);
});

it('blocks the vote page after end date', function () {
    $c = makeActiveIndividualCampaign();
    $c->update(['end_at' => now()->subMinute()]);

    $this->get("/vote/{$c->public_token}")->assertStatus(410);
});

it('rejects wrong number of picks', function () {
    $c = makeActiveIndividualCampaign();
    $cat = $c->categories->first();
    $ids = $cat->candidates->pluck('id')->all();

    $this->post("/vote/{$c->public_token}", [
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
