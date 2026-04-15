<?php

declare(strict_types=1);

namespace App\Modules\Voting\Domain;

use App\Modules\Voting\Actions\CheckVoterSessionAction;
use Illuminate\Http\Request;

/**
 * Identifies the voter from the verified session bound to a campaign.
 * Falls back to throwing — SubmitVoteAction will catch the missing session.
 */
final class PlayerSessionVoterIdentity implements VoterIdentityStrategy
{
    public function __construct(private readonly CheckVoterSessionAction $check) {}

    public function identify(Request $request, int $campaignId): string
    {
        $session = session(\App\Modules\Voting\Actions\CreateVoterSessionAction::SESSION_KEY, []);
        $entry   = $session[$campaignId] ?? null;
        if (! $entry) {
            // Will be caught and turned into a friendlier error in SubmitVoteAction.
            throw new \RuntimeException('voter_session_missing');
        }
        return 'player:'.$entry['player_id'];
    }
}
