<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->string('voter_identifier', 128)->index();
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 512)->nullable();
            $t->timestamp('submitted_at')->useCurrent();
            $t->timestamps();

            $t->unique(['campaign_id', 'voter_identifier']);
        });

        Schema::create('vote_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vote_id')->constrained()->cascadeOnDelete();
            // Nullable — the new club-scoped flow uses award_type +
            // candidate_player_id instead. Legacy /vote/{token} flow
            // still populates these.
            $t->foreignId('voting_category_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('candidate_id')->nullable()->constrained('voting_category_candidates')->cascadeOnDelete();
            $t->timestamps();

            $t->index(['voting_category_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_items');
        Schema::dropIfExists('votes');
    }
};
