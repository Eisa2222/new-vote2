<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The new voting flow makes the voter a registered player, so we can
 * link votes directly to a player_id instead of a SHA-256 hash. We
 * also start tracking the source (which campaign_club link was used)
 * and replace the category/candidate FK pair in vote_items with a
 * richer shape that covers both individual-award and TOTS picks.
 *
 * The legacy `voter_identifier` column is kept so the old `/vote/{token}`
 * flow still works during the transition, but the new pipeline
 * populates player_id / club_id / campaign_club_id and leaves the
 * hash NULL.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $t) {
            if (! Schema::hasColumn('votes', 'player_id')) {
                $t->foreignId('player_id')->nullable()->after('campaign_id')
                    ->constrained('players')->nullOnDelete();
            }
            if (! Schema::hasColumn('votes', 'club_id')) {
                $t->foreignId('club_id')->nullable()->after('player_id')
                    ->constrained('clubs')->nullOnDelete();
            }
            if (! Schema::hasColumn('votes', 'campaign_club_id')) {
                $t->foreignId('campaign_club_id')->nullable()->after('club_id')
                    ->constrained('campaign_clubs')->nullOnDelete();
            }
        });

        // Legacy unique(campaign_id, voter_identifier) still applies to
        // the old hashed flow. The new flow is protected by the
        // (campaign_id, player_id) unique added here. Wrapped in a
        // guard so re-running the migration after a partial failure
        // (e.g. during the initial rollout) is idempotent.
        if (! self::hasIndex('votes', 'votes_campaign_player_unique')) {
            Schema::table('votes', function (Blueprint $t) {
                $t->unique(['campaign_id', 'player_id'], 'votes_campaign_player_unique');
            });
        }

        Schema::table('vote_items', function (Blueprint $t) {
            if (! Schema::hasColumn('vote_items', 'award_type')) {
                $t->string('award_type', 32)->nullable()->index()->after('voting_category_id');
            }
            if (! Schema::hasColumn('vote_items', 'category_key')) {
                $t->string('category_key', 40)->nullable()->after('award_type');
            }
            if (! Schema::hasColumn('vote_items', 'candidate_player_id')) {
                $t->foreignId('candidate_player_id')->nullable()->after('category_key')
                    ->constrained('players')->nullOnDelete();
            }
            if (! Schema::hasColumn('vote_items', 'candidate_club_id')) {
                $t->foreignId('candidate_club_id')->nullable()->after('candidate_player_id')
                    ->constrained('clubs')->nullOnDelete();
            }
            if (! Schema::hasColumn('vote_items', 'position_key')) {
                $t->string('position_key', 16)->nullable()->index()->after('candidate_club_id');
            }
        });

        // Make voting_category_id and candidate_id nullable — the new
        // flow doesn't use them (it uses award_type + candidate_player_id).
        // Driver-aware; SQLite silently allows without ALTER, MySQL needs it.
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE vote_items MODIFY voting_category_id BIGINT UNSIGNED NULL');
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE vote_items MODIFY candidate_id BIGINT UNSIGNED NULL');
        }
    }

    /** Driver-agnostic "does this index exist?" check. */
    private static function hasIndex(string $table, string $index): bool
    {
        $conn = Schema::getConnection();
        try {
            $rows = match ($conn->getDriverName()) {
                'sqlite' => $conn->select("SELECT name FROM sqlite_master WHERE type='index' AND name=?", [$index]),
                default  => $conn->select('SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1', [$table, $index]),
            };
            return ! empty($rows);
        } catch (\Throwable) {
            return false;
        }
    }

    public function down(): void
    {
        Schema::table('vote_items', function (Blueprint $t) {
            foreach (['position_key', 'candidate_club_id', 'candidate_player_id', 'category_key', 'award_type'] as $c) {
                if (Schema::hasColumn('vote_items', $c)) {
                    $method = in_array($c, ['candidate_club_id', 'candidate_player_id'], true)
                        ? 'dropConstrainedForeignId' : 'dropColumn';
                    $t->$method($c);
                }
            }
        });

        Schema::table('votes', function (Blueprint $t) {
            try { $t->dropUnique('votes_campaign_player_unique'); } catch (\Throwable) {}
            foreach (['campaign_club_id', 'club_id', 'player_id'] as $c) {
                if (Schema::hasColumn('votes', $c)) {
                    $t->dropConstrainedForeignId($c);
                }
            }
        });
    }
};
