<?php

declare(strict_types=1);

namespace App\Modules\Results\Events;

use App\Modules\Results\Models\CampaignResult;
use Illuminate\Foundation\Events\Dispatchable;

final class ResultsHidden
{
    use Dispatchable;
    public function __construct(public readonly CampaignResult $result) {}
}
