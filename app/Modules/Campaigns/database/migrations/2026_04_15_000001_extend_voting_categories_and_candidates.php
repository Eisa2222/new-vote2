<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voting_categories', function (Blueprint $t) {
            // Keep existing title_ar/title_en/required_picks; add richer fields.
            $t->enum('category_type', ['single_choice', 'multiple_choice', 'lineup'])
                ->default('single_choice')->after('title_en');
            $t->unsignedSmallInteger('selection_min')->default(1)->after('required_picks');
            $t->unsignedSmallInteger('selection_max')->default(1)->after('selection_min');
            $t->boolean('is_active')->default(true)->after('selection_max');
        });

        Schema::table('voting_category_candidates', function (Blueprint $t) {
            $t->enum('candidate_type', ['player', 'club', 'team'])->default('player')->after('voting_category_id');
            $t->boolean('is_active')->default(true)->after('display_order');
        });

        // Seed defaults for existing rows so tests and prod data stay coherent.
        \Illuminate\Support\Facades\DB::table('voting_categories')->update([
            'selection_min' => \Illuminate\Support\Facades\DB::raw('required_picks'),
            'selection_max' => \Illuminate\Support\Facades\DB::raw('required_picks'),
        ]);
        \Illuminate\Support\Facades\DB::table('voting_category_candidates')
            ->whereNotNull('player_id')->update(['candidate_type' => 'player']);
        \Illuminate\Support\Facades\DB::table('voting_category_candidates')
            ->whereNotNull('club_id')->update(['candidate_type' => 'club']);
    }

    public function down(): void
    {
        Schema::table('voting_categories', function (Blueprint $t) {
            $t->dropColumn(['category_type', 'selection_min', 'selection_max', 'is_active']);
        });
        Schema::table('voting_category_candidates', function (Blueprint $t) {
            $t->dropColumn(['candidate_type', 'is_active']);
        });
    }
};
