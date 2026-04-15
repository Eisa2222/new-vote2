<?php

declare(strict_types=1);

namespace App\Modules\Voting\Enums;

enum VerificationMethod: string
{
    case NationalId = 'national_id';
    case Mobile     = 'mobile';
}
