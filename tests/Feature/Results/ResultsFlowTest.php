<?php

declare(strict_types=1);

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Enums\ResultsVisibility;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Results\Actions\AnnounceResultsAction;
use App\Modules\Results\Actions\ApproveResultsAction;
use App\Modules\Results\Actions\CalculateCampaignResultsAction;
use App\Modules\Results\Actions\HideResultsAction;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Users\Actions\LogActivityAction;
use App\Modules\Voting\Models\Vote;

function makeCampaignWithVotes(): Campaign
{
    seedRolesAndPermissions();
    $p1 = makePlayer(['position' => PlayerPosition::Attack, 'jersey_number' => 9]);
    $p2 = makePlayer(['position' => PlayerPosition::Attack, 'club_id' => makeClub()->id, 'jersey_number' => 10]);

    $c = Campaign::create([
        'title_ar' => 'أفضل', 'title_en' => 'Best',
        'type' => CampaignType::IndividualAward->value,
        'start_at' => now()->subDay(),
        'end_at' => now()->addDay(),
        'status' => CampaignStatus::Active->value,
    ]);
    $cat = $c->categories()->create([
        'title_ar' => 'الأفضل', 'title_en' => 'Best',
        'position_slot' => 'any', 'required_picks' => 1, 'display_order' => 0,
    ]);
    $cand1 = $cat->candidates()->create(['player_id' => $p1->id, 'display_order' => 0]);
    $cand2 = $cat->candidates()->create(['player_id' => $p2->id, 'display_order' => 1]);

    foreach (range(1, 3) as $i) {
        $v = Vote::create(['campaign_id' => $c->id, 'voter_identifier' => "v{$i}", 'submitted_at' => now()]);
        $v->items()->create(['voting_category_id' => $cat->id, 'candidate_id' => $cand1->id]);
    }
    $v = Vote::create(['campaign_id' => $c->id, 'voter_identifier' => 'v4', 'submitted_at' => now()]);
    $v->items()->create(['voting_category_id' => $cat->id, 'candidate_id' => $cand2->id]);

    return $c;
}

it('calculates results with correct counts and winner', function () {
    $c = makeCampaignWithVotes();
    $result = (new CalculateCampaignResultsAction())->execute($c->load('categories'));

    expect($result->status)->toBe(ResultStatus::Calculated);
    $items = $result->items->sortBy('rank')->values();
    expect($items[0]->votes_count)->toBe(3);
    expect($items[0]->is_winner)->toBeTrue();
    expect($items[1]->votes_count)->toBe(1);
    expect($items[1]->is_winner)->toBeFalse();
});

it('visibility stays hidden until approved', function () {
    $c = makeCampaignWithVotes();
    (new CalculateCampaignResultsAction())->execute($c->load('categories'));
    expect($c->fresh()->results_visibility)->toBe(ResultsVisibility::Hidden);
});

it('approve then announce updates visibility correctly', function () {
    $c = makeCampaignWithVotes();
    $log = new LogActivityAction();
    $this->actingAs(makeSuperAdmin());

    $result = (new CalculateCampaignResultsAction())->execute($c->load('categories'));
    (new ApproveResultsAction($log))->execute($result);
    expect($c->fresh()->results_visibility)->toBe(ResultsVisibility::Approved);

    (new AnnounceResultsAction($log))->execute($result->fresh());
    expect($c->fresh()->results_visibility)->toBe(ResultsVisibility::Announced);
});

it('cannot announce before approval', function () {
    $c = makeCampaignWithVotes();
    $result = (new CalculateCampaignResultsAction())->execute($c->load('categories'));
    (new AnnounceResultsAction(new LogActivityAction()))->execute($result);
})->throws(DomainException::class);

it('cannot approve an uncalculated result', function () {
    $c = makeCampaignWithVotes();
    $result = \App\Modules\Results\Models\CampaignResult::create([
        'campaign_id' => $c->id, 'status' => 'pending_calculation',
    ]);
    (new ApproveResultsAction(new LogActivityAction()))->execute($result);
})->throws(DomainException::class);

it('hide resets visibility to hidden', function () {
    $c = makeCampaignWithVotes();
    $log = new LogActivityAction();
    $this->actingAs(makeSuperAdmin());
    $result = (new CalculateCampaignResultsAction())->execute($c->load('categories'));
    (new ApproveResultsAction($log))->execute($result);
    (new HideResultsAction($log))->execute($result->fresh());
    expect($c->fresh()->results_visibility)->toBe(ResultsVisibility::Hidden);
});
