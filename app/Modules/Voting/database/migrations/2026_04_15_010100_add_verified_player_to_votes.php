<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $t) {
            $t->foreignId('verified_player_id')->nullable()
                ->after('voter_identifier')
                ->constrained('players')->nullOnDelete();
            $t->enum('verification_method', ['national_id', 'mobile'])
                ->nullable()->after('verified_player_id');
            $t->string('verification_value', 32)->nullable()->after('verification_method');
            $t->boolean('is_verified')->default(false)->after('verification_value');

            $t->index('verified_player_id');
            // Hard guard: one vote per player per campaign
            $t->unique(['campaign_id', 'verified_player_id'], 'votes_campaign_player_unique');
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $t) {
            $t->dropUnique('votes_campaign_player_unique');
            $t->dropConstrainedForeignId('verified_player_id');
            $t->dropColumn(['verification_method', 'verification_value', 'is_verified']);
        });
    }
};
