<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Domain;

use App\Modules\Players\Enums\PlayerPosition;

/**
 * Validates that a campaign's categories make up a VALID Team of the Season
 * formation: goalkeeper=1, attack+midfield+defense=10, each outfield line
 * between MIN_LINE and MAX_LINE. Any formation meeting these rules is accepted.
 */
final class TeamOfTheSeasonDistributionRule
{
    /**
     * @param  array<int, array{position_slot:string, required_picks:int}>  $categories
     */
    public function validate(array $categories): void
    {
        $totals = ['attack' => 0, 'midfield' => 0, 'defense' => 0, 'goalkeeper' => 0];

        foreach ($categories as $c) {
            $slot = $c['position_slot'] ?? null;
            if (! array_key_exists($slot, $totals)) {
                throw new \DomainException("Invalid position_slot '{$slot}' for Team of the Season.");
            }
            $totals[$slot] += (int) ($c['required_picks'] ?? 0);
        }

        TeamOfSeasonFormation::validate($totals);
    }

    /**
     * Validates a list of picks (one per slot occurrence) against the
     * expected formation map.
     *
     * @param  array<int, PlayerPosition|string>  $picks
     * @param  array<string,int>|null             $expected  pre-validated formation
     */
    public function validatePicks(array $picks, ?array $expected = null): void
    {
        $counts = ['attack' => 0, 'midfield' => 0, 'defense' => 0, 'goalkeeper' => 0];
        foreach ($picks as $position) {
            $p = $position instanceof PlayerPosition ? $position->value : $position;
            if (! isset($counts[$p])) {
                throw new \DomainException("Invalid player position '{$p}'.");
            }
            $counts[$p]++;
        }

        if ($expected === null) {
            TeamOfSeasonFormation::validate($counts);
            return;
        }

        if ($counts !== $expected) {
            throw new \DomainException(sprintf(
                'Team of the Season picks must match the formation — expected %d attack, %d midfield, %d defense, %d goalkeeper; got %d/%d/%d/%d.',
                $expected['attack'], $expected['midfield'], $expected['defense'], $expected['goalkeeper'],
                $counts['attack'], $counts['midfield'], $counts['defense'], $counts['goalkeeper'],
            ));
        }
    }
}
