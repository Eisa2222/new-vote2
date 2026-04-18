<?php

declare(strict_types=1);

use App\Modules\Campaigns\Actions\ApproveCampaignAction;
use App\Modules\Campaigns\Actions\DeleteCampaignAction;
use App\Modules\Campaigns\Actions\RejectCampaignAction;
use App\Modules\Campaigns\Actions\SubmitCampaignForApprovalAction;
use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;

beforeEach(function () { seedRolesAndPermissions(); });

function makeDraftCampaign(): Campaign
{
    $campaign = Campaign::create([
        'title_ar' => 'مسودة', 'title_en' => 'Draft',
        'type'     => CampaignType::IndividualAward->value,
        'status'   => CampaignStatus::Draft->value,
        'start_at' => now(), 'end_at' => now()->addDay(),
    ]);

    // Every test scenario here expects a reviewable campaign — one
    // with at least one category. Mirrors the real admin flow where
    // a category is always created before submission.
    $campaign->categories()->create([
        'title_ar'       => 'س',
        'title_en'       => 'Q',
        'category_type'  => 'single_choice',
        'position_slot'  => 'any',
        'required_picks' => 1,
        'selection_min'  => 1,
        'selection_max'  => 1,
        'is_active'      => true,
        'display_order'  => 0,
    ]);

    return $campaign;
}

it('admin submits a draft for approval; status becomes pending_approval', function () {
    $c = makeDraftCampaign();
    app(SubmitCampaignForApprovalAction::class)->execute($c);
    expect($c->fresh()->status)->toBe(CampaignStatus::PendingApproval);
});

it('committee approves → status becomes published + timestamp recorded', function () {
    $c = makeDraftCampaign();
    $c->update(['status' => CampaignStatus::PendingApproval->value]);
    app(ApproveCampaignAction::class)->execute($c);
    $c->refresh();
    expect($c->status)->toBe(CampaignStatus::Published);
    expect($c->committee_approved_at)->not->toBeNull();
});

it('committee rejects with a note → status becomes rejected + note stored', function () {
    $c = makeDraftCampaign();
    $c->update(['status' => CampaignStatus::PendingApproval->value]);
    app(RejectCampaignAction::class)->execute($c, 'Please add more candidates');
    $c->refresh();
    expect($c->status)->toBe(CampaignStatus::Rejected);
    expect($c->committee_rejection_note)->toBe('Please add more candidates');
});

it('cannot approve a campaign that is not pending', function () {
    $c = makeDraftCampaign(); // still Draft
    expect(fn() => app(ApproveCampaignAction::class)->execute($c))
        ->toThrow(\DomainException::class);
});

it('admin can delete a campaign with no votes', function () {
    $c = makeDraftCampaign();
    app(DeleteCampaignAction::class)->execute($c);
    expect(Campaign::find($c->id))->toBeNull();
});

it('delete refuses when votes exist unless force=true', function () {
    $c = makeDraftCampaign();
    \App\Modules\Voting\Models\Vote::create([
        'campaign_id'      => $c->id,
        'voter_identifier' => str_repeat('a', 64),
        'submitted_at'     => now(),
    ]);

    expect(fn() => app(DeleteCampaignAction::class)->execute($c))
        ->toThrow(\DomainException::class);

    app(DeleteCampaignAction::class)->execute($c, force: true);
    expect(Campaign::find($c->id))->toBeNull();
});
