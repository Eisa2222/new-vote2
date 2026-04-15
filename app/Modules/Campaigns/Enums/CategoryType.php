<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Enums;

enum CategoryType: string
{
    case SingleChoice   = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case Lineup         = 'lineup';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice   => __('Single choice'),
            self::MultipleChoice => __('Multiple choice'),
            self::Lineup         => __('Lineup'),
        };
    }
}
