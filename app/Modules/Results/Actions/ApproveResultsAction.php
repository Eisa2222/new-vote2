<?php

declare(strict_types=1);

namespace App\Modules\Results\Actions;

use App\Modules\Campaigns\Enums\ResultsVisibility;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Models\CampaignResult;
use App\Modules\Results\Events\ResultsApproved;
use App\Modules\Results\Domain\ResultStatusTransitionRule;
use App\Modules\Users\Actions\LogActivityAction;
use Illuminate\Support\Facades\Auth;

final class ApproveResultsAction
{
    public function __construct(
        private readonly LogActivityAction $log,
        private readonly ResultStatusTransitionRule $rule = new ResultStatusTransitionRule(),
    ) {}

    public function execute(CampaignResult $result): CampaignResult
    {
        $this->rule->assert($result->status, ResultStatus::Approved);

        $result->update([
            'status'       => ResultStatus::Approved->value,
            'approved_at'  => now(),
            'approved_by'  => Auth::id(),
        ]);
        $result->campaign->update(['results_visibility' => ResultsVisibility::Approved->value]);

        $this->log->execute('results.approved', $result);
        event(new ResultsApproved($result));

        return $result->fresh();
    }
}
