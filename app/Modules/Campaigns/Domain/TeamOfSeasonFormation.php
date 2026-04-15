<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Domain;

use App\Modules\Players\Enums\PlayerPosition;

/**
 * Central source of truth for the Team of the Season formation.
 *
 * Change the map here and every downstream validator/builder/seeder follows.
 */
final class TeamOfSeasonFormation
{
    public const MAP = [
        'attack'     => 3,
        'midfield'   => 3,
        'defense'    => 4,
        'goalkeeper' => 1,
    ];

    public const LINE_ORDER = ['goalkeeper', 'defense', 'midfield', 'attack'];

    public static function slots(): array { return self::MAP; }

    public static function total(): int { return array_sum(self::MAP); }

    public static function position(string $slot): ?PlayerPosition
    {
        return PlayerPosition::tryFrom($slot);
    }

    public static function lineTitles(string $locale = 'ar'): array
    {
        return $locale === 'ar'
            ? ['attack' => 'خط الهجوم', 'midfield' => 'خط الوسط', 'defense' => 'خط الدفاع', 'goalkeeper' => 'حارس المرمى']
            : ['attack' => 'Attack Line', 'midfield' => 'Midfield Line', 'defense' => 'Defense Line', 'goalkeeper' => 'Goalkeeper'];
    }
}
