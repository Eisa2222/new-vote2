<?php

declare(strict_types=1);

namespace App\Modules\Results\Actions;

use App\Modules\Campaigns\Enums\ResultsVisibility;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Events\ResultsAnnounced;
use App\Modules\Results\Models\CampaignResult;
use App\Modules\Results\Domain\ResultStatusTransitionRule;
use App\Modules\Users\Actions\LogActivityAction;
use Illuminate\Support\Facades\Auth;

final class AnnounceResultsAction
{
    public function __construct(
        private readonly LogActivityAction $log,
        private readonly ResultStatusTransitionRule $rule = new ResultStatusTransitionRule(),
    ) {}

    public function execute(CampaignResult $result): CampaignResult
    {
        $this->rule->assert($result->status, ResultStatus::Announced);

        $result->update([
            'status'       => ResultStatus::Announced->value,
            'announced_at' => now(),
            'announced_by' => Auth::id(),
        ]);
        $result->items()->update(['is_announced' => true]);
        $result->campaign->update(['results_visibility' => ResultsVisibility::Announced->value]);

        $this->log->execute('results.announced', $result);
        event(new ResultsAnnounced($result));

        return $result->fresh();
    }
}
