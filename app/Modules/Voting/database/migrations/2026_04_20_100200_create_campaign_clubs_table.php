<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * campaign_clubs — one row per (campaign, club) pair. Each row owns
 * a unique `voting_link_token` so every club gets its own public URL:
 *
 *     https://vote.sfpa.sa/vote/club/{token}
 *
 * The `max_voters` cap is enforced per club (not per campaign) so one
 * busy club cannot starve the quota of another. `current_voters_count`
 * is denormalised for fast "is this club full?" checks — kept in sync
 * by IncrementCampaignClubVoterCountAction inside a transaction.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_clubs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('club_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('max_voters')->nullable();        // null = unlimited
            $t->unsignedInteger('current_voters_count')->default(0);
            $t->string('voting_link_token', 64)->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['campaign_id', 'club_id']);
            $t->index(['campaign_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_clubs');
    }
};
