<?php

declare(strict_types=1);

use App\Modules\Campaigns\Actions\AttachTeamOfSeasonCandidatesAction;
use App\Modules\Campaigns\Actions\CreateTeamOfSeasonCampaignAction;
use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Users\Actions\LogActivityAction;
use App\Modules\Voting\Actions\ValidateTeamOfSeasonSelectionAction;
use App\Modules\Voting\Exceptions\VotingException;

function makeTOSCampaignWithPlayers(?array $formation = null): Campaign
{
    seedRolesAndPermissions();
    $formation ??= ['attack' => 3, 'midfield' => 3, 'defense' => 4];
    $action = new CreateTeamOfSeasonCampaignAction(new LogActivityAction());
    $c = $action->execute([
        'title_ar' => 'تشكيلة الموسم', 'title_en' => 'TOTS',
        'start_at' => now()->subHour(), 'end_at' => now()->addDay(),
        ...$formation,
    ]);
    $c->update(['status' => 'active']);

    $attach = new AttachTeamOfSeasonCandidatesAction();
    foreach ($c->categories as $cat) {
        $count = match ($cat->position_slot) {
            'attack', 'midfield' => 4,
            'defense' => 5,
            'goalkeeper' => 2,
        };
        $pids = [];
        for ($i = 0; $i < $count; $i++) {
            $pids[] = makePlayer([
                'position' => PlayerPosition::from($cat->position_slot),
                'club_id'  => makeClub()->id,
                'jersey_number' => random_int(1, 999),
                'national_id' => 'TOS'.$cat->id.$i.random_int(1000,9999),
            ])->id;
        }
        $attach->execute($c, $cat, $pids);
    }
    return $c->load('categories.candidates.player');
}

it('formation rule accepts default 3-3-4-1', function () {
    TeamOfSeasonFormation::validate(TeamOfSeasonFormation::default());
    expect(TeamOfSeasonFormation::total())->toBe(11);
    expect(TeamOfSeasonFormation::default())->toBe([
        'attack' => 3, 'midfield' => 3, 'defense' => 4, 'goalkeeper' => 1,
    ]);
});

it('formation rule accepts 3-4-3 and 5-3-2', function () {
    TeamOfSeasonFormation::validate(['attack' => 3, 'midfield' => 4, 'defense' => 3, 'goalkeeper' => 1]);
    TeamOfSeasonFormation::validate(['attack' => 2, 'midfield' => 3, 'defense' => 5, 'goalkeeper' => 1]);
});

it('formation rule rejects goalkeeper != 1', function () {
    TeamOfSeasonFormation::validate([
        'attack' => 3, 'midfield' => 3, 'defense' => 4, 'goalkeeper' => 2,
    ]);
})->throws(DomainException::class);

it('formation rule rejects outfield sum != 10', function () {
    TeamOfSeasonFormation::validate([
        'attack' => 3, 'midfield' => 3, 'defense' => 3, 'goalkeeper' => 1,
    ]);
})->throws(DomainException::class);

it('creates a TOTS campaign and seeds 4 categories', function () {
    $action = new CreateTeamOfSeasonCampaignAction(new LogActivityAction());
    $c = $action->execute([
        'title_ar' => 'تشكيلة', 'title_en' => 'TOTS',
        'start_at' => now(), 'end_at' => now()->addDay(),
        'attack' => 3, 'midfield' => 3, 'defense' => 4,
    ]);
    expect($c->type)->toBe(CampaignType::TeamOfTheSeason);
    expect($c->categories()->count())->toBe(4);
    expect($c->categories->pluck('position_slot')->sort()->values()->all())
        ->toBe(['attack', 'defense', 'goalkeeper', 'midfield']);
    expect($c->categories->firstWhere('position_slot', 'attack')->required_picks)->toBe(3);
    expect($c->categories->firstWhere('position_slot', 'defense')->required_picks)->toBe(4);
    expect($c->categories->firstWhere('position_slot', 'goalkeeper')->required_picks)->toBe(1);
});

it('rejects attaching player with wrong position', function () {
    $c = makeTOSCampaignWithPlayers();
    $attackCat = $c->categories->firstWhere('position_slot', 'attack');
    $gk = makePlayer(['position' => PlayerPosition::Goalkeeper, 'club_id' => makeClub()->id]);

    (new AttachTeamOfSeasonCandidatesAction())->execute($c, $attackCat, [$gk->id]);
})->throws(DomainException::class);

it('validates exact distribution 3-3-4-1 on submit payload', function () {
    $c = makeTOSCampaignWithPlayers();
    $cats = $c->categories->keyBy('position_slot');
    $payload = [
        'attack'     => $cats['attack']->candidates->take(3)->pluck('id')->all(),
        'midfield'   => $cats['midfield']->candidates->take(3)->pluck('id')->all(),
        'defense'    => $cats['defense']->candidates->take(4)->pluck('id')->all(),
        'goalkeeper' => $cats['goalkeeper']->candidates->take(1)->pluck('id')->all(),
    ];
    $selections = (new ValidateTeamOfSeasonSelectionAction())->execute($c, $payload);
    expect($selections)->toHaveCount(4);
});

