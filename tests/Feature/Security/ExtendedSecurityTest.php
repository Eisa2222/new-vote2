<?php

declare(strict_types=1);

use App\Modules\Campaigns\Models\Campaign;

it('public results does not leak hidden result via direct token', function () {
    // Behaviour change: /results/{token} now renders a "coming soon"
    // view for unannounced campaigns (see PublicResultsController::show)
    // instead of 404. The security invariant is unchanged — no ranking
    // / winner data leaks — so this test now asserts the response
    // cannot surface any winner names or vote counts from the hidden
    // result. Unknown tokens still 404 via firstOrFail().
    seedRolesAndPermissions();
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now()->subDay(), 'end_at' => now()->subHour(),
        'status' => 'closed', 'results_visibility' => 'hidden',
    ]);
    // create a calculated result in hidden state
    \App\Modules\Results\Models\CampaignResult::create([
        'campaign_id' => $c->id, 'status' => 'calculated',
    ]);
    $this->get("/results/{$c->public_token}")
        ->assertOk()
        ->assertDontSee('Winner', false)
        ->assertDontSee('votes_count', false);
});

it('submit endpoint enforces CSRF by web middleware', function () {
    seedRolesAndPermissions();
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now()->subHour(), 'end_at' => now()->addDay(), 'status' => 'active',
    ]);
    // Pest's test client disables CSRF by default — we assert the route is at least
    // on the `web` middleware group (that enables CSRF in production).
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('voting.submit');
    expect($route->gatherMiddleware())->toContain('web');
});

it('admin edit route requires authentication', function () {
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now(), 'end_at' => now()->addDay(), 'status' => 'draft',
    ]);
    $this->get("/admin/campaigns/{$c->id}/edit")
        ->assertRedirect('/login');
});

it('activate / archive / close admin routes require authentication', function () {
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now(), 'end_at' => now()->addDay(), 'status' => 'active',
    ]);
    $this->post("/admin/campaigns/{$c->id}/activate")->assertRedirect('/login');
    $this->post("/admin/campaigns/{$c->id}/archive")->assertRedirect('/login');
    $this->post("/admin/campaigns/{$c->id}/close")->assertRedirect('/login');
    $this->post("/admin/campaigns/{$c->id}/publish")->assertRedirect('/login');
});

it('live stats JSON endpoint is admin-only', function () {
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now(), 'end_at' => now()->addDay(), 'status' => 'active',
    ]);
    $r = $this->getJson("/admin/campaigns/{$c->id}/stats");
    expect($r->status())->toBeIn([302, 401, 419]);
});

it('tos candidates admin route requires auth', function () {
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'team_of_the_season',
        'start_at' => now(), 'end_at' => now()->addDay(), 'status' => 'draft',
    ]);
    $this->get("/admin/tos/{$c->id}/candidates")->assertRedirect('/login');
});
