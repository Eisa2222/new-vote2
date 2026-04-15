<?php

declare(strict_types=1);

it('returns live stats JSON for a campaign', function () {
    $c = \App\Modules\Campaigns\Models\Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now()->subHour(), 'end_at' => now()->addDay(),
        'status' => 'active', 'max_voters' => 100,
    ]);

    $this->actingAs(makeSuperAdmin())
        ->getJson("/admin/campaigns/{$c->id}/stats")
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['campaign_id', 'votes_count', 'max_voters', 'percentage', 'status', 'near_limit'],
        ]);
});

it('stats endpoint requires auth', function () {
    $c = \App\Modules\Campaigns\Models\Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x', 'type' => 'individual_award',
        'start_at' => now()->subHour(), 'end_at' => now()->addDay(),
        'status' => 'active',
    ]);

    // JSON requests don't get redirected; they return 401 from the auth middleware
    $r = $this->getJson("/admin/campaigns/{$c->id}/stats");
    expect($r->status())->toBeIn([302, 401, 419]);
});
