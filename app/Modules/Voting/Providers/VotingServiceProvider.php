<?php

declare(strict_types=1);

namespace App\Modules\Voting\Providers;

use App\Modules\Voting\Domain\PlayerSessionVoterIdentity;
use App\Modules\Voting\Domain\VoterIdentityStrategy;
use Illuminate\Support\ServiceProvider;

final class VotingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Player-session strategy: verification is required before voting.
        $this->app->bind(VoterIdentityStrategy::class, PlayerSessionVoterIdentity::class);
    }
}
