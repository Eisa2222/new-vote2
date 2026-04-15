<?php

declare(strict_types=1);

use App\Modules\Campaigns\Actions\CloseVotingCampaignAction;
use App\Modules\Campaigns\Actions\PublishVotingCampaignAction;
use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Users\Actions\LogActivityAction;

function draftCampaign(array $attrs = []): Campaign
{
    return Campaign::create(array_merge([
        'title_ar' => 'x', 'title_en' => 'x',
        'type' => 'individual_award',
        'start_at' => now()->addHour(), 'end_at' => now()->addDay(),
        'status' => 'draft',
    ], $attrs));
}

it('publish promotes future-start draft to published', function () {
    $c = draftCampaign();
    (new PublishVotingCampaignAction(new LogActivityAction()))->execute($c);
    expect($c->fresh()->status->value)->toBe('published');
});

it('publish promotes active-window draft to active', function () {
    $c = draftCampaign(['start_at' => now()->subMinute(), 'end_at' => now()->addDay()]);
    (new PublishVotingCampaignAction(new LogActivityAction()))->execute($c);
    expect($c->fresh()->status->value)->toBe('active');
});

it('cannot re-publish an already active campaign', function () {
    $c = draftCampaign();
    $c->update(['status' => 'active']);
    (new PublishVotingCampaignAction(new LogActivityAction()))->execute($c);
})->throws(DomainException::class);

it('close works from active', function () {
    $c = draftCampaign();
    $c->update(['status' => 'active']);
    (new CloseVotingCampaignAction(new LogActivityAction()))->execute($c);
    expect($c->fresh()->status->value)->toBe('closed');
});

it('cannot close archived campaign', function () {
    $c = draftCampaign();
    $c->update(['status' => 'archived']);
    (new CloseVotingCampaignAction(new LogActivityAction()))->execute($c);
})->throws(DomainException::class);

it('transition rules are enforced', function () {
    expect(CampaignStatus::Draft->canTransitionTo(CampaignStatus::Active))->toBeFalse(); // must go through published
    expect(CampaignStatus::Published->canTransitionTo(CampaignStatus::Active))->toBeTrue();
    expect(CampaignStatus::Closed->canTransitionTo(CampaignStatus::Active))->toBeFalse();
    expect(CampaignStatus::Archived->canTransitionTo(CampaignStatus::Draft))->toBeFalse();
});
