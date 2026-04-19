<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('title_ar', 180);
            $t->string('title_en', 180);
            $t->text('description_ar')->nullable();
            $t->text('description_en')->nullable();
            $t->enum('type', ['individual_award', 'team_award', 'team_of_the_season']);
            $t->dateTime('start_at');
            $t->dateTime('end_at');
            $t->unsignedInteger('max_voters')->nullable();
            $t->string('public_token', 64)->unique();
            $t->enum('status', ['draft', 'published', 'active', 'closed', 'archived'])
                ->default('draft')->index();
            $t->enum('results_visibility', ['hidden', 'approved', 'announced'])->default('hidden');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['status', 'start_at', 'end_at']);
        });

        Schema::create('voting_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->string('title_ar', 180);
            $t->string('title_en', 180);
            // For team_of_the_season, one category per position slot group:
            $t->enum('position_slot', ['attack', 'midfield', 'defense', 'goalkeeper', 'any'])
                ->default('any');
            $t->unsignedSmallInteger('required_picks')->default(1); // e.g. 3 attackers
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->timestamps();
        });

        Schema::create('voting_category_candidates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('voting_category_id')->constrained()->cascadeOnDelete();
            $t->foreignId('player_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('club_id')->nullable()->constrained()->cascadeOnDelete(); // for team awards
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->timestamps();

            $t->unique(['voting_category_id', 'player_id']);
            $t->unique(['voting_category_id', 'club_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_category_candidates');
        Schema::dropIfExists('voting_categories');
        Schema::dropIfExists('campaigns');
    }
};
