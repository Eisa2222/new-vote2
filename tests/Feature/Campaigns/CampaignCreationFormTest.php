<?php

declare(strict_types=1);

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\PlayerPosition;

it('admin can view the campaign create form', function () {
    $this->actingAs(makeSuperAdmin())
        ->get('/admin/campaigns/create')
        ->assertOk()
        ->assertSee('campaign', false);
});

it('admin can store a campaign with one category', function () {
    $p1 = makePlayer(['position' => PlayerPosition::Attack, 'jersey_number' => 11]);
    $p2 = makePlayer(['position' => PlayerPosition::Attack, 'club_id' => makeClub()->id, 'jersey_number' => 7]);

    $this->actingAs(makeSuperAdmin())
        ->post('/admin/campaigns', [
            'title_ar' => 'أفضل', 'title_en' => 'Best',
            'type' => 'individual_award',
            'start_at' => now()->addHour()->toDateTimeString(),
            'end_at'   => now()->addDays(7)->toDateTimeString(),
            'categories' => [[
                'title_ar' => 'الأفضل', 'title_en' => 'Best',
                'position_slot' => 'any', 'required_picks' => 1,
                'player_ids' => [$p1->id, $p2->id],
            ]],
        ])
        ->assertRedirect();

    $c = Campaign::first();
    expect($c)->not->toBeNull();
    expect($c->status->value)->toBe('draft');
    expect($c->categories->count())->toBe(1);
    expect($c->categories->first()->candidates->count())->toBe(2);
});

it('accepts campaign without categories (falls back to default 3 awards)', function () {
    // After the club-scoped voting refactor, a campaign with no
    // voting_categories is valid: the ballot falls back to the
    // standard 3 awards (Best Saudi / Best Foreign / TOS). The
    // admin can still attach curated shortlists later.
    $this->actingAs(makeSuperAdmin())
        ->post('/admin/campaigns', [
            'title_ar' => 'x', 'title_en' => 'x',
            'type' => 'individual_award',
            'start_at' => now()->addHour()->toDateTimeString(),
            'end_at'   => now()->addDays(7)->toDateTimeString(),
        ])
        ->assertRedirect();

    expect(Campaign::count())->toBe(1);
});

it('rejects campaign with end before start', function () {
    $p = makePlayer();
    $this->actingAs(makeSuperAdmin())
        ->post('/admin/campaigns', [
            'title_ar' => 'x', 'title_en' => 'x',
            'type' => 'individual_award',
            'start_at' => now()->addDays(7)->toDateTimeString(),
            'end_at'   => now()->addHour()->toDateTimeString(),
            'categories' => [[
                'title_ar' => 'x', 'title_en' => 'x',
                'position_slot' => 'any', 'required_picks' => 1,
                'player_ids' => [$p->id],
            ]],
        ])
        ->assertSessionHasErrors(['end_at']);
});

it('enforces 3-3-4-1 for team_of_the_season on creation', function () {
    $p = makePlayer(['jersey_number' => 1]);

    $this->actingAs(makeSuperAdmin())
        ->post('/admin/campaigns', [
            'title_ar' => 'x', 'title_en' => 'x',
            'type' => 'team_of_the_season',
            'start_at' => now()->addHour()->toDateTimeString(),
            'end_at'   => now()->addDays(7)->toDateTimeString(),
            'categories' => [[
                'title_ar' => 'Att', 'title_en' => 'Att',
                'position_slot' => 'attack', 'required_picks' => 5, // wrong: should be 3
                'player_ids' => [$p->id],
            ]],
        ])
        ->assertSessionHasErrors();
});
