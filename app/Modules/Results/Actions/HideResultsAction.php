<?php

declare(strict_types=1);

namespace App\Modules\Results\Actions;

use App\Modules\Campaigns\Enums\ResultsVisibility;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Models\CampaignResult;
use App\Modules\Results\Domain\ResultStatusTransitionRule;
use App\Modules\Results\Events\ResultsHidden;
use App\Modules\Users\Actions\LogActivityAction;

final class HideResultsAction
{
    public function __construct(
        private readonly LogActivityAction $log,
        private readonly ResultStatusTransitionRule $rule = new ResultStatusTransitionRule(),
    ) {}

    public function execute(CampaignResult $result): CampaignResult
    {
        $this->rule->assert($result->status, ResultStatus::Hidden);

        $result->update(['status' => ResultStatus::Hidden->value]);
        $result->campaign->update(['results_visibility' => ResultsVisibility::Hidden->value]);

        $this->log->execute('results.hidden', $result);
        event(new ResultsHidden($result));

        return $result->fresh();
    }
}
