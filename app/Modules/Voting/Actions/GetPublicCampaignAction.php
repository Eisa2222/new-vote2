<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Models\Campaign;

final class GetPublicCampaignAction
{
    public function execute(string $token): Campaign
    {
        return Campaign::where('public_token', $token)
            ->with([
                'categories' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
                'categories.candidates' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
                'categories.candidates.player.club',
                'categories.candidates.club',
            ])
            ->firstOrFail();
    }
}
