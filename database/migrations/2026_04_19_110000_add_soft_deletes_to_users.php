<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enable soft-deletes on users so that deleting an admin sends the
 * row to an archive instead of evaporating it. Critical for the
 * audit trail (`activity_logs` references user IDs) and for the new
 * "restore from archive" UX.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $t) {
                $t->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
    }
};
