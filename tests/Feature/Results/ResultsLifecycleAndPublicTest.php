<?php

declare(strict_types=1);

use App\Modules\Campaigns\Enums\ResultsVisibility;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Results\Actions\AnnounceResultsAction;
use App\Modules\Results\Actions\ApproveResultsAction;
use App\Modules\Results\Actions\CalculateCampaignResultsAction;
use App\Modules\Results\Actions\HideResultsAction;
use App\Modules\Results\Domain\ResultStatusTransitionRule;
use App\Modules\Results\Domain\ResultTieBreakerRule;
use App\Modules\Results\Domain\ResultVisibilityRule;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Models\CampaignResult;
use App\Modules\Users\Actions\LogActivityAction;
use App\Modules\Voting\Models\Vote;

function buildResultScenario(int $votesFor1 = 5, int $votesFor2 = 2): Campaign
{
    seedRolesAndPermissions();
    $p1 = makePlayer(['position' => PlayerPosition::Attack, 'jersey_number' => 11, 'national_id' => '5000000001']);
    $p2 = makePlayer(['position' => PlayerPosition::Attack, 'club_id' => makeClub()->id, 'jersey_number' => 9, 'national_id' => '5000000002']);

    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now()->subDay(), 'end_at' => now()->addDay(), 'status' => 'active',
    ]);
    $cat = $c->categories()->create([
        'title_ar' => 'X', 'title_en' => 'X',
        'position_slot' => 'any', 'required_picks' => 1,
        'selection_min' => 1, 'selection_max' => 1, 'is_active' => true,
    ]);
    $cand1 = $cat->candidates()->create(['player_id' => $p1->id, 'display_order' => 0, 'is_active' => true]);
    $cand2 = $cat->candidates()->create(['player_id' => $p2->id, 'display_order' => 1, 'is_active' => true]);

    for ($i = 0; $i < $votesFor1; $i++) {
        $v = Vote::create(['campaign_id' => $c->id, 'voter_identifier' => "v1-{$i}", 'submitted_at' => now()]);
        $v->items()->create(['voting_category_id' => $cat->id, 'candidate_id' => $cand1->id]);
    }
    for ($i = 0; $i < $votesFor2; $i++) {
        $v = Vote::create(['campaign_id' => $c->id, 'voter_identifier' => "v2-{$i}", 'submitted_at' => now()]);
        $v->items()->create(['voting_category_id' => $cat->id, 'candidate_id' => $cand2->id]);
    }
    return $c;
}

it('calculate writes total_votes, percentages, and audit fields', function () {
    $c = buildResultScenario();
    $this->actingAs(makeSuperAdmin());
    $r = (new CalculateCampaignResultsAction())->execute($c);

    expect($r->total_votes)->toBe(7);
    expect($r->calculated_by)->not->toBeNull();
    expect($r->items->first()->vote_percentage)->toBe((float) round(5/7*100, 2));
});

it('tie-breaker orders ranks deterministically AND flags tied-at-cutoff items for the committee', function () {
    // Both candidates get 3 votes; required_picks=1 → tie straddles the
    // cutoff → both items get needs_committee_decision=true and is_winner=null.
    // Rank order is still deterministic (lower display_order first) so the
    // UI can list them predictably before the committee picks.
    $c = buildResultScenario(votesFor1: 3, votesFor2: 3);
    $this->actingAs(makeSuperAdmin());
    $r = (new CalculateCampaignResultsAction())->execute($c);

    $ranked = $r->items->sortBy('rank')->values();
    expect($ranked[0]->candidate->display_order)->toBe(0);
    expect($ranked[0]->needs_committee_decision)->toBeTrue();
    expect($ranked[0]->is_winner)->toBeNull();
    expect($ranked[1]->needs_committee_decision)->toBeTrue();
    expect($ranked[1]->is_winner)->toBeNull();
});

