<?php

declare(strict_types=1);

namespace App\Modules\Results\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Results\Domain\ResultVisibilityRule;
use App\Modules\Results\Models\CampaignResult;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicResultsController extends Controller
{
    public function show(string $token, ResultVisibilityRule $vis): View
    {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();
        $result   = CampaignResult::where('campaign_id', $campaign->id)
            ->with('items.candidate.player.club', 'items.candidate.club', 'items.category')
            ->first();

        // Do NOT leak that the result exists but is hidden — return a plain 404.
        if (! $vis->isPublic($campaign, $result)) {
            throw new NotFoundHttpException();
        }

        $view = $campaign->type === CampaignType::TeamOfTheSeason
            ? 'results::public_tots' : 'results::public';

        return view($view, compact('campaign', 'result'));
    }
}
