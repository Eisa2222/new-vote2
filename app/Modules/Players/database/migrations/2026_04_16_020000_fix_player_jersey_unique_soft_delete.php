<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reverted: adding deleted_at to the UNIQUE tuple weakens the constraint
 * because SQLite/MySQL treat NULL as distinct, allowing two ACTIVE rows
 * to coexist with the same jersey number. Keep the strict 3-column
 * unique and handle soft-deleted jersey reuse at the application layer.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('players', function (Blueprint $t) {
            try {
                $t->dropUnique(['club_id', 'sport_id', 'jersey_number', 'deleted_at']);
            } catch (\Throwable $e) { /* already dropped */ }
        });
        Schema::table('players', function (Blueprint $t) {
            $t->unique(['club_id', 'sport_id', 'jersey_number']);
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $t) {
            $t->dropUnique(['club_id', 'sport_id', 'jersey_number']);
            $t->unique(['club_id', 'sport_id', 'jersey_number', 'deleted_at']);
        });
    }
};
