<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Club-scoped voting flow needs:
 *   • nationality — saudi / foreign — drives Best-Saudi / Best-Foreign
 *     award eligibility filtering.
 *   • league_id   — so a campaign that targets specific leagues can
 *     restrict both the voter list AND the candidate list.
 *   • email       — optional post-vote profile capture.
 *
 * mobile_number + national_id already exist on the players table from
 * the earlier 2026_04_15 migration, so we only add what's new.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('players', function (Blueprint $t) {
            if (! Schema::hasColumn('players', 'nationality')) {
                $t->string('nationality', 16)->default('saudi')->index()->after('position');
            }
            if (! Schema::hasColumn('players', 'league_id')) {
                $t->foreignId('league_id')->nullable()->after('sport_id')
                    ->constrained('leagues')->nullOnDelete();
            }
            if (! Schema::hasColumn('players', 'email')) {
                $t->string('email', 180)->nullable()->after('mobile_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $t) {
            if (Schema::hasColumn('players', 'email')) {
                $t->dropColumn('email');
            }
            if (Schema::hasColumn('players', 'league_id')) {
                $t->dropConstrainedForeignId('league_id');
            }
            if (Schema::hasColumn('players', 'nationality')) {
                $t->dropColumn('nationality');
            }
        });
    }
};
