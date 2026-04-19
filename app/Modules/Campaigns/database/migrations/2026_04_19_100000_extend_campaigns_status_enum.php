<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bug-fix migration: the original 2026_01_01 create-campaigns migration
 * defined the status column as
 *     ENUM('draft', 'published', 'active', 'closed', 'archived')
 * but the domain enum (CampaignStatus) has evolved to include
 * `pending_approval` and `rejected`. On MySQL this caused
 *   SQLSTATE[01000] Data truncated for column 'status'
 * any time an admin submitted a campaign for committee approval.
 *
 * SQLite stores ENUMs as plain TEXT and never enforces the list, so
 * the existing test suite passed — this is a MySQL-only bug.
 *
 * We only alter the column when the underlying driver actually needs
 * it (MySQL / MariaDB). On SQLite we no-op so tests and local runs
 * keep working; on PostgreSQL the column is VARCHAR so we also no-op.
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE campaigns MODIFY COLUMN status ".
            "ENUM('draft', 'pending_approval', 'published', 'active', 'closed', 'archived', 'rejected') ".
            "NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Collapse any rows that used the new states back to drafts before
        // shrinking the ENUM, so no row becomes invalid mid-rollback.
        DB::statement("UPDATE campaigns SET status = 'draft' WHERE status IN ('pending_approval', 'rejected')");
        DB::statement(
            "ALTER TABLE campaigns MODIFY COLUMN status ".
            "ENUM('draft', 'published', 'active', 'closed', 'archived') ".
            "NOT NULL DEFAULT 'draft'"
        );
    }
};
