<?php

declare(strict_types=1);

use App\Modules\Campaigns\Actions\UpdateCampaignAction;
use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Voting\Actions\CheckVoterSessionAction;
use App\Modules\Voting\Actions\CreateVoterSessionAction;
use App\Modules\Voting\Enums\VerificationMethod;
use App\Support\Csv;

/*
 * Pinned regression suite for the 8 audit findings:
 *
 *   1 — API DELETE /campaigns/{id} must use the `delete` policy AND go
 *       through DeleteCampaignAction so the vote-count guard fires.
 *   2 — API PUT /campaigns/{id} must refuse to mutate a non-Draft campaign.
 *   4 — Voter session must expire after the configured TTL.
 *   5 — Club logo upload must reject SVG.
 *   6 — CSV exports must neutralise formula injection.
 *   7 — Player export must mask national_id and mobile_number by default.
 */

beforeEach(function () {
    seedRolesAndPermissions();
});

// -----------------------------------------------------------------------------
// Finding #1 — API destroy with votes
// -----------------------------------------------------------------------------

it('API: refuses to delete a campaign that already has votes (no force)', function () {
    $admin    = makeSuperAdmin();
    $campaign = Campaign::create([
        'title_ar' => 'حملة', 'title_en' => 'Campaign',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Active->value,
        'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
    ]);
    \App\Modules\Voting\Models\Vote::create([
        'campaign_id'      => $campaign->id,
        'voter_identifier' => str_repeat('a', 64),
        'submitted_at'     => now(),
    ]);

    test()->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/campaigns/{$campaign->id}")
        ->assertStatus(422)
        ->assertJsonStructure(['message']);

    expect(Campaign::find($campaign->id))->not->toBeNull();
});

it('API: allows force-delete and cascades children', function () {
    $admin    = makeSuperAdmin();
    $campaign = Campaign::create([
        'title_ar' => 'حملة', 'title_en' => 'Campaign',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Active->value,
        'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
    ]);
    \App\Modules\Voting\Models\Vote::create([
        'campaign_id'      => $campaign->id,
        'voter_identifier' => str_repeat('b', 64),
        'submitted_at'     => now(),
    ]);

    test()->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/campaigns/{$campaign->id}?force=1")
        ->assertNoContent();

    expect(Campaign::find($campaign->id))->toBeNull();
});

// -----------------------------------------------------------------------------
// Finding #2 — API update on non-Draft campaign
// -----------------------------------------------------------------------------

it('API: refuses to update a Published campaign', function () {
    $admin    = makeSuperAdmin();
    $campaign = Campaign::create([
        'title_ar' => 'قبل', 'title_en' => 'Before',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Published->value,
        'start_at' => now()->addDay(), 'end_at' => now()->addDays(2),
    ]);

    test()->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/campaigns/{$campaign->id}", ['title_en' => 'After'])
        ->assertStatus(422);

    expect($campaign->fresh()->title_en)->toBe('Before');
});

it('API: refuses to update an Active campaign', function () {
    $admin    = makeSuperAdmin();
    $campaign = Campaign::create([
        'title_ar' => 'نشطة', 'title_en' => 'Active',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Active->value,
        'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
    ]);

    test()->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/campaigns/{$campaign->id}", ['title_en' => 'Hijacked'])
        ->assertStatus(422);

    expect($campaign->fresh()->title_en)->toBe('Active');
});

it('API: still allows update on Draft', function () {
    $admin    = makeSuperAdmin();
    $campaign = Campaign::create([
        'title_ar' => 'مسودة', 'title_en' => 'Draft',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Draft->value,
        'start_at' => now()->addDay(), 'end_at' => now()->addDays(2),
    ]);

    test()->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/campaigns/{$campaign->id}", ['title_en' => 'Edited Draft'])
        ->assertOk();

    expect($campaign->fresh()->title_en)->toBe('Edited Draft');
});

it('UpdateCampaignAction.isEditable matches expected status set', function () {
    $cases = [
        [CampaignStatus::Draft,           true],
        [CampaignStatus::Rejected,        true],
        [CampaignStatus::PendingApproval, false],
        [CampaignStatus::Published,       false],
        [CampaignStatus::Active,          false],
        [CampaignStatus::Closed,          false],
        [CampaignStatus::Archived,        false],
    ];

    foreach ($cases as [$status, $expected]) {
        $c = Campaign::create([
            'title_ar' => 'x', 'title_en' => 'x',
            'type'     => CampaignType::IndividualAward->value,
            'status'   => $status->value,
            'start_at' => now(), 'end_at' => now()->addDay(),
        ]);
        expect(UpdateCampaignAction::isEditable($c))->toBe($expected, $status->value);
    }
});

// -----------------------------------------------------------------------------
// Finding #4 — Voter session TTL
// -----------------------------------------------------------------------------

it('voter session is returned when fresh and dropped after TTL', function () {
    $campaign = Campaign::create([
        'title_ar' => 'C', 'title_en' => 'C',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Active->value,
        'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
    ]);
    $player = makePlayer(['national_id' => '1012345678']);

    app(CreateVoterSessionAction::class)->execute(
        $campaign, $player, VerificationMethod::NationalId, '1012345678'
    );

    // Fresh: returns the entry.
    expect(app(CheckVoterSessionAction::class)->execute($campaign))
        ->not->toBeNull();

    // Travel beyond TTL (default 15 min) → entry expires.
    $this->travel(20)->minutes();
    expect(app(CheckVoterSessionAction::class)->execute($campaign))
        ->toBeNull();
});

// -----------------------------------------------------------------------------
// Finding #5 — Club logo MIME allowlist
// -----------------------------------------------------------------------------

it('club logo rules no longer accept svg', function () {
    $store  = (new \App\Modules\Clubs\Http\Requests\StoreClubRequest)->rules();
    $update = (new \App\Modules\Clubs\Http\Requests\UpdateClubRequest)->rules();
    expect($store['logo'])->not->toContain('mimes:png,jpg,jpeg,svg,webp');
    expect($update['logo'])->not->toContain('mimes:png,jpg,jpeg,svg,webp');
    expect(implode(',', $store['logo']))->toContain('png,jpg,jpeg,webp');
});

// -----------------------------------------------------------------------------
// Finding #6 — CSV formula injection
// -----------------------------------------------------------------------------

it('Csv::safe defuses formula-injection prefixes', function () {
    expect(Csv::safe('=cmd|/c calc'))->toStartWith("'=");
    expect(Csv::safe('+SUM(1,2)'))->toStartWith("'+");
    expect(Csv::safe('-1'))->toStartWith("'-");
    expect(Csv::safe('@evil'))->toStartWith("'@");
    expect(Csv::safe("\tinjected"))->toStartWith("'\t");
    // Safe inputs are passed through.
    expect(Csv::safe('Ahmed Ali'))->toBe('Ahmed Ali');
    expect(Csv::safe(null))->toBe('');
    expect(Csv::safe(true))->toBe('1');
});

// -----------------------------------------------------------------------------
// Finding #7 — PII masking
// -----------------------------------------------------------------------------

it('Csv::maskNationalId keeps first 4 + last 2 only', function () {
    expect(Csv::maskNationalId('1012345678'))->toBe('1012****78');
    expect(Csv::maskNationalId(''))->toBe('');
    expect(Csv::maskNationalId(null))->toBe('');
});

it('Csv::maskMobile keeps first 2 + last 3 only', function () {
    expect(Csv::maskMobile('0501234567'))->toBe('05*****567');
    expect(Csv::maskMobile(''))->toBe('');
});