it('tie-breaker service sorts by votes then display_order then id', function () {
    $rows = collect([
        (object) ['candidate_id' => 10, 'votes_count' => 5, 'display_order' => 2],
        (object) ['candidate_id' => 20, 'votes_count' => 5, 'display_order' => 1],
        (object) ['candidate_id' => 30, 'votes_count' => 7, 'display_order' => 3],
    ]);
    $sorted = (new ResultTieBreakerRule())->sort($rows);
    expect($sorted[0]->candidate_id)->toBe(30); // highest votes
    expect($sorted[1]->candidate_id)->toBe(20); // tied 5, lower display_order
    expect($sorted[2]->candidate_id)->toBe(10);
});

it('transition rule allows calculated → approved', function () {
    $r = new ResultStatusTransitionRule();
    expect($r->can(ResultStatus::Calculated, ResultStatus::Approved))->toBeTrue();
});

it('transition rule blocks pending → announced', function () {
    $r = new ResultStatusTransitionRule();
    expect($r->can(ResultStatus::PendingCalculation, ResultStatus::Announced))->toBeFalse();
});

it('transition rule allows announced → hidden (emergency takedown)', function () {
    $r = new ResultStatusTransitionRule();
    expect($r->can(ResultStatus::Announced, ResultStatus::Hidden))->toBeTrue();
});

it('visibility rule hides unannounced results from public', function () {
    $c = buildResultScenario();
    $this->actingAs(makeSuperAdmin());
    $r = (new CalculateCampaignResultsAction())->execute($c);
    $vis = new ResultVisibilityRule();

    expect($vis->isPublic($c->fresh(), $r))->toBeFalse();
    (new ApproveResultsAction(new LogActivityAction()))->execute($r);
    expect($vis->isPublic($c->fresh(), $r->fresh()))->toBeFalse();
    (new AnnounceResultsAction(new LogActivityAction()))->execute($r->fresh());
    expect($vis->isPublic($c->fresh(), $r->fresh()))->toBeTrue();
});

it('announce sets announced_by and flips is_announced on items', function () {
    $c = buildResultScenario();
    $this->actingAs(makeSuperAdmin());
    $r = (new CalculateCampaignResultsAction())->execute($c);
    (new ApproveResultsAction(new LogActivityAction()))->execute($r);
    (new AnnounceResultsAction(new LogActivityAction()))->execute($r->fresh());

    $fresh = $r->fresh('items');
    expect($fresh->announced_by)->not->toBeNull();
    expect($fresh->items->every(fn ($i) => $i->is_announced === true))->toBeTrue();
});

it('hide after announce still works (emergency takedown)', function () {
    $c = buildResultScenario();
    $this->actingAs(makeSuperAdmin());
    $r = (new CalculateCampaignResultsAction())->execute($c);
    (new ApproveResultsAction(new LogActivityAction()))->execute($r);
    (new AnnounceResultsAction(new LogActivityAction()))->execute($r->fresh());
    (new HideResultsAction(new LogActivityAction()))->execute($r->fresh());

    expect($r->fresh()->status->value)->toBe('hidden');
});

it('public results page shows a "coming soon" view for unannounced results', function () {
    // Behaviour changed by user request: instead of a bare 404 (which
    // felt broken when a voter tapped the "Results" CTA on the stats
    // page before the committee announced), we now render a friendly
    // coming-soon view with a live countdown. The page deliberately
    // does NOT leak any ranking data.
    $c = buildResultScenario();
    $this->actingAs(makeSuperAdmin());
    (new CalculateCampaignResultsAction())->execute($c);

    $this->get("/results/{$c->public_token}")
        ->assertOk()
        // Generic coming-soon copy — no winner names or vote counts.
        ->assertDontSee('Winner', false);
});

it('public results page shows the result once announced', function () {
    $c = buildResultScenario();
    $this->actingAs(makeSuperAdmin());
    $r = (new CalculateCampaignResultsAction())->execute($c);
    (new ApproveResultsAction(new LogActivityAction()))->execute($r);
    (new AnnounceResultsAction(new LogActivityAction()))->execute($r->fresh());

    $this->get("/results/{$c->public_token}")
        ->assertOk()
        // View now announces winners by name + category label
        // instead of the generic "Winner" eyebrow + ranking table.
        // The scenario's category title is 'X'; that's what lands
        // above the winner card.
        ->assertSee('Official announcement', false);
});

it('public results returns 404 for unknown token — no info leakage', function () {
    $this->get('/results/does-not-exist')->assertNotFound();
});
