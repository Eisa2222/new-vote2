<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Enums;

enum CandidateType: string
{
    case Player = 'player';
    case Club   = 'club';
    case Team   = 'team';
}
