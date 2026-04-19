<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable email templates.
 *
 * Each row = one template that the notifications layer picks up before
 * falling back to a hardcoded default. Lookup key is the triple
 * (key, campaign_type, locale):
 *   • key           — event name, e.g. campaign.results_announced
 *   • campaign_type — null for generic, or one of
 *                     individual_award / team_award / team_of_the_season
 *                     so the Team of the Season announcement can read
 *                     differently from the Player-of-the-Year one.
 *   • locale        — "ar" / "en"; always both seeded on install.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $t) {
            $t->id();
            $t->string('key', 80)->index();              // e.g. "campaign.results_announced"
            $t->string('campaign_type', 40)->nullable(); // null = generic fallback
            $t->string('locale', 8);                     // "ar" / "en"
            $t->string('subject', 240);
            $t->text('body');                            // simple HTML with {variables}
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['key', 'campaign_type', 'locale'], 'email_templates_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