it('rejects payload with wrong attack count', function () {
    $c = makeTOSCampaignWithPlayers();
    $cats = $c->categories->keyBy('position_slot');
    (new ValidateTeamOfSeasonSelectionAction())->execute($c, [
        'attack'     => $cats['attack']->candidates->take(2)->pluck('id')->all(), // only 2
        'midfield'   => $cats['midfield']->candidates->take(3)->pluck('id')->all(),
        'defense'    => $cats['defense']->candidates->take(4)->pluck('id')->all(),
        'goalkeeper' => $cats['goalkeeper']->candidates->take(1)->pluck('id')->all(),
    ]);
})->throws(VotingException::class);

it('rejects payload with wrong defense count', function () {
    $c = makeTOSCampaignWithPlayers();
    $cats = $c->categories->keyBy('position_slot');
    (new ValidateTeamOfSeasonSelectionAction())->execute($c, [
        'attack'     => $cats['attack']->candidates->take(3)->pluck('id')->all(),
        'midfield'   => $cats['midfield']->candidates->take(3)->pluck('id')->all(),
        'defense'    => $cats['defense']->candidates->take(5)->pluck('id')->all(), // 5
        'goalkeeper' => $cats['goalkeeper']->candidates->take(1)->pluck('id')->all(),
    ]);
})->throws(VotingException::class);

it('rejects unexpected keys in payload', function () {
    $c = makeTOSCampaignWithPlayers();
    $cats = $c->categories->keyBy('position_slot');
    (new ValidateTeamOfSeasonSelectionAction())->execute($c, [
        'attack'     => $cats['attack']->candidates->take(3)->pluck('id')->all(),
        'midfield'   => $cats['midfield']->candidates->take(3)->pluck('id')->all(),
        'defense'    => $cats['defense']->candidates->take(4)->pluck('id')->all(),
        'goalkeeper' => $cats['goalkeeper']->candidates->take(1)->pluck('id')->all(),
        'coach'      => [999],
    ]);
})->throws(VotingException::class);

it('rejects candidate from wrong line', function () {
    $c = makeTOSCampaignWithPlayers();
    $cats = $c->categories->keyBy('position_slot');
    $attackId = $cats['attack']->candidates->first()->id;
    (new ValidateTeamOfSeasonSelectionAction())->execute($c, [
        'attack'     => $cats['attack']->candidates->take(3)->pluck('id')->all(),
        'midfield'   => [$attackId, ...$cats['midfield']->candidates->take(2)->pluck('id')->all()], // attack cand in midfield
        'defense'    => $cats['defense']->candidates->take(4)->pluck('id')->all(),
        'goalkeeper' => $cats['goalkeeper']->candidates->take(1)->pluck('id')->all(),
    ]);
})->throws(VotingException::class);

it('admin can create a TOTS campaign via controller', function () {
    $this->actingAs(makeSuperAdmin())
        ->post('/admin/tos', [
            'title_ar' => 'تشكيلة الموسم', 'title_en' => 'TOTS 2026',
            'start_at' => now()->addHour()->toDateTimeString(),
            'end_at'   => now()->addDays(7)->toDateTimeString(),
            'attack' => 3, 'midfield' => 3, 'defense' => 4,
        ])
        ->assertRedirect();

    $c = Campaign::first();
    expect($c->type)->toBe(CampaignType::TeamOfTheSeason);
    expect($c->categories()->count())->toBe(4);
});

it('end-to-end: verified player submits a valid TOTS vote', function () {
    $c = makeTOSCampaignWithPlayers();
    $voter = makePlayer([
        'position' => PlayerPosition::Attack,
        'club_id'  => makeClub()->id,
        'national_id' => '4000000001',
    ]);

    $this->post(route('voting.verify', $c->public_token), ['national_id' => '4000000001'])
        ->assertRedirect(route('voting.form', $c->public_token));

    $cats = $c->categories->keyBy('position_slot');
    $this->post(route('voting.submit', $c->public_token), [
        'attack'     => $cats['attack']->candidates->take(3)->pluck('id')->all(),
        'midfield'   => $cats['midfield']->candidates->take(3)->pluck('id')->all(),
        'defense'    => $cats['defense']->candidates->take(4)->pluck('id')->all(),
        'goalkeeper' => $cats['goalkeeper']->candidates->take(1)->pluck('id')->all(),
    ])->assertRedirect(route('voting.thanks', $c->public_token));

    expect(\App\Modules\Voting\Models\Vote::count())->toBe(1);
    expect(\App\Modules\Voting\Models\VoteItem::count())->toBe(11);
});

it('FormRequest rejects submit with wrong attack size', function () {
    $c = makeTOSCampaignWithPlayers();
    $voter = makePlayer(['national_id' => '4000000002', 'club_id' => makeClub()->id]);
    $this->post(route('voting.verify', $c->public_token), ['national_id' => '4000000002']);

    $cats = $c->categories->keyBy('position_slot');
    $this->post(route('voting.submit', $c->public_token), [
        'attack'     => $cats['attack']->candidates->take(2)->pluck('id')->all(),
        'midfield'   => $cats['midfield']->candidates->take(3)->pluck('id')->all(),
        'defense'    => $cats['defense']->candidates->take(4)->pluck('id')->all(),
        'goalkeeper' => $cats['goalkeeper']->candidates->take(1)->pluck('id')->all(),
    ])->assertSessionHasErrors(['attack']);
});
