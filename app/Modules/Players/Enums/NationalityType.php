<?php

declare(strict_types=1);

namespace App\Modules\Players\Enums;

/**
 * Voting-eligibility flag on a player. Drives which candidates are
 * shown for the Best-Saudi vs Best-Foreign award. Deliberately coarse
 * — no country-level detail — because the product only cares about
 * the saudi / non-saudi distinction.
 */
enum NationalityType: string
{
    case Saudi   = 'saudi';
    case Foreign = 'foreign';

    public function label(): string
    {
        return match ($this) {
            self::Saudi   => __('Saudi'),
            self::Foreign => __('Foreign'),
        };
    }
}
