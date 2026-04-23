<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let the admin label each voting_category with the award it feeds
 * into in the new club-scoped flow:
 *   • best_saudi         → candidate pool for "Best Saudi Player"
 *   • best_foreign       → candidate pool for "Best Foreign Player"
 *   • team_of_the_season → shortlist for the TOS pitch (optional)
 *
 * Nullable because campaigns created before this migration (or those
 * that want the default "all players by nationality" behaviour) simply
 * leave it blank. When set, GetEligibleCandidatesAction uses the
 * category's candidates as the award pool instead of the full DB.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('voting_categories', function (Blueprint $t) {
            if (! Schema::hasColumn('voting_categories', 'award_type')) {
                $t->string('award_type', 32)->nullable()->index()->after('position_slot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voting_categories', function (Blueprint $t) {
            if (Schema::hasColumn('voting_categories', 'award_type')) {
                $t->dropColumn('award_type');
            }
        });
    }
};
