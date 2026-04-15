<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Models\Campaign;

final class CheckVoterSessionAction
{
    /**
     * @return array{player_id:int, method:string, value:string, verified_at:string}|null
     */
    public function execute(Campaign $campaign): ?array
    {
        $sessions = session(CreateVoterSessionAction::SESSION_KEY, []);
        return $sessions[$campaign->id] ?? null;
    }
}
