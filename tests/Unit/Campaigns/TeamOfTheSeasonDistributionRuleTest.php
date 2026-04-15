<?php

declare(strict_types=1);

use App\Modules\Campaigns\Domain\TeamOfTheSeasonDistributionRule;

it('accepts a valid 3-3-4-1 distribution', function () {
    (new TeamOfTheSeasonDistributionRule())->validate([
        ['position_slot' => 'attack',     'required_picks' => 3],
        ['position_slot' => 'midfield',   'required_picks' => 3],
        ['position_slot' => 'defense',    'required_picks' => 4],
        ['position_slot' => 'goalkeeper', 'required_picks' => 1],
    ]);
    expect(true)->toBeTrue();
});

it('accepts a 4-3-3-1 (attack-heavy) distribution', function () {
    (new TeamOfTheSeasonDistributionRule())->validate([
        ['position_slot' => 'attack',     'required_picks' => 4],
        ['position_slot' => 'midfield',   'required_picks' => 3],
        ['position_slot' => 'defense',    'required_picks' => 3],
        ['position_slot' => 'goalkeeper', 'required_picks' => 1],
    ]);
    expect(true)->toBeTrue();
});

it('rejects distribution with goalkeeper != 1', function () {
    (new TeamOfTheSeasonDistributionRule())->validate([
        ['position_slot' => 'attack',     'required_picks' => 3],
        ['position_slot' => 'midfield',   'required_picks' => 3],
        ['position_slot' => 'defense',    'required_picks' => 4],
        ['position_slot' => 'goalkeeper', 'required_picks' => 2],
    ]);
})->throws(DomainException::class);

it('rejects distribution with outfield sum != 10', function () {
    (new TeamOfTheSeasonDistributionRule())->validate([
        ['position_slot' => 'attack',     'required_picks' => 3],
        ['position_slot' => 'midfield',   'required_picks' => 3],
        ['position_slot' => 'defense',    'required_picks' => 3],
        ['position_slot' => 'goalkeeper', 'required_picks' => 1],
    ]);
})->throws(DomainException::class);

it('rejects invalid position_slot', function () {
    (new TeamOfTheSeasonDistributionRule())->validate([
        ['position_slot' => 'any', 'required_picks' => 11],
    ]);
})->throws(DomainException::class);

it('splits distribution across multiple categories of same slot', function () {
    (new TeamOfTheSeasonDistributionRule())->validate([
        ['position_slot' => 'attack',     'required_picks' => 2],
        ['position_slot' => 'attack',     'required_picks' => 1],
        ['position_slot' => 'midfield',   'required_picks' => 3],
        ['position_slot' => 'defense',    'required_picks' => 4],
        ['position_slot' => 'goalkeeper', 'required_picks' => 1],
    ]);
    expect(true)->toBeTrue();
});
