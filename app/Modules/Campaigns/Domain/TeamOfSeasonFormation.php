<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Domain;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\PlayerPosition;
use DomainException;

/**
 * Team of the Season formation rules — pure domain, no UI concerns.
 *
 * Goalkeeper is fixed at 1. Defense/midfield/attack are flexible,
 * but together they must sum to OUTFIELD_TOTAL.
 *
 * Constants are sourced from config('voting.team_of_the_season') so
 * operations can tune them without touching compiled code.
 */
final class TeamOfSeasonFormation
{
    public const LINE_ORDER = ['goalkeeper', 'defense', 'midfield', 'attack'];

    public static function goalkeeperCount(): int
    {
        return (int) config('voting.team_of_the_season.goalkeeper_count', 1);
    }

    public static function outfieldTotal(): int
    {
        return (int) config('voting.team_of_the_season.outfield_total', 10);
    }

    public static function total(): int
    {
        return (int) config('voting.team_of_the_season.total', 11);
    }

    public static function minLine(): int
    {
        return (int) config('voting.team_of_the_season.min_line', 2);
    }

    public static function maxLine(): int
    {
        return (int) config('voting.team_of_the_season.max_line', 6);
    }

    public static function position(string $slot): ?PlayerPosition
    {
        return PlayerPosition::tryFrom($slot);
    }

    /**
     * @param  array<string,int>  $formation
     *
     * @throws DomainException
     */
    public static function validate(array $formation): void
    {
        $goalkeeperCount = self::goalkeeperCount();
        $outfieldTotal   = self::outfieldTotal();
        $minLine         = self::minLine();
        $maxLine         = self::maxLine();

        foreach (self::LINE_ORDER as $slot) {
            if (! array_key_exists($slot, $formation)) {
                throw new DomainException("Formation missing '{$slot}' line.");
            }
        }
        if ((int) $formation['goalkeeper'] !== $goalkeeperCount) {
            throw new DomainException("Goalkeeper count must be exactly {$goalkeeperCount}.");
        }
        foreach (['attack', 'midfield', 'defense'] as $slot) {
            $count = (int) $formation[$slot];
            if ($count < $minLine || $count > $maxLine) {
                throw new DomainException(
                    "{$slot} line must be between {$minLine} and {$maxLine} players.",
                );
            }
        }
        $outfield = $formation['attack'] + $formation['midfield'] + $formation['defense'];
        if ($outfield !== $outfieldTotal) {
            throw new DomainException(
                "Attack + midfield + defense must equal {$outfieldTotal} (got {$outfield}).",
            );
        }
    }

    /** @return array<string,int> */
    public static function default(): array
    {
        $config = config('voting.team_of_the_season');
        return [
            'attack'     => (int) ($config['default_attack']   ?? 3),
            'midfield'   => (int) ($config['default_midfield'] ?? 3),
            'defense'    => (int) ($config['default_defense']  ?? 4),
            'goalkeeper' => self::goalkeeperCount(),
        ];
    }

    /**
     * Reads the formation actually stored on a campaign's categories.
     *
     * @return array<string,int>
     */
    public static function fromCampaign(Campaign $campaign): array
    {
        $formation = [];
        foreach ($campaign->categories as $category) {
            if (in_array($category->position_slot, self::LINE_ORDER, true)) {
                $formation[$category->position_slot] = (int) $category->required_picks;
            }
        }
        return $formation;
    }
}
