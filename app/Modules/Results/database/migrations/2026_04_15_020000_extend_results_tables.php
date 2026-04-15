<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaign_results', function (Blueprint $t) {
            $t->unsignedInteger('total_votes')->default(0)->after('status');
            $t->foreignId('calculated_by')->nullable()->after('approved_by')
                ->constrained('users')->nullOnDelete();
            $t->foreignId('announced_by')->nullable()->after('calculated_by')
                ->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable()->after('announced_by');
        });

        Schema::table('result_items', function (Blueprint $t) {
            $t->decimal('vote_percentage', 5, 2)->default(0)->after('votes_count');
            $t->string('position', 20)->nullable()->after('voting_category_id');
            $t->json('metadata')->nullable()->after('is_winner');
            $t->boolean('is_announced')->default(false)->after('is_winner');
            // `rank` already exists — rename concept to `rank_order` would break; keep.
            $t->index(['campaign_result_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('campaign_results', function (Blueprint $t) {
            $t->dropConstrainedForeignId('calculated_by');
            $t->dropConstrainedForeignId('announced_by');
            $t->dropColumn(['total_votes', 'notes']);
        });
        Schema::table('result_items', function (Blueprint $t) {
            $t->dropIndex(['campaign_result_id', 'position']);
            $t->dropColumn(['vote_percentage', 'position', 'metadata', 'is_announced']);
        });
    }
};
