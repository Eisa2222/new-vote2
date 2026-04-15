<?php

declare(strict_types=1);

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Services\CampaignAvailabilityService;

function makeCampaign(string $status = 'active', ?\Closure $mod = null): Campaign
{
    $c = Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x',
        'type' => 'individual_award',
        'start_at' => now()->subHour(), 'end_at' => now()->addDay(),
        'status' => $status,
    ]);
    if ($mod) $mod($c);
    return $c->fresh();
}

it('returns OK for an active in-window campaign', function () {
    $svc = app(CampaignAvailabilityService::class);
    expect($svc->reasonFor(makeCampaign()))->toBe(CampaignAvailabilityService::OK);
});

it('returns NOT_PUBLISHED for draft', function () {
    $svc = app(CampaignAvailabilityService::class);
    expect($svc->reasonFor(makeCampaign('draft')))->toBe(CampaignAvailabilityService::NOT_PUBLISHED);
});

it('returns NOT_STARTED for future start_at', function () {
    $svc = app(CampaignAvailabilityService::class);
    $c = makeCampaign('active', fn ($c) => $c->update(['start_at' => now()->addHour()]));
    expect($svc->reasonFor($c))->toBe(CampaignAvailabilityService::NOT_STARTED);
});

it('returns ENDED for past end_at', function () {
    $svc = app(CampaignAvailabilityService::class);
    $c = makeCampaign('active', fn ($c) => $c->update(['end_at' => now()->subMinute()]));
    expect($svc->reasonFor($c))->toBe(CampaignAvailabilityService::ENDED);
});

it('returns CLOSED for closed status', function () {
    $svc = app(CampaignAvailabilityService::class);
    expect($svc->reasonFor(makeCampaign('closed')))->toBe(CampaignAvailabilityService::CLOSED);
});
