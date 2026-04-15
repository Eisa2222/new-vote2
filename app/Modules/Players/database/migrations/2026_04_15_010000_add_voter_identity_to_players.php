<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('players', function (Blueprint $t) {
            $t->string('national_id', 20)->nullable()->unique()->after('jersey_number');
            $t->string('mobile_number', 20)->nullable()->unique()->after('national_id');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $t) {
            $t->dropUnique(['national_id']);
            $t->dropUnique(['mobile_number']);
            $t->dropColumn(['national_id', 'mobile_number']);
        });
    }
};
