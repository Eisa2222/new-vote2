<?php

declare(strict_types=1);

use App\Modules\Campaigns\Models\Campaign;

function multiChoiceCampaign(int $min, int $max): Campaign
{
    seedRolesAndPermissions();
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x',
        'type' => 'individual_award',
        'start_at' => now()->subHour(), 'end_at' => now()->addDay(),
        'status' => 'active',
    ]);
    $cat = $c->categories()->create([
        'title_ar' => 'multi', 'title_en' => 'multi',
        'category_type' => 'multiple_choice',
        'position_slot' => 'any',
        'required_picks' => $max,
        'selection_min' => $min,
        'selection_max' => $max,
        'is_active' => true,
    ]);
    for ($i = 0; $i < 5; $i++) {
        $p = makePlayer([
            'club_id'       => makeClub()->id,
            'jersey_number' => $i + 1,
            'national_id'   => '300000000'.$i,
        ]);
        $cat->candidates()->create([
            'candidate_type' => 'player', 'player_id' => $p->id,
            'display_order' => $i, 'is_active' => true,
        ]);
    }
    return $c->load('categories.candidates');
}

function verifyMc(Campaign $c): void
{
    test()->post(route('voting.verify', $c->public_token), ['national_id' => '3000000000'])
        ->assertRedirect(route('voting.form', $c->public_token));
}

it('accepts a submission within selection_min..selection_max range', function () {
    $c = multiChoiceCampaign(min: 2, max: 4);
    $cat = $c->categories->first();
    $ids = $cat->candidates->pluck('id')->take(3)->all();

    verifyMc($c);
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => $ids]],
    ])->assertRedirect(route('voting.thanks', $c->public_token));

    expect(\App\Modules\Voting\Models\Vote::count())->toBe(1);
    expect(\App\Modules\Voting\Models\VoteItem::count())->toBe(3);
});

it('rejects fewer picks than selection_min', function () {
    $c = multiChoiceCampaign(min: 2, max: 4);
    $cat = $c->categories->first();
    $ids = $cat->candidates->pluck('id')->take(1)->all();

    verifyMc($c);
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => $ids]],
    ])->assertSessionHasErrors();

    expect(\App\Modules\Voting\Models\Vote::count())->toBe(0);
});

it('rejects more picks than selection_max', function () {
    $c = multiChoiceCampaign(min: 2, max: 4);
    $cat = $c->categories->first();
    $ids = $cat->candidates->pluck('id')->take(5)->all();

    verifyMc($c);
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => $ids]],
    ])->assertSessionHasErrors();

    expect(\App\Modules\Voting\Models\Vote::count())->toBe(0);
});

it('skips inactive categories in validation', function () {
    $c = multiChoiceCampaign(min: 1, max: 1);
    $c->categories()->create([
        'title_ar' => 'off', 'title_en' => 'off',
        'position_slot' => 'any', 'required_picks' => 1,
        'selection_min' => 1, 'selection_max' => 1, 'is_active' => false,
    ]);
    $cat = $c->categories->first();
    $ids = $cat->candidates->pluck('id')->take(1)->all();

    verifyMc($c);
    $this->post(route('voting.submit', $c->public_token), [
        'selections' => [['category_id' => $cat->id, 'candidate_ids' => $ids]],
    ])->assertRedirect(route('voting.thanks', $c->public_token));
});
