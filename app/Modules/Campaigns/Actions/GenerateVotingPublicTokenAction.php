<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Support\Str;

final class GenerateVotingPublicTokenAction
{
    /** Generates a collision-free token and persists it on the campaign. */
    public function execute(Campaign $campaign, int $length = 48): string
    {
        do {
            $token = Str::random($length);
        } while (Campaign::where('public_token', $token)->exists());

        $campaign->update(['public_token' => $token]);
        return $token;
    }
}
