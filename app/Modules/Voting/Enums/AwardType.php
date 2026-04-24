<?php

declare(strict_types=1);

namespace App\Modules\Voting\Enums;

/**
 * The three awards every campaign ships with in the new flow. Kept as
 * a fixed enum (not admin-configurable categories) because each award
 * has distinct validation + candidate-filtering logic that lives in
 * code, not in the DB.
 */
enum AwardType: string
{
    case BestSaudi       = 'best_saudi';
    case BestForeign     = 'best_foreign';
    case TeamOfTheSeason = 'team_of_the_season';

    public function label(): string
    {
        return match ($this) {
            self::BestSaudi       => __('Best Saudi Player'),
            self::BestForeign     => __('Best Foreign Player'),
            self::TeamOfTheSeason => __('Team of the Season'),
        };
    }

    /** Is this award a single-player pick (true) or a whole lineup (false)? */
    public function isIndividual(): bool
    {
        return $this !== self::TeamOfTheSeason;
    }
}