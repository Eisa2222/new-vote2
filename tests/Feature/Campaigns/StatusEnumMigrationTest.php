<?php

declare(strict_types=1);

use App\Modules\Campaigns\Actions\SubmitCampaignForApprovalAction;
use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;

/*
 * Bug #1 / #6 regression:
 *  Submitting a Draft campaign to committee approval writes
 *  `pending_approval` into campaigns.status. The original create-table
 *  migration only whitelisted 5 values, so MySQL truncated the write
 *  and tearing through the controller surfaced a 500 ("Data truncated").
 *
 *  The dedicated ALTER-TABLE migration now extends the ENUM. This test
 *  just proves the end-to-end: a real SubmitCampaignForApprovalAction
 *  run succeeds and the row has the new status persisted.
 */
beforeEach(function () { seedRolesAndPermissions(); });

it('stores pending_approval without truncation after the ENUM extension', function () {
    $campaign = Campaign::create([
        'title_ar' => 'تحديث', 'title_en' => 'Test',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Draft->value,
        'start_at' => now(), 'end_at' => now()->addDay(),
    ]);
    $campaign->categories()->create([
        'title_ar' => 'س', 'title_en' => 'Q',
        'category_type'  => 'single_choice',
        'position_slot'  => 'any',
        'required_picks' => 1,
        'selection_min'  => 1,
        'selection_max'  => 1,
        'is_active'      => true,
        'display_order'  => 0,
    ]);

    app(SubmitCampaignForApprovalAction::class)->execute($campaign);

    $row = \DB::table('campaigns')->where('id', $campaign->id)->first();
    expect($row->status)->toBe('pending_approval');
    expect($campaign->fresh()->status)->toBe(CampaignStatus::PendingApproval);
});
