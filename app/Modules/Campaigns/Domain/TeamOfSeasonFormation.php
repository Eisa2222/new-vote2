<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Domain;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Enums\PlayerPosition;

/**
 * Team of the Season formation rules.
 *
 * Only the goalkeeper is fixed (1). Defense / midfield / attack are flexible,
 * but together they must sum to 10 outfield players — total lineup = 11.
 *
 * Common formations: 4-3-3, 3-4-3, 4-4-2, 5-3-2, 3-5-2, 4-5-1.
 */
final class TeamOfSeasonFormation
{
    public const GOALKEEPER_COUNT = 1;
    public const OUTFIELD_TOTAL   = 10;
    public const TOTAL            = 11;
    public const MIN_LINE         = 2;
    public const MAX_LINE         = 6;

    public const DEFAULT_ATTACK   = 3;
    public const DEFAULT_MIDFIELD = 3;
    public const DEFAULT_DEFENSE  = 4;

    public const LINE_ORDER = ['goalkeeper', 'defense', 'midfield', 'attack'];

    public static function total(): int { return self::TOTAL; }

    public static function position(string $slot): ?PlayerPosition
    {
        return PlayerPosition::tryFrom($slot);
    }

    /**
     * @param  array<string,int>  $formation
     */
    public static function validate(array $formation): void
    {
        foreach (['attack', 'midfield', 'defense', 'goalkeeper'] as $slot) {
            if (! array_key_exists($slot, $formation)) {
                throw new \DomainException("Formation missing '{$slot}' line.");
            }
        }
        if ($formation['goalkeeper'] !== self::GOALKEEPER_COUNT) {
            throw new \DomainException('Goalkeeper count must be exactly '.self::GOALKEEPER_COUNT.'.');
        }
        foreach (['attack', 'midfield', 'defense'] as $slot) {
            $n = (int) $formation[$slot];
            if ($n < self::MIN_LINE || $n > self::MAX_LINE) {
                throw new \DomainException(
                    "{$slot} line must be between ".self::MIN_LINE.' and '.self::MAX_LINE.' players.',
                );
            }
        }
        $outfield = $formation['attack'] + $formation['midfield'] + $formation['defense'];
        if ($outfield !== self::OUTFIELD_TOTAL) {
            throw new \DomainException(
                "Attack + midfield + defense must equal ".self::OUTFIELD_TOTAL." (got {$outfield}).",
            );
        }
    }

    public static function default(): array
    {
        return [
            'attack'     => self::DEFAULT_ATTACK,
            'midfield'   => self::DEFAULT_MIDFIELD,
            'defense'    => self::DEFAULT_DEFENSE,
            'goalkeeper' => self::GOALKEEPER_COUNT,
        ];
    }

    public static function lineTitles(string $locale = 'ar'): array
    {
        return $locale === 'ar'
            ? ['attack' => 'خط الهجوم', 'midfield' => 'خط الوسط', 'defense' => 'خط الدفاع', 'goalkeeper' => 'حارس المرمى']
            : ['attack' => 'Attack Line', 'midfield' => 'Midfield Line', 'defense' => 'Defense Line', 'goalkeeper' => 'Goalkeeper'];
    }

    /** Reads the formation actually stored on a campaign's categories. */
    public static function fromCampaign(Campaign $c): array
    {
        $map = [];
        foreach ($c->categories as $cat) {
            if (in_array($cat->position_slot, ['attack', 'midfield', 'defense', 'goalkeeper'], true)) {
                $map[$cat->position_slot] = (int) $cat->required_picks;
            }
        }
        return $map;
    }
}
