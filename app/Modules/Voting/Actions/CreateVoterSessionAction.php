<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Enums\VerificationMethod;

/**
 * Stores a short-lived voter context in the session, scoped per campaign so a
 * voter verifying against campaign A cannot vote on campaign B.
 */
final class CreateVoterSessionAction
{
    public const SESSION_KEY = 'voter_sessions';

    public function execute(
        Campaign $campaign,
        Player $player,
        VerificationMethod $method,
        string $value,
    ): void {
        $sessions = session(self::SESSION_KEY, []);
        $sessions[$campaign->id] = [
            'player_id' => $player->id,
            'method'    => $method->value,
            'value'     => $value,
            'verified_at' => now()->toIso8601String(),
        ];
        session([self::SESSION_KEY => $sessions]);
        session()->save();
    }

    public function clear(Campaign $campaign): void
    {
        $sessions = session(self::SESSION_KEY, []);
        unset($sessions[$campaign->id]);
        session([self::SESSION_KEY => $sessions]);
    }
}
