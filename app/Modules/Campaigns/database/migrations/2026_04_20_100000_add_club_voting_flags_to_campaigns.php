<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New club-scoped voting flow:
 *   • allow_self_vote     — can a voter pick themselves as a candidate?
 *   • allow_teammate_vote — can a voter pick someone from the same club?
 *
 * Defaults mirror the historically-lenient behaviour (both true) so no
 * live campaign changes behaviour on deploy. Admins flip them on per
 * new campaign.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $t) {
            if (! Schema::hasColumn('campaigns', 'allow_self_vote')) {
                $t->boolean('allow_self_vote')->default(true)->after('max_voters');
            }
            if (! Schema::hasColumn('campaigns', 'allow_teammate_vote')) {
                $t->boolean('allow_teammate_vote')->default(true)->after('allow_self_vote');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $t) {
            if (Schema::hasColumn('campaigns', 'allow_teammate_vote')) {
                $t->dropColumn('allow_teammate_vote');
            }
            if (Schema::hasColumn('campaigns', 'allow_self_vote')) {
                $t->dropColumn('allow_self_vote');
            }
        });
    }
};
