<?php

declare(strict_types=1);

use App\Modules\Shared\Services\SettingsService;
use App\Modules\Sports\Models\Sport;

it('admin settings page loads', function () {
    $this->actingAs(makeSuperAdmin())
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('General', false);
});

it('non-admin cannot access settings', function () {
    $u = \App\Models\User::factory()->create();
    $this->actingAs($u)->get('/admin/settings')->assertForbidden();
});

it('admin saves general settings', function () {
    $this->actingAs(makeSuperAdmin())
        ->post('/admin/settings/general', [
            'app_name' => 'FPA Voting',
            'contact_email' => 'admin@sfpa.sa',
            'default_max_voters' => 5000,
            'default_campaign_days' => 14,
            'committee_name_ar' => 'لجنة التصويت',
            'committee_name_en' => 'Voting Committee',
        ])->assertRedirect();

    expect(app(SettingsService::class)->get('app_name'))->toBe('FPA Voting');
});

it('admin can add a new sport', function () {
    $this->actingAs(makeSuperAdmin())
        ->post('/admin/settings/sports', [
            'name_ar' => 'الركبي', 'name_en' => 'Rugby', 'status' => 'active',
        ])->assertRedirect();
    expect(Sport::where('slug', 'rugby')->exists())->toBeTrue();
});

it('cannot delete a sport linked to clubs', function () {
    $sport = makeFootball();
    makeClub()->sports()->attach($sport->id);
    $this->actingAs(makeSuperAdmin())
        ->delete("/admin/settings/sports/{$sport->id}")
        ->assertSessionHasErrors(['sport']);
});

it('can delete an orphan sport', function () {
    $sport = Sport::create(['slug' => 'chess', 'name_ar' => 'شطرنج', 'name_en' => 'Chess', 'status' => 'active']);
    $this->actingAs(makeSuperAdmin())
        ->delete("/admin/settings/sports/{$sport->id}")
        ->assertRedirect();
    expect(Sport::find($sport->id))->toBeNull();
});
