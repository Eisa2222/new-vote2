<?php

declare(strict_types=1);

use App\Modules\Campaigns\Models\Campaign;

/**
 * Cheap smoke tests for /admin/campaigns/{id} that pin the recent
 * regressions from commits c4c39bb..09ca050.
 *
 *   - 09ca050: ResultsVisibility enum to-string crash on the show page
 *   - dd08f8e: live countdown stats card needs a campaign with end_at
 *
 * These take seconds to run and prevent the whole admin show page
 * from breaking on any future enum / view tweak.
 */

beforeEach(function () {
    seedRolesAndPermissions();
});

it('renders the admin campaign show page for every results_visibility value', function ($visibility) {
    $admin = makeSuperAdmin();
    $campaign = Campaign::create([
        'title_ar' => 'حملة', 'title_en' => 'Campaign',
        'type'     => 'individual_award',
        'start_at' => now()->subDay(),
        'end_at'   => now()->addDay(),
        'status'   => 'active',
        'results_visibility' => $visibility,
    ]);

    $this->actingAs($admin)
        ->get("/admin/campaigns/{$campaign->id}")
        ->assertOk();
})->with([
    ['hidden'],
    ['approved'],
    ['announced'],
]);
