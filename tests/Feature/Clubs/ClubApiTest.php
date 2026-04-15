<?php

declare(strict_types=1);

use App\Modules\Clubs\Models\Club;

it('lists clubs', function () {
    Club::factory()->count(3)->create();
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->getJson('/api/v1/clubs')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);
});

it('creates a club', function () {
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/clubs', [
            'name_ar' => 'نادي الهلال', 'name_en' => 'Al Hilal', 'short_name' => 'HIL',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name_en', 'Al Hilal');

    expect(Club::where('name_en', 'Al Hilal')->exists())->toBeTrue();
});

it('rejects duplicate club name', function () {
    Club::factory()->create(['name_en' => 'Al Nassr', 'name_ar' => 'النصر']);
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/clubs', ['name_ar' => 'النصر', 'name_en' => 'Al Nassr'])
        ->assertUnprocessable();
});

it('forbids users without permission', function () {
    $u = \App\Models\User::factory()->create();
    $this->actingAs($u, 'sanctum')
        ->postJson('/api/v1/clubs', ['name_ar' => 'x', 'name_en' => 'y'])
        ->assertForbidden();
});

it('validates required bilingual fields', function () {
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/clubs', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name_ar', 'name_en']);
});

it('updates a club', function () {
    $club = Club::factory()->create(['name_en' => 'Original']);
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->putJson("/api/v1/clubs/{$club->id}", ['name_en' => 'Renamed'])
        ->assertOk();
    expect($club->fresh()->name_en)->toBe('Renamed');
});

it('soft-deletes a club', function () {
    $club = Club::factory()->create();
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->deleteJson("/api/v1/clubs/{$club->id}")
        ->assertNoContent();
    expect($club->fresh()->trashed())->toBeTrue();
});
