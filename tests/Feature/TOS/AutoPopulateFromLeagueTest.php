<?php

declare(strict_types=1);

use App\Modules\Campaigns\Actions\AutoPopulateTeamOfSeasonFromLeagueAction;
use App\Modules\Campaigns\Actions\CreateTeamOfSeasonCampaignAction;
use App\Modules\Leagues\Models\League;
use App\Modules\Players\Enums\PlayerPosition;

beforeEach(function () {
    seedRolesAndPermissions();
});

it('attaches every active league player to their matching line', function () {
    $sport  = makeFootball();
    $league = League::create([
        'sport_id' => $sport->id, 'slug' => 'test-league',
        'name_ar' => 'دوري تجربة', 'name_en' => 'Test League', 'status' => 'active',
    ]);
    $club = makeClub(['name_en' => 'TestFC', 'name_ar' => 'نادي التجربة']);
    $league->clubs()->attach($club->id);

    // 1 GK, 4 defenders, 3 midfielders, 3 attackers
    makePlayer(['club_id' => $club->id, 'name_en' => 'GK',   'position' => PlayerPosition::Goalkeeper]);
    foreach (['D1','D2','D3','D4']     as $n) makePlayer(['club_id' => $club->id, 'name_en' => $n, 'position' => PlayerPosition::Defense]);
    foreach (['M1','M2','M3']          as $n) makePlayer(['club_id' => $club->id, 'name_en' => $n, 'position' => PlayerPosition::Midfield]);
    foreach (['A1','A2','A3']          as $n) makePlayer(['club_id' => $club->id, 'name_en' => $n, 'position' => PlayerPosition::Attack]);

    $campaign = app(CreateTeamOfSeasonCampaignAction::class)->execute([
        'title_ar' => 'تجربة', 'title_en' => 'Trial',
        'start_at' => now(), 'end_at' => now()->addDays(7),
        'attack' => 3, 'midfield' => 3, 'defense' => 4,
    ]);

    $summary = app(AutoPopulateTeamOfSeasonFromLeagueAction::class)->execute($campaign, $league->id);

    expect($summary['attached'])->toBe(11);
    expect($summary['no_clubs'])->toBeFalse();
    expect($summary['no_active_players'])->toBeFalse();

    $campaign->load('categories.candidates');
    $byLine = $campaign->categories->keyBy('position_slot');
    expect($byLine['goalkeeper']->candidates)->toHaveCount(1);
    expect($byLine['defense']->candidates)->toHaveCount(4);
    expect($byLine['midfield']->candidates)->toHaveCount(3);
    expect($byLine['attack']->candidates)->toHaveCount(3);
});

it('flags no_clubs when the league has no clubs linked', function () {
    $sport  = makeFootball();
    $league = League::create([
        'sport_id' => $sport->id, 'slug' => 'empty',
        'name_ar' => 'فارغ', 'name_en' => 'Empty', 'status' => 'active',
    ]);

    $campaign = app(CreateTeamOfSeasonCampaignAction::class)->execute([
        'title_ar' => 'x', 'title_en' => 'x',
        'start_at' => now(), 'end_at' => now()->addDays(1),
        'attack' => 3, 'midfield' => 3, 'defense' => 4,
    ]);

    $summary = app(AutoPopulateTeamOfSeasonFromLeagueAction::class)->execute($campaign, $league->id);

    expect($summary['attached'])->toBe(0);
    expect($summary['no_clubs'])->toBeTrue();
});

it('flags no_active_players when the league has clubs but no positioned active players', function () {
    $sport  = makeFootball();
    $league = League::create([
        'sport_id' => $sport->id, 'slug' => 'dry',
        'name_ar' => 'جاف', 'name_en' => 'Dry', 'status' => 'active',
    ]);
    $club = makeClub();
    $league->clubs()->attach($club->id);

    $campaign = app(CreateTeamOfSeasonCampaignAction::class)->execute([
        'title_ar' => 'x', 'title_en' => 'x',
        'start_at' => now(), 'end_at' => now()->addDays(1),
        'attack' => 3, 'midfield' => 3, 'defense' => 4,
    ]);

    $summary = app(AutoPopulateTeamOfSeasonFromLeagueAction::class)->execute($campaign, $league->id);

    expect($summary['attached'])->toBe(0);
    expect($summary['no_clubs'])->toBeFalse();
    expect($summary['no_active_players'])->toBeTrue();
});
