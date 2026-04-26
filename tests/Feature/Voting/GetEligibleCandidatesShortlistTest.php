<?php

declare(strict_types=1);

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\NationalityType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Voting\Actions\Club\GetEligibleCandidatesAction;
use App\Modules\Voting\Enums\AwardType;

/**
 * Regression test: the shortlist path of GetEligibleCandidatesAction
 * must return an Eloquent\Collection so the downstream `->load('club')`
 * call works. A previous refactor used a plain Support\Collection
 * (built via collect()->push()) which crashed at runtime with:
 *
 *   Return value must be of type ?Eloquent\Collection,
 *   Support\Collection returned
 *
 * The bug only surfaced when an admin attached a category with an
 * award_type to a campaign — i.e. it was hidden by the default
 * "all-by-nationality" path that most test fixtures use.
 */

beforeEach(function () {
    seedRolesAndPermissions();
});

it('returns an Eloquent collection from the shortlist path so load() works', function () {
    $voter   = makePlayer([
        'position'    => PlayerPosition::Midfield,
        'nationality' => NationalityType::Saudi,
        'jersey_number' => 1,
    ]);
    $nominee = makePlayer([
        'position'    => PlayerPosition::Attack,
        'nationality' => NationalityType::Saudi,
        'jersey_number' => 9,
    ]);

    $campaign = Campaign::create([
        'title_ar' => 'حملة', 'title_en' => 'Campaign',
        'type'     => 'individual_award',
        'start_at' => now()->subDay(),
        'end_at'   => now()->addDay(),
        'status'   => 'active',
    ]);

    $cat = $campaign->categories()->create([
        'title_ar'        => 'أفضل سعودي',
        'title_en'        => 'Best Saudi',
        'position_slot'   => 'any',
        'required_picks'  => 1,
        'is_active'       => true,
        'award_type'      => AwardType::BestSaudi->value,
    ]);
    $cat->candidates()->create([
        'player_id'     => $nominee->id,
        'display_order' => 0,
        'is_active'     => true,
    ]);

    $candidates = app(GetEligibleCandidatesAction::class)
        ->execute($campaign, $voter, AwardType::BestSaudi);

    expect($candidates)
        ->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($candidates->pluck('id')->all())->toContain($nominee->id);
});
