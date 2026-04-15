<?php

declare(strict_types=1);

use App\Modules\Players\Enums\PlayerPosition;

it('creates a player with valid data via API', function () {
    $club = makeClub();
    $sport = makeFootball();

    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/players', [
            'club_id' => $club->id, 'sport_id' => $sport->id,
            'name_ar' => 'محمد', 'name_en' => 'Mohamed',
            'position' => 'attack', 'jersey_number' => 7,
        ])
        ->assertCreated();

    expect(\App\Modules\Players\Models\Player::where('name_en', 'Mohamed')->exists())->toBeTrue();
});

it('rejects invalid position enum', function () {
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/players', [
            'club_id' => makeClub()->id, 'sport_id' => makeFootball()->id,
            'name_ar' => 'x', 'name_en' => 'x', 'position' => 'manager',
        ])
        ->assertJsonValidationErrors(['position']);
});

it('rejects duplicate jersey number within same club+sport', function () {
    $club = makeClub(); $sport = makeFootball();
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/players', [
            'club_id' => $club->id, 'sport_id' => $sport->id,
            'name_ar' => 'a', 'name_en' => 'A', 'position' => 'attack', 'jersey_number' => 10,
        ])->assertCreated();

    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/players', [
            'club_id' => $club->id, 'sport_id' => $sport->id,
            'name_ar' => 'b', 'name_en' => 'B', 'position' => 'midfield', 'jersey_number' => 10,
        ])
        ->assertJsonValidationErrors(['jersey_number']);
});

it('allows same jersey number across different clubs', function () {
    $s = makeFootball();
    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/players', [
            'club_id' => makeClub()->id, 'sport_id' => $s->id,
            'name_ar' => 'a', 'name_en' => 'A', 'position' => 'attack', 'jersey_number' => 10,
        ])->assertCreated();

    $this->actingAs(makeSuperAdmin(), 'sanctum')
        ->postJson('/api/v1/players', [
            'club_id' => makeClub()->id, 'sport_id' => $s->id,
            'name_ar' => 'b', 'name_en' => 'B', 'position' => 'attack', 'jersey_number' => 10,
        ])->assertCreated();
});

it('accepts all four position enum values', function () {
    foreach (PlayerPosition::cases() as $p) {
        expect($p->value)->toBeIn(['attack', 'midfield', 'defense', 'goalkeeper']);
    }
});
