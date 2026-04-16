<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * No-op on fresh installs (the strict 3-column unique already exists from the
 * original create_players_table migration). Only acts if a previous version of
 * this migration left behind a weaker 4-column index.
 */
return new class extends Migration {
    public function up(): void
    {
        if ($this->indexExists('players_club_id_sport_id_jersey_number_deleted_at_unique')) {
            Schema::table('players', function (Blueprint $t) {
                $t->dropUnique(['club_id', 'sport_id', 'jersey_number', 'deleted_at']);
            });
        }
        if (! $this->indexExists('players_club_id_sport_id_jersey_number_unique')) {
            Schema::table('players', function (Blueprint $t) {
                $t->unique(['club_id', 'sport_id', 'jersey_number']);
            });
        }
    }

    public function down(): void
    {
        // no-op
    }

    private function indexExists(string $index): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return (bool) DB::selectOne(
                "SELECT 1 AS x FROM sqlite_master WHERE type='index' AND name = ?",
                [$index],
            );
        }
        if ($driver === 'mysql') {
            return (bool) DB::selectOne(
                "SELECT 1 AS x FROM information_schema.statistics WHERE table_schema = DATABASE() AND index_name = ?",
                [$index],
            );
        }
        return false;
    }
};
