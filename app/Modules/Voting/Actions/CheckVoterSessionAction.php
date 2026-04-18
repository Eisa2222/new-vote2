<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Campaigns\Models\Campaign;

final class CheckVoterSessionAction
{
    /**
     * Returns the active voter session for this campaign, or null if
     * none exists OR the entry is older than the configured TTL.
     *
     * Why a TTL: a voter who verifies their identity and walks away
     * leaves an authenticated browser tab. Without a TTL anyone passing
     * by could submit the ballot under that voter's identity even hours
     * later. 15 minutes is enough to read the candidates and pick, and
     * re-verification is one form away.
     *
     * @return array{player_id:int, method:string, value:string, verified_at:string}|null
     */
    public function execute(Campaign $campaign): ?array
    {
        $sessions = session(CreateVoterSessionAction::SESSION_KEY, []);
        $entry    = $sessions[$campaign->id] ?? null;
        if (! $entry) {
            return null;
        }

        $ttl = (int) config('voting.voter_session.ttl_minutes', 15);
        if ($ttl > 0 && isset($entry['verified_at'])) {
            try {
                $verifiedAt = \Carbon\Carbon::parse($entry['verified_at']);
                if ($verifiedAt->lt(now()->subMinutes($ttl))) {
                    // Expired — drop the entry so the next request sees
                    // "not verified" and re-prompts cleanly.
                    unset($sessions[$campaign->id]);
                    session([CreateVoterSessionAction::SESSION_KEY => $sessions]);
                    return null;
                }
            } catch (\Throwable) {
                // Malformed timestamp → treat as expired.
                return null;
            }
        }

        return $entry;
    }
}
